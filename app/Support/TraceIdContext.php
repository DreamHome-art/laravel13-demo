<?php

namespace App\Support;

/**
 * TraceId 全局上下文存储
 *
 * 用于在请求生命周期内保存当前请求的 trace_id，
 * 供 Monolog Processor / SQL 日志记录器等无法直接访问 Request 的组件读取。
 *
 * 说明：使用静态属性存储，仅在单次请求生命周期内有效；
 * 在 PHP-FPM / Frankenphp 等每请求重置进程的运行模式下安全；
 * 在常驻进程模式（如 Octane / RoadRunner）下，应在请求结束时调用 reset() 清理。
 */
class TraceIdContext
{
    /**
     * 当前请求的 trace_id
     */
    private static ?string $traceId = null;

    /**
     * 设置当前请求的 trace_id
     *
     * @param  string  $traceId  全局唯一请求标识
     * @return void
     */
    public static function set(string $traceId): void
    {
        self::$traceId = $traceId;
    }

    /**
     * 获取当前请求的 trace_id
     * 若未设置则返回 null（Processor 会兜底为 'no-trace'）
     *
     * @return string|null
     */
    public static function get(): ?string
    {
        return self::$traceId;
    }

    /**
     * 重置 trace_id（常驻进程模式下，请求结束时调用以避免上下文污染）
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$traceId = null;
    }
}
