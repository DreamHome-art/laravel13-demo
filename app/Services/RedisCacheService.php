<?php

/*
|--------------------------------------------------------------------------
| Redis 缓存服务类 (RedisCacheService)
|--------------------------------------------------------------------------
|
| 文件用途：封装 Redis 常用缓存操作，提供统一、类型安全、带日志的缓存接口。
| 适用于：缓存查询结果、计数器、分布式数据暂存、Hash 结构缓存等场景。
|
| 方法概览：
|   基础操作：get / set / has / forget / ttl
|   记忆缓存：remember（不存在则回调生成并缓存）
|   计数器：increment / decrement
|   批量操作：many / putMany / forgetMany
|   永久存储：forever（不设 TTL）
|   批量扫描删除：flushByPattern（按通配符清除）
|   Hash 操作：hGet / hSet / hGetAll / hDel
|
| 使用方式：
|   1. 依赖注入：public function __construct(RedisCacheService $cache)
|   2. 服务容器：app(RedisCacheService::class)
|   3. 已在 AppServiceProvider 注册为单例
|
| 设计说明：
|   - 所有值统一 serialize 存储，保证读取时类型一致还原
|   - 计数器方法（increment/decrement）直接操作 Redis 整型，不经过序列化
|   - 自动加业务前缀（cache:），与 Redis 全局前缀（laravel-database-）叠加隔离
|   - 慢操作（>slowThreshold 毫秒）记录 warning 日志，失败记录 error 日志
|   - 日志自动携带 trace_id（配合 TraceIdProcessor 实现全链路追踪）
|
*/

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisCacheService
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
     * 默认过期时间（秒），set/remember 未指定 TTL 时使用
     */
    protected int $defaultTtl;

    /**
     * 慢操作阈值（毫秒），超过此值记录 warning 日志
     */
    protected float $slowThreshold;

    /**
     * 构造函数
     *
     * @param string $connection     Redis 连接名（cache 使用 db1，default 使用 db0）
     * @param string $prefix         业务键前缀
     * @param int    $defaultTtl     默认过期时间（秒）
     * @param float  $slowThreshold  慢操作阈值（毫秒）
     */
    public function __construct(
        string $connection = 'cache',
        string $prefix = 'cache:',
        int $defaultTtl = 3600,
        float $slowThreshold = 50.0
    ) {
        $this->redis = Redis::connection($connection);
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
        $this->slowThreshold = $slowThreshold;
    }

    /*
    |----------------------------------------------------------------------
    | 基础操作（String 类型）
    |----------------------------------------------------------------------
    */

    /**
     * 读取缓存
     *
     * @param  string $key      缓存键（不含前缀）
     * @param  mixed  $default  键不存在时的默认返回值
     * @return mixed            缓存值或默认值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $start = microtime(true);
        $fullKey = $this->buildKey($key);

        try {
            $val = $this->redis->get($fullKey);
            $elapsed = $this->elapsedMs($start);
            $this->log('get', $key, $elapsed);

            return $val !== null ? $this->unserialize($val) : $default;
        } catch (\Exception $e) {
            $this->log('get', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 写入缓存（带过期时间）
     *
     * @param  string $key     缓存键
     * @param  mixed  $value  缓存值（自动序列化）
     * @param  int|null $ttl  过期时间（秒），为 null 用默认值
     * @return bool           是否写入成功
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $start = microtime(true);
        $fullKey = $this->buildKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            // setex：写入并设置过期时间（秒），phpredis 返回 true
            $result = $this->redis->setex($fullKey, $ttl, $this->serialize($value));
            $elapsed = $this->elapsedMs($start);
            $this->log('set', $key, $elapsed);

            return $result;
        } catch (\Exception $e) {
            $this->log('set', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 判断键是否存在
     */
    public function has(string $key): bool
    {
        $start = microtime(true);
        try {
            $result = (bool) $this->redis->exists($this->buildKey($key));
            $this->log('has', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('has', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除缓存
     */
    public function forget(string $key): bool
    {
        $start = microtime(true);
        try {
            $result = (bool) $this->redis->del($this->buildKey($key));
            $this->log('forget', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('forget', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取键的剩余过期时间（秒）
     * 返回值：-1 表示永久存在，-2 表示键不存在，正数为剩余秒数
     */
    public function ttl(string $key): int
    {
        $start = microtime(true);
        try {
            $result = (int) $this->redis->ttl($this->buildKey($key));
            $this->log('ttl', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('ttl', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 记忆缓存
    |----------------------------------------------------------------------
    */

    /**
     * 记忆缓存：键不存在时执行回调，结果写入缓存并返回；存在则直接返回缓存值
     * 适用场景：缓存数据库查询结果，避免重复查询
     *
     * 注意：回调返回 null 会被视为"未缓存"，每次都重新执行回调。
     *
     * @param  string   $key      缓存键
     * @param  callable $callback  缓存未命中时的数据生成回调
     * @param  int|null $ttl      过期时间（秒）
     * @return mixed              缓存值或回调生成的值
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        // 缓存未命中，执行回调生成数据
        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /*
    |----------------------------------------------------------------------
    | 计数器（直接操作 Redis 整型，不经过序列化）
    |----------------------------------------------------------------------
    */

    /**
     * 自增计数器
     * 注意：调用此方法的键不应通过 set() 写入（set 会序列化导致 incrby 失败）。
     *       键不存在时自动创建并从 0 开始递增。
     *
     * @param  string $key  缓存键
     * @param  int    $step 步长
     * @return int          递增后的值
     */
    public function increment(string $key, int $step = 1): int
    {
        $start = microtime(true);
        try {
            $result = (int) $this->redis->incrby($this->buildKey($key), $step);
            $this->log('increment', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('increment', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 自减计数器
     */
    public function decrement(string $key, int $step = 1): int
    {
        $start = microtime(true);
        try {
            $result = (int) $this->redis->decrby($this->buildKey($key), $step);
            $this->log('decrement', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('decrement', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 批量操作
    |----------------------------------------------------------------------
    */

    /**
     * 批量读取缓存
     *
     * @param  array $keys  键名数组
     * @return array        [key => value] 关联数组，键不存在时 value 为 null
     */
    public function many(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $start = microtime(true);
        $fullKeys = array_map(fn ($k) => $this->buildKey($k), $keys);

        try {
            // mget 批量读取，返回值数组（按传入顺序对应，不存在为 null）
            $values = $this->redis->mget($fullKeys);
            $elapsed = $this->elapsedMs($start);
            $this->log('many', implode(',', $keys), $elapsed);

            $result = [];
            foreach ($keys as $i => $key) {
                $val = $values[$i] ?? null;
                $result[$key] = $val !== null ? $this->unserialize($val) : null;
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('many', implode(',', $keys), $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 批量写入缓存（使用 pipeline 减少 RTT）
     *
     * @param  array    $values  [key => value] 关联数组
     * @param  int|null $ttl     过期时间（秒）
     * @return bool              是否写入成功
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        if (empty($values)) {
            return true;
        }

        $start = microtime(true);
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            // pipeline 在单次网络往返中发送多条命令，提升批量写入性能
            $this->redis->pipeline(function ($pipe) use ($values, $ttl) {
                foreach ($values as $key => $value) {
                    $pipe->setex($this->buildKey($key), $ttl, $this->serialize($value));
                }
            });
            $elapsed = $this->elapsedMs($start);
            $this->log('putMany', count($values).' keys', $elapsed);

            return true;
        } catch (\Exception $e) {
            $this->log('putMany', count($values).' keys', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 批量删除缓存
     *
     * @param  array $keys  键名数组
     * @return int          实际删除的键数量
     */
    public function forgetMany(array $keys): int
    {
        if (empty($keys)) {
            return 0;
        }

        $start = microtime(true);
        $fullKeys = array_map(fn ($k) => $this->buildKey($k), $keys);

        try {
            // del 接受多个键，返回实际删除数量
            $result = (int) $this->redis->del($fullKeys);
            $elapsed = $this->elapsedMs($start);
            $this->log('forgetMany', count($keys).' keys', $elapsed);

            return $result;
        } catch (\Exception $e) {
            $this->log('forgetMany', count($keys).' keys', $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 永久存储
    |----------------------------------------------------------------------
    */

    /**
     * 永久写入缓存（不设过期时间，直到显式删除）
     * 适用场景：配置类数据、不常变更的字典数据
     */
    public function forever(string $key, mixed $value): bool
    {
        $start = microtime(true);
        try {
            // set 不带 TTL 即永久存储
            $result = (bool) $this->redis->set($this->buildKey($key), $this->serialize($value));
            $this->log('forever', $key, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('forever', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 批量扫描删除
    |----------------------------------------------------------------------
    */

    /**
     * 按通配符模式批量删除缓存
     * 示例：flushByPattern('user:*') 删除所有以 user: 开头的缓存键
     *
     * 注意：使用 KEYS 命令，在生产环境大键空间（百万级）下可能阻塞 Redis，
     *       建议仅在低峰期或键数量可控的场景使用。
     *
     * @param  string $pattern  匹配模式（不含业务前缀，自动拼接 prefix + pattern + *）
     * @return int              实际删除的键数量
     */
    public function flushByPattern(string $pattern): int
    {
        $start = microtime(true);
        // 拼接完整模式：业务前缀 + 用户模式 + 通配符
        $fullPattern = $this->prefix.$pattern.'*';

        try {
            $keys = $this->redis->keys($fullPattern);
            if (empty($keys)) {
                $this->log('flushByPattern', $pattern, $this->elapsedMs($start));

                return 0;
            }

            // phpredis keys() 返回带全局前缀的完整键名，而 del() 会再次追加全局前缀，
            // 直接传会导致前缀重复无法删除。此处去除全局前缀，让 del 自动处理前缀拼接。
            $globalPrefix = (string) config('database.redis.options.prefix', '');
            $realKeys = array_map(function ($key) use ($globalPrefix) {
                if ($globalPrefix !== '' && str_starts_with($key, $globalPrefix)) {
                    return substr($key, strlen($globalPrefix));
                }

                return $key;
            }, $keys);

            $result = (int) $this->redis->del($realKeys);
            $elapsed = $this->elapsedMs($start);
            $this->log('flushByPattern', $pattern, $elapsed);

            return $result;
        } catch (\Exception $e) {
            $this->log('flushByPattern', $pattern, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | Hash 类型操作
    |----------------------------------------------------------------------
    */

    /**
     * 写入 Hash 字段
     *
     * @param  string $key    Hash 键
     * @param  string $field  字段名
     * @param  mixed  $value  字段值（自动序列化）
     * @return int            新增字段返回 1，更新已有字段返回 0
     */
    public function hSet(string $key, string $field, mixed $value): int
    {
        $start = microtime(true);
        try {
            $result = (int) $this->redis->hset($this->buildKey($key), $field, $this->serialize($value));
            $this->log('hSet', $key.'.'.$field, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('hSet', $key.'.'.$field, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 读取 Hash 字段
     *
     * @param  string $key      Hash 键
     * @param  string $field    字段名
     * @param  mixed  $default  字段不存在时的默认值
     * @return mixed            字段值或默认值
     */
    public function hGet(string $key, string $field, mixed $default = null): mixed
    {
        $start = microtime(true);
        try {
            $val = $this->redis->hget($this->buildKey($key), $field);
            $this->log('hGet', $key.'.'.$field, $this->elapsedMs($start));

            return $val !== null ? $this->unserialize($val) : $default;
        } catch (\Exception $e) {
            $this->log('hGet', $key.'.'.$field, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 读取整个 Hash（所有字段）
     *
     * @param  string $key  Hash 键
     * @return array        [field => value] 关联数组
     */
    public function hGetAll(string $key): array
    {
        $start = microtime(true);
        try {
            $items = $this->redis->hgetall($this->buildKey($key));
            $elapsed = $this->elapsedMs($start);
            $this->log('hGetAll', $key, $elapsed);

            $result = [];
            foreach ($items as $field => $val) {
                $result[$field] = $this->unserialize($val);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('hGetAll', $key, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除 Hash 字段
     *
     * @param  string $key    Hash 键
     * @param  string $field  字段名
     * @return int            实际删除的字段数量
     */
    public function hDel(string $key, string $field): int
    {
        $start = microtime(true);
        try {
            $result = (int) $this->redis->hdel($this->buildKey($key), $field);
            $this->log('hDel', $key.'.'.$field, $this->elapsedMs($start));

            return $result;
        } catch (\Exception $e) {
            $this->log('hDel', $key.'.'.$field, $this->elapsedMs($start), $e->getMessage());
            throw $e;
        }
    }

    /*
    |----------------------------------------------------------------------
    | 内部辅助方法
    |----------------------------------------------------------------------
    */

    /**
     * 构建完整缓存键：业务前缀 + 用户键
     * 示例：buildKey('user:1') → 'cache:user:1'
     */
    protected function buildKey(string $key): string
    {
        return $this->prefix.$key;
    }

    /**
     * 序列化值：统一用 PHP serialize，保证读取时类型还原
     * 支持 null / bool / int / float / string / array / object
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * 反序列化值：尝试 unserialize，非序列化字符串原样返回
     */
    protected function unserialize(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $result = @unserialize($value);

        // unserialize 失败返回 false；但 serialize(false) = 'b:0;' 也是合法值
        if ($result === false && $value !== 'b:0;') {
            return $value;
        }

        return $result;
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
     * @param  string      $key     操作键（业务键，不含前缀）
     * @param  float       $elapsed 耗时（毫秒）
     * @param  string|null $error   错误信息（null 表示成功）
     */
    protected function log(string $action, string $key, float $elapsed, ?string $error = null): void
    {
        $context = [
            'action' => $action,
            'key' => $key,
            'elapsed_ms' => $elapsed,
        ];

        if ($error !== null) {
            // 操作失败：记录 error 级别，便于排查
            $context['error'] = $error;
            Log::error('Redis 缓存操作失败', $context);
        } elseif ($elapsed > $this->slowThreshold) {
            // 慢操作：记录 warning 级别，提示性能问题
            Log::warning('Redis 缓存慢操作', $context);
        }
        // 常规成功操作不记录，避免日志膨胀
        // 日志自动携带 trace_id（由 TraceIdProcessor 注入），便于全链路追踪
    }
}
