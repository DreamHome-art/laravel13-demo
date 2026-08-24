<?php

/*
|--------------------------------------------------------------------------
| 应用基础配置文件 (config/app.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件是 Laravel 框架的应用层基础配置入口，集中管理应用
| 名称、运行环境、调试模式、URL、时区、语言、加密密钥、维护模式等核心
| 配置项。Laravel 启动时会读取本文件并将配置写入 config 仓库，供全应用
| 通过 config('app.xxx') 调用。
|
| 管理范围：
|   - 应用标识（名称、环境、调试模式、URL）
|   - 本地化与时区（timezone、locale、fallback_locale、faker_locale）
|   - 加密相关（cipher、key、previous_keys）
|   - 维护模式驱动（maintenance）
|
| 注意事项：
|   - 自 Laravel 11 起，服务提供者 (Providers) 与别名 (Aliases) 已迁移
|     到 bootstrap/app.php 与 bootstrap/providers.php，本文件不再包含。
|   - 大多数配置项通过 env() 从 .env 文件读取，部署时务必正确设置 .env。
|   - 修改本文件后通常无需重启，但部分缓存配置需执行：php artisan config:cache
|
| 相关文档：https://laravel.com/docs/13.x/configuration
|
*/

return [

    // ===== 区块：应用名称 (Application Name) =====
    // 作用：定义应用名称，用于框架需要在通知、邮件、UI 元素中展示应用名称的场景。
    // 配置项：name —— 通过 APP_NAME 环境变量读取，默认值为 'Laravel'。
    // 建议：生产环境应设置为项目正式名称（如公司或产品名）。

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'), // 应用名称，来自 .env 的 APP_NAME，默认 Laravel

    // ===== 区块：应用运行环境 (Application Environment) =====
    // 作用：标识当前应用所处的运行环境，框架及第三方服务可据此切换不同行为。
    // 配置项：env —— 通过 APP_ENV 读取，默认 'production'。
    // 常见取值：'local'（本地开发）、'production'（生产）、'testing'（测试）、'staging'（预发布）。
    // 注意：建议在 .env 中显式设置，避免误用生产配置；不同环境可加载不同 .env。

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'), // 运行环境标识，默认 production（生产）

    // ===== 区块：调试模式 (Application Debug Mode) =====
    // 作用：控制错误信息显示详尽程度。开启后异常会输出堆栈跟踪、环境变量等敏感信息。
    // 配置项：debug —— 通过 APP_DEBUG 读取，默认 false。
    // 取值：true / false（通过 (bool) 强制转换为布尔）。
    // 注意：生产环境必须为 false，否则会泄露敏感信息造成安全风险；仅本地开发开启。

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false), // 调试模式开关，生产环境务必为 false

    // ===== 区块：应用 URL (Application URL) =====
    // 作用：定义应用根 URL，供 Artisan 命令行生成 URL、邮件链接等使用。
    // 配置项：url —— 通过 APP_URL 读取，默认 'http://localhost'。
    // 建议：生产环境应设置为完整带 http(s):// 的域名，注意尾部不要加斜杠。
    // 影响：影响 URL::to()、Artisan 中 url() 生成、邮件模板中的链接地址等。

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'), // 应用根 URL，生产应设为实际域名

    // ===== 区块：应用时区 (Application Timezone) =====
    // 作用：定义应用默认时区，影响 PHP 日期时间函数及数据库时间存储。
    // 配置项：timezone —— 直接硬编码为 'UTC'，未走 env()（默认时区）。
    // 取值参考：'UTC'（世界协调时）、'Asia/Shanghai'（中国标准时）、'America/New_York' 等。
    // 注意：修改时区会影响所有 Carbon 实例与数据库时间字段的展示，请谨慎。
    // 建议：面向中国用户的项目可改为 'Asia/Shanghai'。

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it's suitable for most use cases.
    |
    */

    'timezone' => 'Asia/Shanghai', // 应用时区，默认 UTC（世界协调时）

    // ===== 区块：本地化与语言配置 (Application Locale Configuration) =====
    // 作用：设置应用默认语言、回退语言以及 Faker 假数据生成语言。
    // 配置项：
    //   - locale         主语言，用于 __() / trans() 等翻译函数
    //   - fallback_locale 主语言翻译缺失时回退使用的语言
    //   - faker_locale   Faker 生成假数据的区域语言（影响姓名、地址、电话格式）
    // 注意：语言资源文件位于 lang/<locale>/ 目录下；测试时 faker_locale 影响数据真实性。

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'), // 应用默认语言，默认 en（英文）

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'), // 翻译缺失时的回退语言，默认 en

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'), // Faker 假数据生成区域，默认 en_US

    // ===== 区块：加密密钥 (Encryption Key) =====
    // 作用：配置 Laravel 加密服务所用算法与密钥，用于加密 Cookie、Session、自定义加密数据等。
    // 配置项：
    //   - cipher         加密算法，固定为 AES-256-CBC（推荐且兼容性最好）
    //   - key            主加密密钥，来自 APP_KEY，须为 32 字符随机串
    //   - previous_keys  旧密钥列表，密钥轮换后用于解密由旧密钥加密的数据
    // 注意：
    //   - APP_KEY 缺失时加密相关功能（如 session、加密 cookie）将无法正常工作。
    //   - 生产部署前必须设置 APP_KEY，可通过 php artisan key:generate 生成。
    //   - 轮换密钥时，将旧 key 加入 APP_PREVIOUS_KEYS 以平滑迁移。

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC', // 加密算法，使用 AES-256-CBC（Laravel 推荐）

    'key' => env('APP_KEY'), // 主加密密钥，来自 .env 的 APP_KEY（须为 32 字符随机串）

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', '')) // 旧密钥列表（逗号分隔），用于密钥轮换
        ),
    ],

    // ===== 区块：维护模式驱动 (Maintenance Mode Driver) =====
    // 作用：配置 Laravel 维护模式（php artisan down / up）的状态存储驱动与缓存仓库。
    // 配置项：
    //   - driver  维护状态存储驱动，支持 'file'（本地文件）、'cache'（缓存）、'array'（内存）。
    //   - store   当 driver 为 'cache' 时使用的缓存仓库连接名（对应 config/cache.php 的 stores）。
    // 注意：
    //   - 多机部署应使用 'cache' 驱动以共享维护状态，避免单机 file 不一致。
    //   - 'array' 驱动仅适用于测试场景，重启进程后状态丢失。

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'), // 维护状态存储驱动，默认 file
        'store' => env('APP_MAINTENANCE_STORE', 'database'), // cache 驱动所用的缓存连接，默认 database
    ],

];
