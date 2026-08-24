<?php

/*
|--------------------------------------------------------------------------
| 缓存配置文件 (config/cache.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件用于集中管理 Laravel 框架中所有缓存相关的配置。
|
| 管理的配置范围：
|   1. default          —— 默认使用的缓存存储（cache store）
|   2. stores           —— 应用可用的全部缓存存储及其驱动配置
|   3. prefix            —— 缓存键（key）的全局前缀，避免多应用共享缓存时冲突
|   4. serializable_classes —— 允许从缓存反序列化的 PHP 类白名单（安全相关）
|
| 相关 Laravel 官方文档：https://laravel.com/docs/13.x/cache
|
| 主要配置项概览：
|   - 默认缓存驱动：database（数据库缓存，可由 CACHE_STORE 环境变量覆盖）
|   - 支持的驱动：array、database、file、redis、memcached、dynamodb、
|                 storage、octane、failover、null
|   - 缓存前缀：基于 APP_NAME 自动生成（如 laravel-cache-）
|   - 默认禁止反序列化 PHP 类，防止 APP_KEY 泄漏后遭到反序列化攻击
|
*/

use Illuminate\Support\Str;

return [

    // ===== 区块：default（默认缓存存储）=====
    // 作用：定义应用未显式指定缓存存储时默认使用的那个存储。
    // 推荐生产环境使用 redis 或 memcached；开发环境常用 database/file/array。

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    // 默认缓存存储名称；通过 CACHE_STORE 环境变量指定，未设置时回退为 'database'
    'default' => env('CACHE_STORE', 'database'),

    // ===== 区块：stores（缓存存储定义集合）=====
    // 作用：声明应用所有可用的缓存存储及其驱动配置。
    // 可为同一驱动定义多个 store，按用途分组管理（例如分页缓存、会话缓存等）。
    // 支持的驱动：array、database、file、memcached、redis、dynamodb、
    //            storage、octane、session、failover、null

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        // ----- array：内存数组缓存 -----
        // 仅在当前请求生命周期内有效，请求结束即清空，适合单元测试。
        'array' => [
            'driver' => 'array',              // 驱动类型：内存数组
            'serialize' => false,             // 是否对值进行序列化；false 表示直接保留原值，避免不必要的性能开销
        ],

        // ----- database：数据库缓存 -----
        // 将缓存数据存储到指定的数据库表中，无需额外服务，部署简单。
        'database' => [
            'driver' => 'database',                                   // 驱动类型：数据库
            'connection' => env('DB_CACHE_CONNECTION'),                // 使用的数据库连接名；为空时使用默认数据库连接
            'table' => env('DB_CACHE_TABLE', 'cache'),                 // 存放缓存数据的表名，默认 'cache'
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),      // 原子锁所用的数据库连接；为空时复用主连接
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),                // 原子锁所用的表名；为空时使用默认锁表
        ],

        // ----- file：文件系统缓存 -----
        // 将缓存写入本地文件，无需额外服务，适合单机部署。
        'file' => [
            'driver' => 'file',                                                // 驱动类型：文件系统
            'path' => storage_path('framework/cache/data'),                    // 缓存文件存放目录（绝对路径）
            'lock_path' => storage_path('framework/cache/data'),               // 原子锁文件存放目录；默认与缓存目录相同
        ],

        // ----- storage：基于文件系统磁盘（Filesystem Disk）的缓存 -----
        // 通过 Laravel 文件系统（如 s3、local）存放缓存，可对接云存储。
        'storage' => [
            'driver' => 'storage',                                // 驱动类型：Filesystem Disk
            'disk' => env('CACHE_STORAGE_DISK'),                  // 使用的磁盘名称（在 config/filesystems.php 中定义）；为空时使用默认磁盘
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'), // 在磁盘上的相对路径，默认 'framework/cache/data'
        ],

        // ----- memcached：Memcached 缓存 -----
        // 高性能分布式内存缓存，适合多机共享缓存的场景。
        'memcached' => [
            'driver' => 'memcached',                              // 驱动类型：Memcached
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),    // 持久化连接 ID；为空时使用短连接
            'sasl' => [                                           // SASL 认证信息（用户名/密码）；服务端未启用 SASL 时可留空
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [                                        // 传递给 Memcached 客户端的额外选项（键值对）
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,         // 示例：连接超时 2000 毫秒（默认注释，按需启用）
            ],
            'servers' => [                                        // Memcached 服务器列表，可配置多台实现分布式
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'), // 主机地址；默认本机
                    'port' => env('MEMCACHED_PORT', 11211),       // 端口号；默认 11211
                    'weight' => 100,                              // 节点权重（用于一致性哈希分配），默认 100
                ],
            ],
        ],

        // ----- redis：Redis 缓存 -----
        // 高性能内存缓存，支持持久化、丰富数据结构，是生产环境推荐方案。
        'redis' => [
            'driver' => 'redis',                                          // 驱动类型：Redis
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),       // 使用的 Redis 连接名（在 config/database.php 的 redis 区块中定义），默认 'cache'
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'), // 原子锁所用的 Redis 连接，默认 'default'
        ],

        // ----- dynamodb：AWS DynamoDB 缓存 -----
        // 基于 AWS DynamoDB 的云端缓存，适合 AWS 云架构项目。
        'dynamodb' => [
            'driver' => 'dynamodb',                                // 驱动类型：AWS DynamoDB
            'key' => env('AWS_ACCESS_KEY_ID'),                     // AWS 访问密钥 ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'),              // AWS 访问密钥 Secret
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),    // AWS 区域；默认 us-east-1
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),       // 存放缓存的 DynamoDB 表名，默认 'cache'
            'endpoint' => env('DYNAMODB_ENDPOINT'),                // 自定义端点（用于本地模拟或兼容服务如 LocalStack）；为空时使用 AWS 官方
        ],

        // ----- octane：Laravel Octane 内存缓存 -----
        // 仅在 Octane 长驻进程内有效，跨进程不共享，性能极高但容量有限。
        'octane' => [
            'driver' => 'octane',                                  // 驱动类型：Octane
        ],

        // ----- failover：故障转移缓存 -----
        // 按顺序尝试多个 store，第一个可用时即使用，提高缓存可用性。
        'failover' => [
            'driver' => 'failover',                                // 驱动类型：failover
            'stores' => [                                          // 故障转移顺序列表，按优先级从高到低
                'database',                                        // 优先使用数据库缓存
                'array',                                           // 数据库不可用时回退到内存数组（仅当前请求有效）
            ],
        ],

    ],

    // ===== 区块：prefix（缓存键前缀）=====
    // 作用：为 APC、database、memcached、Redis、DynamoDB 等共享型缓存设置统一前缀，
    //      防止多应用共用同一缓存后端时出现键名冲突。
    // 注意：file、array 等独立型存储通常无需此设置。

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    // 缓存键前缀；优先取 CACHE_PREFIX，未设置则由 APP_NAME 转为 slug 后追加 '-cache-' 后缀（如 laravel-cache-）
    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    // ===== 区块：serializable_classes（可反序列化的类白名单）=====
    // 作用：限定可从缓存中反序列化的 PHP 类范围，防止 APP_KEY 泄漏后遭受 PHP 反序列化 gadget 链攻击。
    // 取值：
    //   false        —— 完全禁止反序列化任何类（默认，最安全）
    //   true         —— 允许反序列化任意类（不推荐，仅限受信任环境）
    //   [ClassName]  —— 仅允许反序列化数组中列出的类

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This value determines the classes that can be unserialized from cache
    | storage. By default, no PHP classes will be unserialized from your
    | cache to prevent gadget chain attacks if your APP_KEY is leaked.
    |
    */

    // 默认 false：禁止从缓存反序列化任何 PHP 类，最大限度防止反序列化攻击
    'serializable_classes' => false,

];
