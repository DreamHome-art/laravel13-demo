<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| 会话配置文件 (config/session.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件集中管理 Laravel 会话（Session）的存储驱动、生命周期、加密、
| Cookie 属性以及文件/数据库/Redis 等存储后端配置。会话用于在请求间保持用户状态。
|
| 管理的配置范围：
|   - driver         会话默认存储驱动（file/cookie/database/memcached/redis/dynamodb/array）
|   - lifetime       会话空闲存活分钟数；expire_on_close 控制浏览器关闭即失效
|   - encrypt        是否对会话数据加密
|   - files          file 驱动的存储目录
|   - connection     database/redis 驱动使用的连接名
|   - table          database 驱动使用的表名
|   - store          缓存类驱动（dynamodb/memcached/redis）使用的缓存存储
|   - lottery        过期会话清理概率
|   - cookie / path / domain / secure / http_only / same_site / partitioned
|                    会话 Cookie 的各项属性
|   - serialization  会话数据序列化策略（json/php）
|
| 主要配置项概览：
|   - SESSION_DRIVER、SESSION_LIFETIME、SESSION_CONNECTION、SESSION_TABLE、
|     SESSION_COOKIE、SESSION_DOMAIN、SESSION_SAME_SITE 等
|
| 相关 Laravel 官方文档：https://laravel.com/docs/13.x/session
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    // ===== 区块：driver 默认会话驱动 =====
    // 控制请求默认使用的会话存储驱动。可选值：file（文件）、cookie（加密存入
    // Cookie）、database（数据库表）、memcached、redis、dynamodb（AWS）、
    // array（内存，仅测试用）。默认 'database'：将会话存入数据库表，便于多实例
    // 共享且无需额外服务，适合多数生产环境。
    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    // ===== 区块：lifetime 会话生命周期 =====
    // 会话允许空闲（无活动）的分钟数，超过此时间会话将过期。默认 120 分钟。
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    // 是否在浏览器关闭时会话立即失效；true 表示关闭浏览器即失效（不依赖 lifetime）。
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    // ===== 区块：encrypt 会话加密 =====
    // 是否在存储前自动加密所有会话数据。默认 false。启用后 Laravel 会用 APP_KEY
    // 自动加解密，使用方式不变。注意：加密会增加开销，且 cookie 驱动本身已加密。
    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    // ===== 区块：files 文件驱动存储目录 =====
    // 使用 file 驱动时会话文件存放的磁盘目录，默认 storage/framework/sessions。
    // 生产环境多机部署不建议使用 file 驱动（无法跨机器共享会话）。
    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    // ===== 区块：connection 数据库/Redis 连接名 =====
    // 使用 database 或 redis 驱动时采用的连接名，对应 config/database.php 中
    // 定义的数据库连接或 Redis 连接。留空则使用默认连接。
    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    // ===== 区块：table 数据库驱动表名 =====
    // 使用 database 驱动时存储会话的数据库表名，默认 'sessions'。需通过相关迁移创建。
    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "dynamodb", "memcached", "redis"
    |
    */

    // ===== 区块：store 缓存类驱动使用的缓存存储 =====
    // 当驱动为 dynamodb/memcached/redis 等基于缓存的会话后端时，指定使用的缓存
    // 存储名（对应 config/cache.php 中 stores 的键名）。留空则使用默认缓存存储。
    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    // ===== 区块：lottery 过期会话清理概率 =====
    // 部分驱动（如 file/database）需手动清理过期会话。此值为每次请求触发清理
    // 的概率，默认 [2, 100] 表示 2/100（即 2%）。数值越大清理越频繁，开销也越大。
    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    // ===== 区块：cookie 会话 Cookie 名称 =====
    // 框架创建的会话 Cookie 名称。默认由 APP_NAME 转为 slug 后加 '-session'
    // 后缀（如 'laravel13-demo-session'）。一般无需修改，改名不会带来实质安全提升。
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    // ===== 区块：path 会话 Cookie 路径 =====
    // Cookie 生效的 URL 路径。默认 '/' 表示整站可用。如需限制到子路径可在此指定。
    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain without subdomains. Typically, this shouldn't be changed.
    |
    */

    // ===== 区块：domain 会话 Cookie 域名 =====
    // Cookie 生效的域名。默认留空表示仅当前域名可用。设置为带前导点的域名
    // （如 .example.com）可使 Cookie 在所有子域共享。生产环境需谨慎配置。
    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    // ===== 区块：secure 仅 HTTPS 传输 Cookie =====
    // 设为 true 时，会话 Cookie 仅在 HTTPS 连接下发送，防止在不安全信道中泄露。
    // 默认 null 由框架按当前请求协议自动判断；生产环境建议显式设为 true。
    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    // ===== 区块：http_only 仅 HTTP 访问 =====
    // 设为 true 时 Cookie 标记为 HttpOnly，JavaScript（如 document.cookie）无法
    // 读取，可缓解 XSS 窃取 Cookie 的风险。默认 true，一般不应禁用。
    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    // ===== 区块：same_site 同站 Cookie 策略 =====
    // 控制跨站请求时 Cookie 的发送行为，可缓解 CSRF 攻击。可选值：'lax'（默认，
    // 允许安全跨站顶层导航携带）、'strict'（仅同站请求携带）、'none'（跨站均携带，
    // 需配合 secure 为 true）、null（不设置 SameSite 属性）。
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    // ===== 区块：partitioned 分区 Cookie =====
    // 设为 true 时启用 Cookie 分区（CHIPS），将 Cookie 绑定到顶级站点上下文，
    // 用于跨站点嵌入场景。仅当 secure 为 true 且 same_site 为 'none' 时浏览器才接受。
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Serialization
    |--------------------------------------------------------------------------
    |
    | This value controls the serialization strategy for session data, which
    | is JSON by default. Setting this to "php" allows the storage of PHP
    | objects in the session but can make an application vulnerable to
    | "gadget chain" serialization attacks if the APP_KEY is leaked.
    |
    | Supported: "json", "php"
    |
    */

    // ===== 区块：serialization 会话序列化策略 =====
    // 控制会话数据的序列化方式。'json'（默认）安全但无法存储 PHP 对象实例；
    // 'php' 可存储 PHP 对象，但若 APP_KEY 泄露可能遭受反序列化攻击，不建议使用。
    'serialization' => 'json',

];
