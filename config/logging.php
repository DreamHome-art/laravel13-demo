<?php

/*
|--------------------------------------------------------------------------
| 日志配置文件 (logging.php)
|--------------------------------------------------------------------------
|
| 文件用途：定义 Laravel 应用程序日志记录相关的全部配置。
| 管理的配置范围：
|   - default      : 默认日志通道
|   - deprecations : 废弃特性警告日志通道
|   - channels     : 日志通道集合（stack / single / daily / slack /
|                    papertrail / stderr / syslog / errorlog / sql 等）
|
| 主要配置项概览：
|   'default'      => 'stack'      默认日志通道，使用 stack 聚合多个通道
|   'deprecations' => [...]         废弃特性日志的通道与 trace 控制
|   'channels'     => [...]         各日志通道的具体配置
|
| 本项目自定义说明：
|   - 'sql' 通道：独立按天存储 SQL 查询日志，保留 15 天，便于 SQL 调试分析
|   - 'tap'  配置：注入 \App\Logging\CustomizeFormatter，为每条日志附加 trace_id，
|     实现 SQL 日志与业务日志的全链路关联检索
|
| 相关 Laravel 官方文档：
|   https://laravel.com/docs/13.x/logging
|
*/

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    // ===== 区块：default 默认日志通道 =====
    // 框架默认使用的日志通道名。当调用 Log::info()、Log::error() 等方法
    // 但未显式指定通道时，将使用此处配置的通道。
    // 可选值：channels 配置块中定义的任意通道名（如 stack / single / daily 等）。
    // 默认值：stack（聚合通道，可同时写入多个子通道）。
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'), // 默认通道名，可通过环境变量 LOG_CHANNEL 覆盖，默认 stack

    // ===== 区块：deprecations 废弃特性日志通道 =====
    // 控制用于记录 PHP 与依赖库废弃特性警告的日志通道。
    // 提前感知未来主版本升级风险，便于应用平滑迁移。
    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'), // 废弃特性记录到哪个通道（默认 null=丢弃；可设为 deprecations / single / daily 等）
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),      // 是否记录触发废弃警告的调用栈，便于定位代码位置
    ],

    // ===== 区块：channels 日志通道集合 =====
    // 这里配置应用可用的全部日志通道。Laravel 基于 Monolog 库实现，
    // 支持多种强大的 handler 与 formatter。
    // 可用驱动："single", "daily", "monthly", "slack", "syslog",
    //          "errorlog", "monolog", "custom", "stack"
    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "monthly", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */
    'channels' => [

        // ----- stack：聚合通道 -----
        // 将日志同时写入多个子通道，由 channels 列表指定。便于多目的地同时记录。
        'stack' => [
            'driver' => 'stack',                                              // 驱动类型：聚合
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),  // 子通道列表，逗号分隔，默认 single
            'ignore_exceptions' => false,                                      // 子通道发生异常时是否忽略（false=抛出，便于发现配置问题）
        ],

        // ----- single：单文件日志通道 -----
        // 所有日志写入同一个文件，文件会持续增长，适合开发环境或小规模应用。
        'single' => [
            'driver' => 'single',                               // 驱动类型：单文件
            'path' => storage_path('logs/laravel.log'),         // 日志文件路径：storage/logs/laravel.log
            'level' => env('LOG_LEVEL', 'debug'),               // 记录的最低日志级别（debug/info/notice/warning/error/critical/alert/emergency）
            'replace_placeholders' => true,                     // 是否替换 PSR-3 占位符（如 {foo}）
            // 注入 trace_id Processor，使每条日志携带 trace_id
            'tap' => [\App\Logging\CustomizeFormatter::class],   // 自定义 tap 类，用于在通道构建时注入 Processor（trace_id 链路追踪）
        ],

        // ----- daily：按天分文件日志通道 -----
        // 每天一个日志文件，按 max_files 自动清理旧文件，适合生产环境。
        'daily' => [
            'driver' => 'daily',                                // 驱动类型：按天
            'path' => storage_path('logs/laravel.log'),          // 日志文件路径（实际文件名会附加日期，如 laravel-2024-01-01.log）
            'level' => env('LOG_LEVEL', 'debug'),                // 记录的最低日志级别
            'max_files' => env('LOG_DAILY_DAYS', 14),             // 保留的最大日志文件数量（默认 14 天），超过自动删除最旧
            'replace_placeholders' => true,                      // 是否替换 PSR-3 占位符
            // 注入 trace_id Processor，使每条日志携带 trace_id
            'tap' => [\App\Logging\CustomizeFormatter::class],    // 自定义 tap 类，注入 trace_id Processor（链路追踪）
        ],

        // ----- sql：SQL 查询日志通道（本项目自定义） -----
        // 独立按天存储 SQL 查询日志，保留 15 天，便于 SQL 性能分析与调试。
        // 通过 tap 注入 trace_id，使 SQL 日志与业务日志全链路关联。
        // SQL 查询日志通道：独立按天存储，保留 15 天
        'sql' => [
            'driver' => 'daily',                                 // 驱动类型：按天（复用 daily 驱动，独立文件）
            'path' => storage_path('logs/sql.log'),              // SQL 日志文件路径（实际文件名会附加日期）
            'level' => env('LOG_LEVEL', 'debug'),                // 记录的最低日志级别
            'max_files' => 15,                                    // 固定保留 15 天 SQL 日志文件
            'replace_placeholders' => true,                      // 是否替换 PSR-3 占位符
            // 注入 trace_id Processor，使 SQL 日志也携带 trace_id，便于全链路检索
            'tap' => [\App\Logging\CustomizeFormatter::class],    // 自定义 tap 类，注入 trace_id Processor（链路追踪）
        ],

        // ----- monthly：按月分文件日志通道 -----
        // 每月一个日志文件，适合长期保留少量日志。
        'monthly' => [
            'driver' => 'monthly',                               // 驱动类型：按月
            'path' => storage_path('logs/laravel.log'),          // 日志文件路径（实际文件名会附加年月，如 laravel-2024-01.log）
            'level' => env('LOG_LEVEL', 'debug'),                // 记录的最低日志级别
            'max_files' => 3,                                     // 保留的最大月数（默认 3 个月）
            'replace_placeholders' => true,                      // 是否替换 PSR-3 占位符
        ],

        // ----- slack：Slack 通知通道 -----
        // 将日志通过 webhook 推送到 Slack 频道，常用于告警通知。
        'slack' => [
            'driver' => 'slack',                                 // 驱动类型：Slack
            'url' => env('LOG_SLACK_WEBHOOK_URL'),               // Slack Webhook URL（在 Slack 应用配置后获取）
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')), // 显示的发送者名称，默认取 APP_NAME
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),         // 消息前缀 emoji
            'level' => env('LOG_LEVEL', 'critical'),             // 推送的最低日志级别（默认 critical=严重错误才推送）
            'replace_placeholders' => true,                       // 是否替换 PSR-3 占位符
        ],

        // ----- papertrail：Papertrail 远程日志服务通道 -----
        // 通过 Syslog UDP 协议将日志发送到 Papertrail 云服务，便于集中管理。
        // 默认使用 TLS 加密传输，保证日志安全。
        'papertrail' => [
            'driver' => 'monolog',                                          // 驱动类型：monolog（自定义 handler）
            'level' => env('LOG_LEVEL', 'debug'),                           // 记录的最低日志级别
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class), // Monolog handler 类，默认 SyslogUdpHandler
            'handler_with' => [                                             // handler 构造参数
                'host' => env('PAPERTRAIL_URL'),                             // Papertrail 服务地址
                'port' => env('PAPERTRAIL_PORT'),                            // Papertrail 服务端口
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'), // TLS 连接字符串（加密传输）
            ],
            'processors' => [PsrLogMessageProcessor::class],               // PSR-3 占位符替换 Processor
        ],

        // ----- stderr：标准错误输出通道 -----
        // 日志输出到 stderr，常用于 Docker/容器化部署，便于日志采集（如 Fluentd/Filebeat）。
        'stderr' => [
            'driver' => 'monolog',                                          // 驱动类型：monolog（自定义 handler）
            'level' => env('LOG_LEVEL', 'debug'),                           // 记录的最低日志级别
            'handler' => StreamHandler::class,                              // Monolog handler 类：流写入
            'handler_with' => [                                             // handler 构造参数
                'stream' => 'php://stderr',                                 // 写入目标：PHP 标准错误输出
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),                     // 自定义日志格式化器（可为空，使用默认格式）
            'processors' => [PsrLogMessageProcessor::class],               // PSR-3 占位符替换 Processor
        ],

        // ----- syslog：系统日志通道 -----
        // 通过 syslog 守护进程记录日志，便于系统集成（Linux 常用）。
        'syslog' => [
            'driver' => 'syslog',                                          // 驱动类型：syslog
            'level' => env('LOG_LEVEL', 'debug'),                           // 记录的最低日志级别
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),            // syslog facility（如 LOG_USER / LOG_LOCAL0 等）
            'replace_placeholders' => true,                                 // 是否替换 PSR-3 占位符
        ],

        // ----- errorlog：PHP error_log 通道 -----
        // 通过 PHP 的 error_log 机制输出（写入 SAPI 错误日志或 Apache/Nginx 错误日志）。
        'errorlog' => [
            'driver' => 'errorlog',                                        // 驱动类型：errorlog
            'level' => env('LOG_LEVEL', 'debug'),                          // 记录的最低日志级别
            'replace_placeholders' => true,                                 // 是否替换 PSR-3 占位符
        ],

        // ----- null：空通道 -----
        // 丢弃所有日志，常用于开发环境或临时屏蔽某类日志（如 deprecations 默认指向此通道）。
        'null' => [
            'driver' => 'monolog',                                         // 驱动类型：monolog
            'handler' => NullHandler::class,                              // Monolog handler 类：丢弃所有日志
        ],

        // ----- emergency：应急日志通道（兜底配置） -----
        // 当配置的默认通道不可用（如配置错误、磁盘满）时，Laravel 兜底写入此通道。
        // 该通道配置极简，仅指定文件路径，确保日志不丢失。
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),                  // 应急日志文件路径
        ],

    ],

];
