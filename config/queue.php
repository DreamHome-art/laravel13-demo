<?php

/*
|--------------------------------------------------------------------------
| 队列配置文件 (config/queue.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件集中管理 Laravel 队列系统的连接、驱动及运行参数，包括默认
| 队列连接、各队列后端（database/redis/sqs/beanstalkd 等）的连接参数、任务
| 批处理存储位置以及失败任务的记录方式。
|
| 管理的配置范围：
|   - default     默认队列连接名（派发任务时未显式指定连接则使用）
|   - connections 各队列后端连接配置（sync/database/beanstalkd/sqs/redis/
|                 deferred/background/failover/null）
|   - batching    任务批处理（Job Batching）使用的数据库与表
|   - failed      失败任务的存储驱动与表/文件位置
|
| 主要配置项概览：
|   - QUEUE_CONNECTION、DB_QUEUE_*、REDIS_QUEUE_*、SQS_*、BEANSTALKD_QUEUE_* 等
|
| 相关 Laravel 官方文档：https://laravel.com/docs/13.x/queues
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    // ===== 区块：default 默认队列连接 =====
    // 派发任务（dispatch）时未显式指定连接所使用的默认队列连接名。可选值对应下方
    // connections 数组中的任意键名，例如 sync / database / beanstalkd / sqs /
    // redis / deferred / background / failover。默认 'database'：使用数据库表
    // 存储任务，无需额外依赖、适合中小型项目与开发环境。
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    // ===== 区块：connections 队列连接配置集合 =====
    // 在此定义应用可用的所有队列后端连接及其参数。每个连接对应一个键名，可通过
    // onConnection('键名') 显式指定使用。支持的驱动（driver）类型：sync / database /
    // beanstalkd / sqs / redis / deferred / background / failover / null。可按需新增。
    'connections' => [

        // sync 同步连接：任务在派发时立即同步执行（无后台处理），适合本地开发与测试。
        'sync' => [
            'driver' => 'sync', // 驱动名，固定为 'sync'
        ],

        // database 数据库连接：将任务存入数据库表，由 queue:work 拉取执行，部署简单。
        'database' => [
            'driver' => 'database', // 驱动名，固定为 'database'
            'connection' => env('DB_QUEUE_CONNECTION'), // 使用的数据库连接名（对应 config/database.php）；留空使用默认连接
            'table' => env('DB_QUEUE_TABLE', 'jobs'), // 存储任务的数据库表名，默认 'jobs'
            'queue' => env('DB_QUEUE', 'default'), // 默认队列名称，可在监听时指定不同队列
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90), // 任务被取走后多少秒仍未完成则允许其他 worker 重试（秒）
            'after_commit' => false, // 是否在数据库事务提交后才派发任务；true 可避免事务回滚但任务已入队的不一致
        ],

        // beanstalkd 连接：基于 Beanstalkd 队列服务，轻量高性能。
        'beanstalkd' => [
            'driver' => 'beanstalkd', // 驱动名，固定为 'beanstalkd'
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'), // Beanstalkd 服务主机地址
            'queue' => env('BEANSTALKD_QUEUE', 'default'), // 默认管道（tube）名称
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90), // 任务被保留后多少秒允许重试（秒）
            'block_for' => 0, // 长轮询阻塞等待秒数；0 表示不阻塞，立即返回（适合有新任务时再处理）
            'after_commit' => false, // 是否在事务提交后派发任务
        ],

        // sqs 连接：使用 AWS SQS（Simple Queue Service）作为队列后端。
        'sqs' => [
            'driver' => 'sqs', // 驱动名，固定为 'sqs'
            'key' => env('AWS_ACCESS_KEY_ID'), // AWS 访问密钥 ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'), // AWS 访问密钥密文
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'), // 队列 URL 前缀（含账户 ID 与区域）
            'queue' => env('SQS_QUEUE', 'default'), // 默认队列名称
            'suffix' => env('SQS_SUFFIX'), // 可选：队列 URL 后缀，用于 FIFO 队列等场景
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), // AWS 区域
            'after_commit' => false, // 是否在事务提交后派发任务
        ],

        // redis 连接：使用 Redis 作为队列后端，性能高、部署相对简单。
        'redis' => [
            'driver' => 'redis', // 驱动名，固定为 'redis'
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'), // 使用的 Redis 连接名（对应 config/database.php 的 database.redis）
            'queue' => env('REDIS_QUEUE', 'default'), // 默认队列名称
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90), // 任务超时后多少秒允许重试（秒）
            'block_for' => null, // 长轮询阻塞等待秒数；null 表示不阻塞，设为正整数可降低空闲 Redis 查询
            'after_commit' => false, // 是否在事务提交后派发任务
        ],

        // deferred 连接：将任务延迟到当前请求结束后执行（同进程内延迟派发）。
        'deferred' => [
            'driver' => 'deferred', // 驱动名，固定为 'deferred'
        ],

        // background 连接：在后台进程/运行时中执行任务，无需外部 worker 守护进程。
        'background' => [
            'driver' => 'background', // 驱动名，固定为 'background'
        ],

        // failover 连接：按顺序尝试 connections 列表，主连接失败时降级到备用连接，提高可用性。
        'failover' => [
            'driver' => 'failover', // 驱动名，固定为 'failover'
            'connections' => [ // 依次尝试的连接列表，先 'database' 后 'deferred'
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    // ===== 区块：batching 任务批处理 =====
    // 任务批处理（Job Batching）允许将多个任务作为一组派发并跟踪整体进度。此区块
    // 配置批处理元数据存储的数据库连接与表名。表需通过相关迁移创建。
    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'), // 存储批处理记录的数据库连接名，默认 sqlite
        'table' => 'job_batches', // 批处理记录表名
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    // ===== 区块：failed 失败任务记录 =====
    // 配置失败任务的记录方式，便于后续重试或排查。支持的驱动：database-uuids（数据库，
    // 以 UUID 标识）、dynamodb（AWS DynamoDB）、file（本地文件）、null（不记录）。
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), // 失败任务存储驱动，默认 database-uuids
        'database' => env('DB_CONNECTION', 'sqlite'), // 当使用数据库驱动时存储失败任务的数据库连接名，默认 sqlite
        'table' => 'failed_jobs', // 当使用数据库驱动时存储失败任务的表名
    ],

];
