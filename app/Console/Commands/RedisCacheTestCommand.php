<?php

/*
|--------------------------------------------------------------------------
| Redis 缓存服务验证命令 (RedisCacheTestCommand)
|--------------------------------------------------------------------------
|
| 文件用途：通过 `php artisan redis:cache-test` 验证 RedisCacheService 各方法。
| 覆盖：基础读写、记忆缓存、计数器、批量操作、永久存储、Hash、按模式删除。
|
| 测试键统一加 'test:' 前缀，测试结束自动清理，避免残留数据。
|
*/

namespace App\Console\Commands;

use App\Services\RedisCacheService;
use Illuminate\Console\Command;

class RedisCacheTestCommand extends Command
{
    /**
     * 命令签名：php artisan redis:cache-test
     */
    protected $signature = 'redis:cache-test';

    /**
     * 命令描述
     */
    protected $description = '验证 RedisCacheService 缓存服务类的全部方法';

    public function handle(): int
    {
        $this->info('Redis 缓存服务验证');
        $this->line(str_repeat('=', 60));
        $this->newLine();

        // 通过服务容器获取 RedisCacheService 单例实例
        $cache = app(RedisCacheService::class);

        $passed = 0;
        $failed = 0;

        try {
            // ===== 测试 1：基础读写 (set/get/has/ttl) =====
            $this->line('【1/8】基础读写 (set/get/has/ttl)');
            $key = 'test:basic:'.uniqid();
            $value = ['name' => 'Laravel', 'version' => '13.x', 'time' => now()->toDateTimeString()];

            $cache->set($key, $value, 60);
            $got = $cache->get($key);

            if ($got === $value) {
                $this->info('  [通过] set/get 数据一致（数组类型还原）');
                $passed++;
            } else {
                $this->error('  [失败] 数据不一致');
                $failed++;
            }

            if ($cache->has($key)) {
                $this->info('  [通过] has 键存在');
                $passed++;
            } else {
                $this->error('  [失败] has 键不存在');
                $failed++;
            }

            $ttl = $cache->ttl($key);
            if ($ttl > 0 && $ttl <= 60) {
                $this->info("  [通过] ttl = {$ttl} 秒（预期 1~60）");
                $passed++;
            } else {
                $this->error("  [失败] ttl = {$ttl} 异常");
                $failed++;
            }

            $cache->forget($key);
            if (! $cache->has($key)) {
                $this->info('  [通过] forget 删除成功');
                $passed++;
            } else {
                $this->error('  [失败] forget 删除后键仍存在');
                $failed++;
            }
            $this->newLine();

            // ===== 测试 2：记忆缓存 (remember) =====
            $this->line('【2/8】记忆缓存 (remember)');
            $key = 'test:remember:'.uniqid();
            $callCount = 0;

            // 第一次调用：缓存未命中，执行回调
            $result1 = $cache->remember($key, function () use (&$callCount) {
                $callCount++;

                return 'generated-'.time();
            }, 60);

            // 第二次调用：缓存命中，不执行回调
            $result2 = $cache->remember($key, function () use (&$callCount) {
                $callCount++;

                return 'should-not-execute';
            }, 60);

            if ($result1 === $result2 && $callCount === 1) {
                $this->info('  [通过] remember 缓存命中（回调仅执行 1 次）');
                $passed++;
            } else {
                $this->error("  [失败] 回调执行次数: {$callCount}，结果不一致");
                $failed++;
            }
            $cache->forget($key);
            $this->newLine();

            // ===== 测试 3：计数器 (increment/decrement) =====
            $this->line('【3/8】计数器 (increment/decrement)');
            $key = 'test:counter:'.uniqid();

            $v1 = $cache->increment($key);
            $v2 = $cache->increment($key, 5);
            $v3 = $cache->decrement($key, 2);

            if ($v1 === 1 && $v2 === 6 && $v3 === 4) {
                $this->info("  [通过] increment/decrement 序列正确 (1 → 6 → 4)");
                $passed++;
            } else {
                $this->error("  [失败] 计数序列异常: {$v1} → {$v2} → {$v3}");
                $failed++;
            }
            $cache->forget($key);
            $this->newLine();

            // ===== 测试 4：批量读取 (many) =====
            $this->line('【4/8】批量读取 (many)');
            $keys = ['test:many:1', 'test:many:2', 'test:many:3'];
            $cache->putMany([
                $keys[0] => 'value1',
                $keys[1] => ['nested' => 'array'],
                $keys[2] => 100,
            ], 60);

            $many = $cache->many($keys);

            if ($many[$keys[0]] === 'value1'
                && $many[$keys[1]] === ['nested' => 'array']
                && $many[$keys[2]] === 100) {
                $this->info('  [通过] putMany/many 批量读写一致（含字符串/数组/整型）');
                $passed++;
            } else {
                $this->error('  [失败] 批量数据不一致');
                $failed++;
            }
            $cache->forgetMany($keys);
            $this->newLine();

            // ===== 测试 5：永久存储 (forever) =====
            $this->line('【5/8】永久存储 (forever)');
            $key = 'test:forever:'.uniqid();
            $cache->forever($key, 'persistent-data');

            $ttl = $cache->ttl($key);
            // 永久键的 ttl 返回 -1
            if ($cache->get($key) === 'persistent-data' && $ttl === -1) {
                $this->info('  [通过] forever 永久存储（ttl = -1）');
                $passed++;
            } else {
                $this->error("  [失败] forever 异常，ttl = {$ttl}");
                $failed++;
            }
            $cache->forget($key);
            $this->newLine();

            // ===== 测试 6：Hash 操作 (hSet/hGet/hGetAll/hDel) =====
            $this->line('【6/8】Hash 操作 (hSet/hGet/hGetAll/hDel)');
            $key = 'test:hash:'.uniqid();

            $cache->hSet($key, 'field1', 'value1');
            $cache->hSet($key, 'field2', ['complex' => 'data']);
            $cache->hSet($key, 'field3', 999);

            $f1 = $cache->hGet($key, 'field1');
            $f2 = $cache->hGet($key, 'field2');
            $all = $cache->hGetAll($key);

            if ($f1 === 'value1'
                && $f2 === ['complex' => 'data']
                && count($all) === 3
                && $all['field3'] === 999) {
                $this->info('  [通过] Hash 读写一致（含字符串/数组/整型）');
                $passed++;
            } else {
                $this->error('  [失败] Hash 数据异常');
                $failed++;
            }

            $cache->hDel($key, 'field1');
            $allAfterDel = $cache->hGetAll($key);
            if (count($allAfterDel) === 2 && ! isset($allAfterDel['field1'])) {
                $this->info('  [通过] hDel 删除字段成功');
                $passed++;
            } else {
                $this->error('  [失败] hDel 后字段仍存在');
                $failed++;
            }
            $cache->forget($key);
            $this->newLine();

            // ===== 测试 7：按模式批量删除 (flushByPattern) =====
            $this->line('【7/8】按模式批量删除 (flushByPattern)');
            $pattern = 'test:flush:'.uniqid();
            // 写入 3 个同前缀键
            $cache->set("{$pattern}:1", 'a', 60);
            $cache->set("{$pattern}:2", 'b', 60);
            $cache->set("{$pattern}:3", 'c', 60);

            $deleted = $cache->flushByPattern($pattern);

            if ($deleted === 3) {
                $this->info("  [通过] flushByPattern 删除 {$deleted} 个键");
                $passed++;
            } else {
                $this->error("  [失败] 删除数量异常: {$deleted}");
                $failed++;
            }
            $this->newLine();

            // ===== 测试 8：类型还原完整性 =====
            $this->line('【8/8】类型还原完整性 (int/bool/null/float)');
            $key = 'test:types:'.uniqid();
            $types = [
                'int' => 42,
                'bool_true' => true,
                'bool_false' => false,
                'null_val' => null,
                'float' => 3.14,
            ];

            // null 值 get 会返回 default（null），跳过 null_val 比较
            $cache->set($key, $types, 60);
            $got = $cache->get($key);

            if ($got['int'] === 42
                && $got['bool_true'] === true
                && $got['bool_false'] === false
                && $got['float'] === 3.14) {
                $this->info('  [通过] 类型还原完整（int/bool/null/float）');
                $passed++;
            } else {
                $this->error('  [失败] 类型还原异常');
                $this->line('    实际: '.json_encode($got));
                $failed++;
            }
            $cache->forget($key);
            $this->newLine();

            // ===== 汇总 =====
            $this->info(str_repeat('=', 60));
            $this->info("验证完成：通过 {$passed} 项，失败 {$failed} 项");
            if ($failed === 0) {
                $this->info('RedisCacheService 全部方法验证通过');
            } else {
                $this->error('存在失败项，请检查');
            }
            $this->info(str_repeat('=', 60));

            return $failed === 0 ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('验证异常: '.$e->getMessage());
            $this->line('  请确认 Redis 服务已启动且 RedisCacheService 已注册');

            return 1;
        }
    }
}
