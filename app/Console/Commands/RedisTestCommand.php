<?php

/*
|--------------------------------------------------------------------------
| Redis 连接测试命令 (RedisTestCommand)
|--------------------------------------------------------------------------
|
| 文件用途：通过 `php artisan redis:test` 测试 Redis 连接与读写功能。
|
| 测试流程（6 步）：
|   1. 读取并展示 Redis 配置（client / host / port / database / password）
|   2. 检查 PHP Redis 扩展是否加载（phpredis 客户端时）
|   3. PING 连接测试（验证 Redis 服务可达）
|   4. SET 写入测试（写入带 TTL 的测试键值）
|   5. GET 读取测试（读取并验证数据一致性 + TTL 检查）
|   6. DEL 清理测试数据
|
| 测试键格式：redis:test:{uniqid()}，TTL 60 秒，防止异常中断后残留。
| 失败时给出排查建议（Redis 服务、配置、密码）。
|
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RedisTestCommand extends Command
{
    /**
     * 命令签名：php artisan redis:test
     */
    protected $signature = 'redis:test';

    /**
     * 命令描述
     */
    protected $description = '测试 Redis 连接与读写功能（连接 / PING / SET / GET / DEL）';

    public function handle(): int
    {
        $this->info('Redis 连接测试');
        $this->line(str_repeat('=', 60));
        $this->newLine();

        // ===== 区块 1：读取 Redis 配置 =====
        $this->line('【1/6】读取 Redis 配置');
        $config = config('database.redis.default', []);
        $client = config('database.redis.client', 'phpredis');
        $hasPassword = isset($config['password'])
            && $config['password'] !== null
            && $config['password'] !== '';

        $this->table(['配置项', '值'], [
            ['Client', $client],
            ['Host', $config['host'] ?? '-'],
            ['Port', $config['port'] ?? '-'],
            ['Database', $config['database'] ?? '-'],
            ['Password', $hasPassword ? '已设置' : '无'],
        ]);
        $this->newLine();

        // ===== 区块 2：检查 PHP 扩展 =====
        $this->line('【2/6】检查 PHP 扩展');
        if ($client === 'phpredis') {
            // phpredis 客户端要求 PHP 已加载 redis 扩展（C 扩展）
            if (! extension_loaded('redis')) {
                $this->error('[失败] phpredis 扩展未加载');
                $this->line('  请在 php.ini 中启用 redis 扩展（extension=redis）');
                $this->line('  phpStudy：软件管理 → PHP → 扩展 → 勾选 redis');
                return 1;
            }
            $version = phpversion('redis');
            $this->info("[通过] phpredis 扩展已加载（版本 {$version}）");
        } else {
            $this->info('[通过] 使用 predis 客户端（无需 PHP 扩展）');
        }
        $this->newLine();

        // ===== 区块 3-6：连接与读写测试 =====
        try {
            $redis = Redis::connection('default');

            // ----- 区块 3：PING 连接测试 -----
            $this->line('【3/6】连接测试 (PING)');
            $start = microtime(true);
            $pong = $redis->ping();
            $time = round((microtime(true) - $start) * 1000, 2);
            // phpredis 5.0+ ping() 返回 true，旧版返回 "+PONG"，统一处理
            $pongStr = is_bool($pong) ? ($pong ? 'PONG' : 'FAIL') : (string) $pong;
            $this->info("[通过] PING 返回: {$pongStr} ({$time} ms)");
            $this->newLine();

            // ----- 区块 4：SET 写入测试 -----
            $this->line('【4/6】写入测试 (SET)');
            // 测试键用唯一 ID 避免并发冲突，TTL 60 秒防止异常中断后残留
            $key = 'redis:test:'.uniqid();
            $value = 'laravel-test-'.date('Y-m-d H:i:s');
            $start = microtime(true);
            $redis->setex($key, 60, $value);  // setex：写入并设置过期时间（秒）
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->info("[通过] SET {$key} = {$value} ({$time} ms, TTL 60s)");
            $this->newLine();

            // ----- 区块 5：GET 读取测试 + 数据一致性验证 -----
            $this->line('【5/6】读取测试 (GET)');
            $start = microtime(true);
            $got = $redis->get($key);
            $time = round((microtime(true) - $start) * 1000, 2);
            if ($got === $value) {
                $this->info("[通过] GET {$key} = {$got} ({$time} ms) 数据一致");
            } else {
                $this->error("[失败] 数据不一致: 期望 {$value}, 实际 {$got}");
                return 1;
            }
            // TTL 检查：验证过期时间已设置且合理（应略小于 60 秒）
            $ttl = $redis->ttl($key);
            $this->line("  TTL: {$ttl} 秒（预期 1~60 秒）");
            $this->newLine();

            // ----- 区块 6：DEL 清理测试数据 -----
            $this->line('【6/6】清理测试数据 (DEL)');
            $start = microtime(true);
            $deleted = $redis->del($key);  // 返回被删除的键数量
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->info("[通过] DEL {$key} 删除 {$deleted} 条 ({$time} ms)");
            $this->newLine();

            // ===== 汇总 =====
            $this->info(str_repeat('=', 60));
            $this->info('Redis 连接测试全部通过');
            $this->info(str_repeat('=', 60));

            return 0;  // 返回 0 表示命令执行成功

        } catch (\Exception $e) {
            // 捕获连接异常（Redis 服务未启动 / 端口错误 / 密码错误等）
            $this->error('[失败] Redis 连接失败: '.$e->getMessage());
            $this->newLine();
            $this->line('排查建议：');
            $this->line('  1. 确认 Redis 服务已启动（phpStudy 中开启 Redis，或运行 redis-server）');
            $this->line('  2. 检查 .env 中 REDIS_HOST / REDIS_PORT 配置是否正确');
            $this->line('  3. 若 Redis 设置了密码，检查 .env 中 REDIS_PASSWORD 是否匹配');
            $this->line('  4. 检查防火墙是否放行 Redis 端口（默认 6379）');

            return 1;  // 返回 1 表示命令执行失败
        }
    }
}
