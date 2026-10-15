<?php

/*
|--------------------------------------------------------------------------
| 布隆过滤器服务 (BloomFilterService)
|--------------------------------------------------------------------------
|
| 文件用途：基于 Redis 位图（SETBIT/GETBIT）实现概率型数据结构布隆过滤器，
|           用于在海量数据场景下高效判断元素"一定不存在"或"可能存在"。
|
| 适用场景：
|   - 缓存穿透防护（拦截查询不存在 Key 的请求，避免穿透到数据库）
|   - 邮箱/用户名注册去重快速预检
|   - 海量 URL 去重（爬虫已访问标记）
|   - 黑名单/敏感词快速过滤
|
| 方法概览：
|   写入：add / addBatch（批量写入）
|   查询：exists / existsBatch（批量查询）
|   统计：count（近似元素数量，基于置位比例估算）
|   维护：clear（清空过滤器）
|
| 使用方式：
|   1. 依赖注入：public function __construct(BloomFilterService $bloom)
|   2. 服务容器：app(BloomFilterService::class)
|   3. 已在 AppServiceProvider 注册为单例
|
| 算法原理：
|   - 使用 k 个不同种子的哈希函数，将元素映射到位数组的 k 个位置并置 1
|   - 查询时检查这 k 个位置，全部为 1 视为"可能存在"，任意为 0 视为"一定不存在"
|   - 存在假阳性（false positive），不存在假阴性（false negative）
|
| 参数选择（容量 n=10000，误判率 p=1%）：
|   - 位数组长度 m = ceil(-n * ln(p) / (ln(2)^2)) ≈ 95851
|   - 哈希函数数 k = round((m/n) * ln(2)) ≈ 7
|   - 实际占用 Redis 内存 ≈ m/8/1024 ≈ 11.7 KB
|
| 设计说明：
|   - 使用 Redis 单 Key 存储位数组，利用 SETBIT/GETBIT 的 O(1) 时间复杂度
|   - 哈希函数采用 CRC32 + 不同种子的方式生成，避免引入复杂哈希库依赖
|   - 批量操作使用 pipeline 减少 RTT
|   - 慢操作（>slowThreshold 毫秒）记录 warning 日志，失败记录 error 日志
|   - 日志自动携带 trace_id（配合 TraceIdProcessor 实现全链路追踪）
|
*/

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BloomFilterService
{
    /**
     * Redis 连接实例
     */
    protected Connection $redis;

    /**
     * 业务键前缀（与 Redis 全局前缀叠加，用于按模块隔离数据）
     */
    protected string $prefix;

    /**
     * 过滤器名称（多实例场景下区分不同过滤器）
     */
    protected string $filterName;

    /**
     * 位数组长度 m（决定内存占用与冲突率）
     */
    protected int $bitSize;

    /**
     * 哈希函数数量 k（影响误判率与查询次数）
     */
    protected int $hashCount;

    /**
     * 慢操作阈值（毫秒），超过此值记录 warning 日志
     */
    protected float $slowThreshold;

    /**
     * 构造函数
     *
     * @param  string $connection     Redis 连接名（cache 使用 db1，default 使用 db0）
     * @param  string $prefix         业务键前缀
     * @param  string $filterName     过滤器名称（多实例隔离）
     * @param  int    $bitSize        位数组长度 m（必须为正数）
     * @param  int    $hashCount      哈希函数数量 k（必须为正数）
     * @param  float  $slowThreshold  慢操作阈值（毫秒）
     */
    public function __construct(
        string $connection = 'cache',
        string $prefix = 'bloom:',
        string $filterName = 'default',
        int $bitSize = 95851,
        int $hashCount = 7,
        float $slowThreshold = 50.0
    ) {
        $this->redis = Redis::connection($connection);
        $this->prefix = $prefix;
        $this->filterName = $filterName;
        $this->bitSize = $bitSize;
        $this->hashCount = $hashCount;
        $this->slowThreshold = $slowThreshold;
    }

    /*
    |----------------------------------------------------------------------
    | 写入操作
    |----------------------------------------------------------------------
    */

    /**
     * 添加元素到布隆过滤器
     *
     * @param  string $value  待添加元素
     * @return bool           是否写入成功
     */
    public function add(string $value): bool
    {
        $start = microtime(true);
        $fullKey = $this->buildKey();

        try {
            // 计算元素对应的 k 个位偏移，逐个置 1
            $offsets = $this->getOffsets($value);
            foreach ($offsets as $offset) {
                $this->redis->setbit($fullKey, $offset, 1);
            }
            $elapsed = $this->elapsedMs($start);
            $this->log('add', $value, $elapsed);

            return true;
        } catch (\Exception $e) {
            $this->log('add', $value, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 批量添加元素到布隆过滤器（使用 pipeline 减少 RTT）
     *
     * @param  array $values  待添加元素数组
     * @return int            实际添加的元素数量
     */
    public function addBatch(array $values): int
    {
        if (empty($values)) {
            return 0;
        }

        $start = microtime(true);
        $fullKey = $this->buildKey();

        try {
            // pipeline 在单次网络往返中发送多条 SETBIT 命令，提升批量写入性能
            $this->redis->pipeline(function ($pipe) use ($values) {
                foreach ($values as $value) {
                    // 为每个元素计算 k 个位偏移并置 1
                    $offsets = $this->getOffsets((string) $value);
                    foreach ($offsets as $offset) {
                        $pipe->setbit($fullKey, $offset, 1);
                    }
                }
            });
            $elapsed = $this->elapsedMs($start);
            $this->log('addBatch', count($values).' items', $elapsed);

            return count($values);
        } catch (\Exception $e) {
            $this->log('addBatch', count($values).' items', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 查询操作
    |----------------------------------------------------------------------
    */

    /**
     * 判断元素是否可能存在于布隆过滤器
     *
     * 注意：返回 true 表示"可能存在"（存在假阳性），
     *       返回 false 表示"一定不存在"（无假阴性）。
     *
     * @param  string $value  待查询元素
     * @return bool           true=可能存在，false=一定不存在
     */
    public function exists(string $value): bool
    {
        $start = microtime(true);
        $fullKey = $this->buildKey();

        try {
            $offsets = $this->getOffsets($value);
            foreach ($offsets as $offset) {
                // 任一位为 0 即可判定元素一定不存在，提前返回
                if ((int) $this->redis->getbit($fullKey, $offset) === 0) {
                    $elapsed = $this->elapsedMs($start);
                    $this->log('exists', $value, $elapsed);

                    return false;
                }
            }
            $elapsed = $this->elapsedMs($start);
            $this->log('exists', $value, $elapsed);

            return true;
        } catch (\Exception $e) {
            $this->log('exists', $value, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 批量查询元素是否存在（使用 pipeline 减少 RTT）
     *
     * @param  array $values  待查询元素数组
     * @return array           [value => bool] 关联数组，true=可能存在，false=一定不存在
     */
    public function existsBatch(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $start = microtime(true);
        $fullKey = $this->buildKey();

        try {
            // 为每个元素计算 k 个位偏移，pipeline 批量 GETBIT
            $allOffsets = [];
            $offsetIndex = []; // 记录每个元素的偏移在结果数组中的位置区间
            $results = [];

            foreach ($values as $value) {
                $offsets = $this->getOffsets((string) $value);
                $offsetIndex[(string) $value] = count($allOffsets);
                $allOffsets = array_merge($allOffsets, $offsets);
            }

            $bitValues = $this->redis->pipeline(function ($pipe) use ($fullKey, $allOffsets) {
                foreach ($allOffsets as $offset) {
                    $pipe->getbit($fullKey, $offset);
                }
            });

            // 根据 pipeline 返回的位数组判断每个元素是否存在
            foreach ($values as $value) {
                $valueStr = (string) $value;
                $startIdx = $offsetIndex[$valueStr];
                $exists = true;
                // 检查该元素对应的 k 个位是否全部为 1
                for ($i = 0; $i < $this->hashCount; $i++) {
                    if ((int) $bitValues[$startIdx + $i] === 0) {
                        $exists = false;
                        break;
                    }
                }
                $results[$valueStr] = $exists;
            }

            $elapsed = $this->elapsedMs($start);
            $this->log('existsBatch', count($values).' items', $elapsed);

            return $results;
        } catch (\Exception $e) {
            $this->log('existsBatch', count($values).' items', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 统计与维护
    |----------------------------------------------------------------------
    */

    /**
     * 估算当前过滤器中的元素数量
     *
     * 算法：基于位数组中置位（值为 1）的比例估算
     *   公式：n ≈ -(m / k) * ln(1 - X / m)
     *   其中 m 为位数组长度，k 为哈希函数数，X 为置位数
     *
     * 注意：此为近似值，仅在过滤器未饱和时较准确。
     *
     * @return int 近似元素数量
     */
    public function count(): int
    {
        $start = microtime(true);
        $fullKey = $this->buildKey();

        try {
            // BITCOUNT 统计位图中值为 1 的位数
            $setBits = (int) $this->redis->bitcount($fullKey);
            $elapsed = $this->elapsedMs($start);
            $this->log('count', 'bitcount='.$setBits, $elapsed);

            // 全 0 的特殊情况：避免 log(1) = 0 导致除零
            if ($setBits === 0) {
                return 0;
            }

            // 当置位比例接近 100% 时，过滤器已饱和，估算失去意义
            $ratio = $setBits / $this->bitSize;
            if ($ratio >= 0.999) {
                return (int) round($this->bitSize / $this->hashCount);
            }

            // 应用估算公式：n ≈ -(m / k) * ln(1 - X / m)
            $estimated = -($this->bitSize / $this->hashCount) * log(1 - $ratio);

            return max(0, (int) round($estimated));
        } catch (\Exception $e) {
            $this->log('count', '', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清空布隆过滤器（删除底层 Redis Key）
     *
     * @return bool 是否清空成功
     */
    public function clear(): bool
    {
        $start = microtime(true);
        try {
            $result = (bool) $this->redis->del($this->buildKey());
            $elapsed = $this->elapsedMs($start);
            $this->log('clear', '', $elapsed);

            return $result;
        } catch (\Exception $e) {
            $this->log('clear', '', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 配置访问器
    |----------------------------------------------------------------------
    */

    /**
     * 获取位数组长度 m
     */
    public function getBitSize(): int
    {
        return $this->bitSize;
    }

    /**
     * 获取哈希函数数量 k
     */
    public function getHashCount(): int
    {
        return $this->hashCount;
    }

    /**
     * 获取当前过滤器名称
     */
    public function getFilterName(): string
    {
        return $this->filterName;
    }

    /*
    |----------------------------------------------------------------------
    | 内部辅助方法
    |----------------------------------------------------------------------
    */

    /**
     * 构建完整 Redis 键：业务前缀 + 过滤器名
     * 示例：buildKey() → 'bloom:default'
     */
    protected function buildKey(): string
    {
        return $this->prefix.$this->filterName;
    }

    /**
     * 计算元素对应的 k 个位偏移
     *
     * 算法：使用双重哈希（Double Hashing）技术
     *   - h1 = CRC32(value + seed1)
     *   - h2 = CRC32(value + seed2)
     *   - 第 i 个偏移 = (h1 + i * h2) mod m
     * 该技术能在仅用两个哈希函数的情况下，生成 k 个独立的位偏移，
     * 是布隆过滤器的经典优化方案。
     *
     * @param  string $value  待哈希的元素
     * @return array          k 个位偏移数组（均 < bitSize）
     */
    protected function getOffsets(string $value): array
    {
        $offsets = [];
        // 双重哈希：用两个不同种子生成基础哈希值，再线性组合
        $h1 = crc32($value.'_seed_a');
        $h2 = crc32($value.'_seed_b');

        // 确保 h2 非零（否则所有偏移会退化为相同值）
        if ($h2 === 0) {
            $h2 = 1;
        }

        for ($i = 0; $i < $this->hashCount; $i++) {
            // 双重哈希公式：offset_i = (h1 + i * h2) mod m
            // 使用 PHP_INT_MAX 取模避免负数影响
            $offset = (($h1 + $i * $h2) % $this->bitSize + $this->bitSize) % $this->bitSize;
            $offsets[] = (int) $offset;
        }

        return $offsets;
    }

    /**
     * 计算耗时（毫秒）
     */
    protected function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * 记录操作日志
     * 慢操作（>slowThreshold）记录 warning，失败记录 error
     * 常规操作不记录 info，避免日志膨胀（如需调试可调整此处）
     *
     * @param  string      $action  操作名称
     * @param  string      $key     操作键或元素值（用于上下文追溯）
     * @param  float       $elapsed 耗时（毫秒）
     * @param  string|null $error   错误信息（null 表示成功）
     */
    protected function log(string $action, string $key, float $elapsed, ?string $error = null): void
    {
        $context = [
            'action' => $action,
            'key' => $key,
            'filter' => $this->filterName,
            'elapsed_ms' => $elapsed,
        ];

        if ($error !== null) {
            // 操作失败：记录 error 级别，便于排查
            $context['error'] = $error;
            Log::error('布隆过滤器操作失败', $context);
        } elseif ($elapsed > $this->slowThreshold) {
            // 慢操作：记录 warning 级别，提示性能问题
            Log::warning('布隆过滤器慢操作', $context);
        }
        // 常规成功操作不记录，避免日志膨胀
        // 日志自动携带 trace_id（由 TraceIdProcessor 注入），便于全链路追踪
    }
}
