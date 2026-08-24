<?php

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * 日志定制类（tap 类）
 *
 * 在 logging.php 中通过 'tap' 配置项引用此类，
 * 为对应通道的 logger 实例注入 TraceIdProcessor，
 * 使每条日志的 context 中自动携带 trace_id。
 *
 * 说明：
 * - Laravel 的 tap 回调接收的是 Illuminate\Log\Logger（包装类），
 *   需通过 getLogger() 获取底层 Monolog\Logger 实例再 pushProcessor。
 * - trace_id 通过 TraceIdProcessor 注入到 record.context，
 *   配合 Laravel 默认 LineFormatter（SIMPLE_FORMAT 含 %context%），
 *   trace_id 会以 JSON 形式显示在日志末尾，便于按 trace_id 检索。
 *
 * 日志格式示例：
 *   [2026-08-23 14:09:35] local.INFO: [mysql] select * from `users` limit 1 | 27.68 ms {"trace_id":"20260823-140935-a1b2c3d4"}
 */
class CustomizeFormatter
{
    /**
     * 应用日志定制：注入 trace_id Processor
     *
     * @param  Logger  $logger  Laravel 的 Logger 包装类
     * @return void
     */
    public function __invoke(Logger $logger): void
    {
        // 获取底层 Monolog Logger 实例，并注入 TraceIdProcessor
        // Processor 会在每条日志记录被处理前调用，将 trace_id 注入 context
        $logger->getLogger()->pushProcessor(new TraceIdProcessor());
    }
}
