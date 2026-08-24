<?php

/*
|--------------------------------------------------------------------------
| 邮件配置文件 (config/mail.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件集中管理 Laravel 应用发送邮件所需的所有配置，包括默认
| 邮件器、各邮件传输驱动（transport）的连接参数，以及全局发件人地址。
|
| 管理的配置范围：
|   - default   默认邮件器（发送邮件时未显式指定邮件器时使用）
|   - mailers   各类邮件器及其传输驱动配置（smtp / ses / postmark / resend /
|               sendmail / log / array / failover / roundrobin 等）
|   - from      全局默认发件人地址与名称
|
| 主要配置项概览：
|   - MAIL_MAILER、MAIL_HOST、MAIL_PORT、MAIL_USERNAME、MAIL_PASSWORD
|   - MAIL_FROM_ADDRESS、MAIL_FROM_NAME、MAIL_LOG_CHANNEL 等环境变量
|
| 相关 Laravel 官方文档：https://laravel.com/docs/13.x/mail
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    // ===== 区块：default 默认邮件器 =====
    // 控制应用发送邮件时默认使用的邮件器；当调用 Mail::send() 等方法未显式指定
    // 邮件器时，将使用此处配置的邮件器。可选值对应下方 mailers 数组中定义的任意
    // 键名，例如 smtp / ses / postmark / resend / sendmail / log / array /
    // failover / roundrobin。默认 'log'：邮件不实际发出，而是写入日志通道，
    // 便于本地开发与调试时观察邮件内容而不必配置真实 SMTP 服务器。
    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    // ===== 区块：mailers 邮件器配置集合 =====
    // 在此定义应用可用的所有邮件器及其传输驱动（transport）参数。每个邮件器对应
    // 一个键名，可通过 Mailer::mailer('键名') 显式指定使用。支持的 transport 类型：
    // smtp / sendmail / mailgun / ses / ses-v2 / postmark / resend / log / array /
    // failover / roundrobin。可根据需要新增自定义邮件器。
    'mailers' => [

        // SMTP 邮件器：通过标准 SMTP 协议发送邮件，最常用的生产环境方案。
        'smtp' => [
            'transport' => 'smtp', // 传输驱动，固定为 'smtp'
            'scheme' => env('MAIL_SCHEME'), // 协议方案：'smtp' 或 'smtps'（SMTPS/SSL）；留空则根据端口自动判断
            'url' => env('MAIL_URL'), // 可选：完整 SMTP 连接 URL（如 smtps://user:pass@host:port），设置后优先于 host/port/scheme
            'host' => env('MAIL_HOST', '127.0.0.1'), // SMTP 服务器主机地址，默认本地 127.0.0.1（如 mailtrap/邮件服务商标识）
            'port' => env('MAIL_PORT', 2525), // SMTP 服务器端口；常见：25（明文）、465（SSL）、587（STARTTLS）、2525（Mailtrap 等）
            'username' => env('MAIL_USERNAME'), // SMTP 认证用户名；留空表示不使用认证
            'password' => env('MAIL_PASSWORD'), // SMTP 认证密码；留空表示不使用认证
            'timeout' => null, // 连接/读取超时秒数；null 表示使用 Symfony Mailer 默认值
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)), // EHLO 命令发送的主机域名，默认取 APP_URL 的 host
        ],

        // AWS SES（Simple Email Service）邮件器：通过 AWS SDK 发送，凭据在 config/services.php 的 ses 块配置。
        'ses' => [
            'transport' => 'ses', // 传输驱动，固定为 'ses'
        ],

        // Postmark 邮件器：使用 Postmark 邮件服务发送，API Key 在 config/services.php 的 postmark 块配置。
        'postmark' => [
            'transport' => 'postmark', // 传输驱动，固定为 'postmark'
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'), // 可选：Postmark 消息流 ID，用于区分不同消息流（如 transactional/broadcast）
            // 'client' => [ // 可选：自定义 HTTP 客户端参数
            //     'timeout' => 5, // HTTP 请求超时秒数
            // ],
        ],

        // Resend 邮件器：使用 Resend 邮件服务发送，API Key 在 config/services.php 的 resend 块配置。
        'resend' => [
            'transport' => 'resend', // 传输驱动，固定为 'resend'
        ],

        // Sendmail 邮件器：调用服务器本地 sendmail 程序投递，适用于具备 MTA 的服务器环境。
        'sendmail' => [
            'transport' => 'sendmail', // 传输驱动，固定为 'sendmail'
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'), // sendmail 可执行路径及参数；-bs 使用 SMTP 模式，-i 忽略单点结束输入
        ],

        // Log 邮件器：不实际发送邮件，而是将邮件内容写入指定日志通道，便于本地开发调试。
        'log' => [
            'transport' => 'log', // 传输驱动，固定为 'log'
            'channel' => env('MAIL_LOG_CHANNEL'), // 写入的日志通道名；留空则使用默认日志通道
        ],

        // Array 邮件器：将邮件存入内存数组（不发送也不记录），常用于单元测试断言邮件内容。
        'array' => [
            'transport' => 'array', // 传输驱动，固定为 'array'
        ],

        // 故障转移邮件器：按顺序尝试 mailers 列表，前一个失败时切到下一个，提高发送可用性。
        'failover' => [
            'transport' => 'failover', // 传输驱动，固定为 'failover'
            'mailers' => [ // 依次尝试的邮件器列表，先 'smtp' 后 'log'
                'smtp',
                'log',
            ],
            'retry_after' => 60, // 主邮件器失败后多少秒内不再重试，直接降级到备用邮件器（秒）
        ],

        // 轮询邮件器：在多个邮件器间轮询发送，分散负载；某个失败时自动切换到下一个。
        'roundrobin' => [
            'transport' => 'roundrobin', // 传输驱动，固定为 'roundrobin'
            'mailers' => [ // 轮询使用的邮件器列表，如 'ses' 与 'postmark' 交替
                'ses',
                'postmark',
            ],
            'retry_after' => 60, // 某邮件器失败后多少秒内跳过它（秒）
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    // ===== 区块：from 全局发件人地址 =====
    // 应用发送的所有邮件默认采用此处的发件人地址与名称，避免在每个 Mailable 中
    // 重复指定。如某个邮件需使用不同的发件人，可在该 Mailable 中单独覆盖。
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), // 全局默认发件人邮箱地址
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')), // 全局默认发件人显示名称，默认取 APP_NAME
    ],

];
