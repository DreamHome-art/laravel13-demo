<?php

/*
|--------------------------------------------------------------------------
| 第三方服务凭据配置文件 (config/services.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件集中存放应用接入的各类第三方服务（如邮件服务、消息通知服务等）
| 的凭据与基础参数，是 Laravel 约定的服务凭据统一存放位置，便于各扩展包按惯例读取。
|
| 管理的配置范围：
|   - postmark  Postmark 邮件服务（API Key）
|   - resend    Resend 邮件服务（API Key）
|   - ses       AWS SES 邮件服务（访问密钥与区域）
|   - slack     Slack 通知机器人（Bot OAuth Token 与默认频道）
|
| 主要配置项概览：
|   - POSTMARK_API_KEY、RESEND_API_KEY、AWS_ACCESS_KEY_ID、AWS_SECRET_ACCESS_KEY、
|     AWS_DEFAULT_REGION、SLACK_BOT_USER_OAUTH_TOKEN、SLACK_BOT_USER_DEFAULT_CHANNEL 等
|
| 注意：凭据均通过 env() 从 .env 读取，请勿在 .env 中提交真实凭据到版本库。
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // ===== 区块：postmark Postmark 邮件服务凭据 =====
    // Postmark 是事务性邮件服务。这里的 API Key 供 config/mail.php 中 transport 为
    // 'postmark' 的邮件器使用。可在 Postmark 控制台的 Server Credentials 获取。
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'), // Postmark 服务器 API Token
    ],

    // ===== 区块：resend Resend 邮件服务凭据 =====
    // Resend 是面向开发者的邮件服务。这里的 API Key 供 config/mail.php 中 transport
    // 为 'resend' 的邮件器使用。可在 Resend 控制台 API Keys 页面创建。
    'resend' => [
        'key' => env('RESEND_API_KEY'), // Resend API 密钥
    ],

    // ===== 区块：ses AWS SES 邮件服务凭据 =====
    // Amazon SES（Simple Email Service）凭据，供 config/mail.php 中 transport 为 'ses'
    // 的邮件器使用，亦用于 queue.php 中 sqs 连接的 AWS 凭据。密钥需在 AWS IAM 创建。
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'), // AWS 访问密钥 ID
        'secret' => env('AWS_SECRET_ACCESS_KEY'), // AWS 访问密钥密文
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), // AWS 区域，默认美东 1（us-east-1）
    ],

    // ===== 区块：slack Slack 通知机器人凭据 =====
    // 供 Laravel 的 Slack 通知渠道（SlackChannel / Notification::route）使用，发送
    // 消息到 Slack 频道。Bot User OAuth Token 以 'xoxb-' 开头，需在 Slack App 配置
    // OAuth & Permissions 并授予 chat:write 等权限后获取。
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), // Slack Bot User OAuth Token（xoxb- 开头）
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'), // 默认发送频道（如 #general 或频道 ID）
        ],
    ],

];
