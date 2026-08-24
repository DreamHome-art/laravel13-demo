<?php

namespace App\Providers;

use App\Services\RedisCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 注册 Redis 缓存服务为单例：全应用共享同一 Redis 连接与配置实例
        // 使用方式：依赖注入（构造函数参数类型提示 RedisCacheService）
        //           或服务容器（app(RedisCacheService::class)）
        $this->app->singleton(RedisCacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 解决旧版 MySQL/MariaDB（utf8mb4 字符集）下索引键长度超过 1000 字节的报错
        // 官方推荐：将默认字符串长度限制为 191
        Schema::defaultStringLength(191);

        // 监听数据库查询事件，记录 SQL 查询日志到独立的 sql 通道
        // 日志按天存储，保留 15 天，位于 storage/logs/sql-YYYY-MM-DD.log
        DB::listen(function ($query) {
            // 将 SQL 语句中的 ? 占位符替换为实际绑定参数值，便于阅读
            $sql = $this->formatSql($query->sql, $query->bindings);

            // 拼接日志内容：连接名 + SQL + 执行时间
            $logMessage = sprintf(
                '[%s] %s | %.2f ms',
                $query->connectionName,
                $sql,
                $query->time
            );

            // 写入独立的 sql 日志通道
            Log::channel('sql')->info($logMessage);
        });
    }

    /**
     * 将 SQL 语句中的 ? 占位符替换为实际的绑定参数值
     *
     * @param  string  $sql       原始 SQL 语句（含 ? 占位符）
     * @param  array   $bindings  绑定参数数组
     * @return string             替换占位符后的可读 SQL 语句
     */
    protected function formatSql(string $sql, array $bindings): string
    {
        // 若无绑定参数，直接返回原 SQL
        if (empty($bindings)) {
            return $sql;
        }

        // 遍历绑定参数，逐个替换 SQL 中的 ? 占位符
        foreach ($bindings as $binding) {
            // 根据参数类型进行格式化处理
            if (is_numeric($binding)) {
                // 数值类型：保持原样
                $value = $binding;
            } elseif (is_null($binding)) {
                // NULL 值：替换为 NULL
                $value = 'NULL';
            } elseif (is_bool($binding)) {
                // 布尔值：转换为 1 或 0
                $value = $binding ? 1 : 0;
            } else {
                // 字符串及其他类型：用单引号包裹并转义内部单引号
                $value = "'" . addslashes((string) $binding) . "'";
            }

            // 替换第一个出现的 ? 占位符
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}
