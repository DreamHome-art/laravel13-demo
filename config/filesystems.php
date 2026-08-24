<?php

/*
|--------------------------------------------------------------------------
| 文件系统配置文件 (filesystems.php)
|--------------------------------------------------------------------------
|
| 文件用途：定义 Laravel 应用程序文件存储相关的全部配置。
| 管理的配置范围：
|   - default : 默认文件系统磁盘
|   - disks   : 可用的文件系统磁盘集合（local / public / s3 等）
|   - links   : 执行 `storage:link` 命令时创建的软链接映射
|
| 主要配置项概览：
|   'default' => 'local'        默认磁盘，框架所有文件操作未指定磁盘时使用
|   'disks'   => [...]          支持的磁盘定义（本地、公共、S3 等）
|   'links'   => [...]          public/storage -> storage/app/public 软链
|
| 相关 Laravel 官方文档：
|   https://laravel.com/docs/13.x/filesystem
|
*/

return [

    // ===== 区块：default 默认磁盘 =====
    // 框架默认使用的文件系统磁盘名。当调用 Storage::put()、Storage::get() 等
    // 方法但未显式指定磁盘时，将使用此处配置的磁盘。
    // 可选值：disks 配置块中定义的任意磁盘名（如 local / public / s3）。
    // 默认值：local（即使用本地私有存储）。
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */
    'default' => env('FILESYSTEM_DISK', 'local'), // 默认磁盘名，可通过环境变量 FILESYSTEM_DISK 覆盖，默认 local

    // ===== 区块：disks 文件系统磁盘集合 =====
    // 这里可以配置任意数量的磁盘，甚至为同一个 driver 配置多个磁盘实例。
    // 支持的驱动类型："local", "ftp", "sftp", "s3"
    // 每个磁盘的 driver 决定了它的存储方式，其他参数作为该驱动的配置项。
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */
    'disks' => [

        // ----- local：本地私有磁盘 -----
        // 仅应用内部可访问，不对外公开。Laravel 11+ 默认存储于 app/private 子目录。
        'local' => [
            'driver' => 'local',                      // 驱动类型：本地文件系统
            'root' => storage_path('app/private'),    // 存储根目录：storage/app/private
            'serve' => true,                          // 是否允许通过框架的文件响应方法直接对外提供该磁盘文件服务
            'throw' => false,                         // 发生异常时是否抛出（false=静默处理）
            'report' => false,                        // 是否将读写异常上报到日志（false=不上报）
        ],

        // ----- public：本地公开磁盘 -----
        // 通过 public/storage 软链对外可访问，文件 URL 可被外部直接访问。
        'public' => [
            'driver' => 'local',                      // 驱动类型：本地文件系统
            'root' => storage_path('app/public'),     // 存储根目录：storage/app/public
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage', // 生成文件 URL 时的访问前缀（基于 APP_URL 拼接 /storage）
            'visibility' => 'public',                 // 文件可见性：public（公有，可被外部访问）
            'throw' => false,                         // 发生异常时是否抛出
            'report' => false,                        // 是否将读写异常上报到日志
        ],

        // ----- s3：AWS S3 云存储磁盘 -----
        // 适用于生产环境的大文件/对象存储场景。需通过 .env 配置 AWS 凭据。
        // 也兼容 S3 协议的第三方对象存储（如 MinIO、阿里云 OSS、腾讯云 COS）。
        's3' => [
            'driver' => 's3',                                      // 驱动类型：AWS S3 兼容对象存储
            'key' => env('AWS_ACCESS_KEY_ID'),                     // AWS 访问密钥 ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'),              // AWS 访问密钥 Secret
            'region' => env('AWS_DEFAULT_REGION'),                 // AWS 区域（如 us-east-1、ap-northeast-1）
            'bucket' => env('AWS_BUCKET'),                         // S3 bucket 名称
            'url' => env('AWS_URL'),                               // 自定义访问 URL（如使用 CDN），留空则使用 S3 默认 URL
            'endpoint' => env('AWS_ENDPOINT'),                    // 自定义 endpoint（用于 MinIO/OSS/COS 等 S3 兼容服务）
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false), // 是否使用 path-style endpoint（MinIO 需设为 true）
            'throw' => false,                                      // 发生异常时是否抛出
            'report' => false,                                     // 是否将读写异常上报到日志
        ],

    ],

    // ===== 区块：links 软链接映射 =====
    // 配置执行 `php artisan storage:link` 命令时创建的软链接。
    // 数组 key 为软链接位置（在 public 目录下），value 为链接指向的真实存储目录。
    // 软链使 storage/app/public 中的文件可通过 web 直接访问。
    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:Link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */
    'links' => [
        public_path('storage') => storage_path('app/public'), // public/storage -> storage/app/public，使 public 磁盘可通过 web 访问
    ],

];
