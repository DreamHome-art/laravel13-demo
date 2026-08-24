<?php

/*
|--------------------------------------------------------------------------
| 数据库配置文件 (config/database.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件用于集中管理 Laravel 框架的数据库连接、迁移、Redis 等相关配置。
|
| 管理的配置范围：
|   1. default      —— 默认数据库连接名称
|   2. connections  —— 应用可用的全部数据库连接配置（sqlite、mysql、mariadb、pgsql、sqlsrv）
|   3. migrations  —— 迁移记录表的配置
|   4. redis        —— Redis 连接配置（含默认连接和缓存专用连接）
|
| 相关 Laravel 官方文档：https://laravel.com/docs/13.x/database
|
| 主要配置项概览：
|   - 默认数据库连接：sqlite（可由 DB_CONNECTION 环境变量覆盖）
|   - 内置连接类型：sqlite、mysql、mariadb、pgsql、sqlsrv
|   - 迁移记录表：migrations
|   - 默认 Redis 客户端：phpredis（备选 predis）
|   - 默认 Redis 数据库：0，缓存专用 Redis 数据库：1
|
*/

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    // ===== 区块：default（默认数据库连接名称）=====
    // 作用：指定应用未显式声明连接时默认使用的数据库连接。
    // 当执行 DB::table()、Model::query() 等数据库操作且未指定连接时，会使用此默认值。
    // 推荐开发环境使用 sqlite，生产环境使用 mysql/pgsql/sqlsrv。

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    // 默认数据库连接名；通过 DB_CONNECTION 环境变量指定，未设置时回退为 'sqlite'
    'default' => env('DB_CONNECTION', 'sqlite'),

    // ===== 区块：connections（数据库连接定义集合）=====
    // 作用：定义应用可用的全部数据库连接及其参数（驱动、主机、账号、字符集等）。
    // 可自由增删连接，例如为读写分离或多个独立数据库各自定义连接。
    // 支持的驱动：sqlite、mysql、mariadb、pgsql、sqlsrv。

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        // ----- sqlite：SQLite 数据库连接 -----
        // 轻量级文件型数据库，无需独立服务，适合开发环境与小型应用。
        'sqlite' => [
            'driver' => 'sqlite',                                  // 驱动类型：SQLite
            'url' => env('DB_URL'),                                // 数据库 URL（如 sqlite::memory:）；优先级高于 database 字段；为空时使用 database 字段
            'database' => env('DB_DATABASE', database_path('database.sqlite')), // 数据库文件路径；默认为 database/database.sqlite；使用 :memory: 表示内存数据库
            'prefix' => '',                                        // 表名前缀；用于多应用共享同一数据库时隔离表
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true), // 是否启用外键约束；默认 true
            'busy_timeout' => null,                                // 数据库被锁定时的等待超时（毫秒）；null 表示使用驱动默认值
            'journal_mode' => null,                                // 日志模式（如 DELETE、WAL、MEMORY）；null 表示使用驱动默认值
            'synchronous' => null,                                 // 同步级别（OFF、NORMAL、FULL）；null 表示使用驱动默认值
            'transaction_mode' => 'DEFERRED',                      // 事务模式：DEFERRED（延迟）、IMMEDIATE（立即）、EXCLUSIVE（独占）
        ],

        // ----- mysql：MySQL 数据库连接 -----
        // 生产环境最常用的关系型数据库，支持丰富特性和高并发。
        'mysql' => [
            'driver' => 'mysql',                          // 驱动类型：MySQL
            'url' => env('DB_URL'),                       // 数据库 URL（如 mysql://user:pass@host:3306/db）；优先级高于下列独立字段
            'host' => env('DB_HOST', '127.0.0.1'),        // 数据库主机地址；默认本机
            'port' => env('DB_PORT', '3306'),             // 端口号；默认 3306
            'database' => env('DB_DATABASE', 'laravel'),  // 数据库名；默认 'laravel'
            'username' => env('DB_USERNAME', 'root'),     // 登录用户名；默认 'root'
            'password' => env('DB_PASSWORD', ''),         // 登录密码；默认为空
            'unix_socket' => env('DB_SOCKET', ''),        // Unix Socket 路径；设置后将通过 socket 连接，host/port 被忽略
            'charset' => env('DB_CHARSET', 'utf8mb4'),    // 字符集；默认 utf8mb4，支持完整 Unicode（含 Emoji）
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'), // 排序规则；默认 utf8mb4_unicode_ci
            'prefix' => '',                                // 表名前缀；用于多应用共享同一数据库时隔离表
            'prefix_indexes' => true,                      // 是否给索引名也加表前缀；默认 true
            'strict' => true,                              // 是否启用严格模式（强制 NOT NULL、严格类型转换等）；生产环境建议 true
            'engine' => null,                              // 存储引擎（如 InnoDB、MyISAM）；null 表示使用服务器默认值
            'options' => extension_loaded('pdo_mysql') ? array_filter([ // 传递给 PDO 的额外选项（仅在 pdo_mysql 扩展可用时生效）
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'), // SSL 证书路径；用于建立加密连接；array_filter 会自动过滤空值
            ]) : [],
        ],

        // ----- mariadb：MariaDB 数据库连接 -----
        // MySQL 的开源分支，与 MySQL 协议兼容，配置项与 mysql 一致。
        'mariadb' => [
            'driver' => 'mariadb',                        // 驱动类型：MariaDB
            'url' => env('DB_URL'),                       // 数据库 URL；优先级高于下列独立字段
            'host' => env('DB_HOST', '127.0.0.1'),        // 数据库主机地址；默认本机
            'port' => env('DB_PORT', '3306'),             // 端口号；默认 3306
            'database' => env('DB_DATABASE', 'laravel'),  // 数据库名；默认 'laravel'
            'username' => env('DB_USERNAME', 'root'),     // 登录用户名；默认 'root'
            'password' => env('DB_PASSWORD', ''),         // 登录密码；默认为空
            'unix_socket' => env('DB_SOCKET', ''),         // Unix Socket 路径；设置后将通过 socket 连接，host/port 被忽略
            'charset' => env('DB_CHARSET', 'utf8mb4'),    // 字符集；默认 utf8mb4
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'), // 排序规则；默认 utf8mb4_unicode_ci
            'prefix' => '',                                // 表名前缀；用于多应用共享同一数据库时隔离表
            'prefix_indexes' => true,                      // 是否给索引名也加表前缀；默认 true
            'strict' => true,                              // 是否启用严格模式；生产环境建议 true
            'engine' => null,                              // 存储引擎；null 表示使用服务器默认值
            'options' => extension_loaded('pdo_mysql') ? array_filter([ // 传递给 PDO 的额外选项（仅在 pdo_mysql 扩展可用时生效）
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'), // SSL 证书路径；用于建立加密连接；array_filter 会自动过滤空值
            ]) : [],
        ],

        // ----- pgsql：PostgreSQL 数据库连接 -----
        // 功能强大的开源关系型数据库，支持复杂查询、JSON、扩展等高级特性。
        'pgsql' => [
            'driver' => 'pgsql',                          // 驱动类型：PostgreSQL
            'url' => env('DB_URL'),                       // 数据库 URL；优先级高于下列独立字段
            'host' => env('DB_HOST', '127.0.0.1'),        // 数据库主机地址；默认本机
            'port' => env('DB_PORT', '5432'),             // 端口号；默认 5432
            'database' => env('DB_DATABASE', 'laravel'),  // 数据库名；默认 'laravel'
            'username' => env('DB_USERNAME', 'root'),     // 登录用户名；默认 'root'
            'password' => env('DB_PASSWORD', ''),         // 登录密码；默认为空
            'charset' => env('DB_CHARSET', 'utf8'),       // 字符集；默认 utf8
            'prefix' => '',                                // 表名前缀；用于多应用共享同一数据库时隔离表
            'prefix_indexes' => true,                      // 是否给索引名也加表前缀；默认 true
            'search_path' => 'public',                     // Schema 搜索路径；默认 'public'（可指定多个，用逗号分隔）
            'sslmode' => env('DB_SSLMODE', 'prefer'),      // SSL 模式：disable、allow、prefer、require、verify-ca、verify-full；默认 prefer
        ],

        // ----- sqlsrv：SQL Server 数据库连接 -----
        // 微软 SQL Server 数据库，主要用于 Windows 服务器环境。
        'sqlsrv' => [
            'driver' => 'sqlsrv',                         // 驱动类型：SQL Server
            'url' => env('DB_URL'),                       // 数据库 URL；优先级高于下列独立字段
            'host' => env('DB_HOST', 'localhost'),        // 数据库主机地址；默认 localhost
            'port' => env('DB_PORT', '1433'),             // 端口号；默认 1433
            'database' => env('DB_DATABASE', 'laravel'),   // 数据库名；默认 'laravel'
            'username' => env('DB_USERNAME', 'root'),      // 登录用户名；默认 'root'
            'password' => env('DB_PASSWORD', ''),          // 登录密码；默认为空
            'charset' => env('DB_CHARSET', 'utf8'),        // 字符集；默认 utf8
            'prefix' => '',                                  // 表名前缀；用于多应用共享同一数据库时隔离表
            'prefix_indexes' => true,                        // 是否给索引名也加表前缀；默认 true
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),       // 是否启用加密连接；默认注释，按需启用
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'), // 是否信任服务器证书；默认注释，按需启用
        ],

    ],

    // ===== 区块：migrations（迁移记录表配置）=====
    // 作用：定义 Laravel 迁移系统用于跟踪已执行迁移的表及相关行为。
    // Laravel 通过此表判断哪些迁移文件尚未执行，避免重复执行迁移。

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',              // 记录已执行迁移的数据表名；默认 'migrations'
        'update_date_on_publish' => true,     // 通过 php artisan migrate:publish 发布迁移时是否更新日期字段；默认 true
    ],

    // ===== 区块：redis（Redis 连接配置）=====
    // 作用：定义应用可用的 Redis 连接及其客户端选项。
    // Redis 是高性能内存键值存储，常用于缓存、队列、会话、分布式锁等场景。
    // 通过 client 选项选择 phpredis（C 扩展，性能更好）或 predis（纯 PHP）。

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),  // Redis 客户端实现：phpredis（C 扩展，推荐）或 predis（纯 PHP 实现）

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'), // 集群模式选项：redis（标准集群，节点自动路由）或 predis（使用 predis 客户端）；非集群场景可留 'redis'
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'), // 键名前缀；优先取 REDIS_PREFIX，否则用 APP_NAME 生成（如 laravel-database-）
            'persistent' => env('REDIS_PERSISTENT', false), // 是否启用持久化连接 ID；false 使用短连接，true 启用长连接以减少握手开销
        ],

        // ----- default：默认 Redis 连接 -----
        // 通用 Redis 连接，供未明确指定连接的 Redis 操作使用。
        'default' => [
            'url' => env('REDIS_URL'),                                // Redis 连接 URL（如 redis://user:pass@host:port/db）；优先级高于下列独立字段
            'host' => env('REDIS_HOST', '127.0.0.1'),                 // Redis 主机地址；默认本机
            'username' => env('REDIS_USERNAME'),                       // 登录用户名；Redis 6+ 支持账户系统；为空时匿名登录
            'password' => env('REDIS_PASSWORD'),                       // 登录密码；为空表示无密码
            'port' => env('REDIS_PORT', '6379'),                       // 端口号；默认 6379
            'database' => env('REDIS_DB', '0'),                        // 使用的 Redis 数据库编号（0~15）；默认 0
            'max_retries' => env('REDIS_MAX_RETRIES', 3),              // 命令失败时的最大重试次数；默认 3
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'), // 重试退避算法：decorrelated_jitter（默认）、usleep、default
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),          // 退避初始等待时间（毫秒）；默认 100
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),           // 退避最大等待时间（毫秒）；默认 1000
        ],

        // ----- cache：缓存专用 Redis 连接 -----
        // 用于 cache.php 中 redis 驱动所引用的连接，与默认连接区分以隔离数据。
        'cache' => [
            'url' => env('REDIS_URL'),                                // Redis 连接 URL；优先级高于下列独立字段
            'host' => env('REDIS_HOST', '127.0.0.1'),                 // Redis 主机地址；默认本机
            'username' => env('REDIS_USERNAME'),                       // 登录用户名；Redis 6+ 支持账户系统
            'password' => env('REDIS_PASSWORD'),                       // 登录密码；为空表示无密码
            'port' => env('REDIS_PORT', '6379'),                       // 端口号；默认 6379
            'database' => env('REDIS_CACHE_DB', '1'),                  // 缓存专用数据库编号；默认 1，与默认连接的 0 隔离
            'max_retries' => env('REDIS_MAX_RETRIES', 3),              // 命令失败时的最大重试次数；默认 3
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'), // 重试退避算法：decorrelated_jitter（默认）、usleep、default
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),          // 退避初始等待时间（毫秒）；默认 100
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),           // 退避最大等待时间（毫秒）；默认 1000
        ],

    ],

];
