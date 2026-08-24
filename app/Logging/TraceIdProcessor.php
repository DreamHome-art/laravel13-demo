<?php

namespace App\Logging;

use App\Support\TraceIdContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * TraceId Monolog Processor
 *
 * 将当前请求的 trace_id 注入到每条日志记录的 context 字段，
 * 配合 Laravel 默认的 LineFormatter（SIMPLE_FORMAT 含 %context%），
 * trace_id 会以 JSON 形式显示在日志末尾，便于按 trace_id 检索全链路日志。
 *
 * 注入位置：record.context.trace_id
 * 兜底值：未设置 trace_id 时使用 'no-trace'（如命令行场景）
 *
 * 说明：LogRecord 的 context 属性是 readonly，
 * 这里通过 with() 方法返回包含新 context 的新 LogRecord 实例。
 */
class TraceIdProcessor implements ProcessorInterface
{
    /**
     * 处理日志记录，注入 trace_id 到 context
     *
     * @param  LogRecord  $record  Monolog 日志记录
     * @return LogRecord          处理后的日志记录（带 trace_id）
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // 读取当前请求的 trace_id，未设置时兜底为 'no-trace'
        $traceId = TraceIdContext::get() ?? 'no-trace';

        // LogRecord 的 context 是 readonly，通过 with() 命名参数返回新实例
        // trace_id 与原有 context 合并，避免覆盖业务上下文
        return $record->with(
            context: array_merge($record->context, ['trace_id' => $traceId])
        );
    }
}
