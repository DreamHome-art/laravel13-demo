<?php

use App\Processes\DemoProcess;

return [
    /*
    |--------------------------------------------------------------------------
    | LaravelS Settings
    |--------------------------------------------------------------------------
    |
    | English https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#laravels-settings
    | Chinese https://github.com/hhxsv5/laravel-s/blob/master/Settings-CN.md#laravels-%E9%85%8D%E7%BD%AE%E9%A1%B9
    |
    | 【中文说明】LaravelS 核心配置文件。LaravelS 基于 Swoole 让 Laravel 应用常驻内存
    | 运行，显著提升性能。下方为各配置项说明（保留官方英文注释并附中文翻译）。
    |
    */

    /*
    |--------------------------------------------------------------------------
    | The IP address of the server
    |--------------------------------------------------------------------------
    |
    | IPv4: use "127.0.0.1" to listen local address, and "0.0.0.0" to listen all addresses.
    | IPv6: use "::1" to listen local address, and "::"(equivalent to 0:0:0:0:0:0:0:0) to listen all addresses.
    |
    | 【中文说明】服务器监听的 IP 地址。
    |   IPv4：用 "127.0.0.1" 只监听本机回环地址，用 "0.0.0.0" 监听所有网卡地址。
    |   IPv6：用 "::1" 监听本机地址，用 "::"（等同于 0:0:0:0:0:0:0:0）监听所有地址。
    |
    */

    'listen_ip' => env('LARAVELS_LISTEN_IP', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | The port of the server
    |--------------------------------------------------------------------------
    |
    | Require root privilege if port is less than 1024.
    |
    | 【中文说明】服务器监听端口。端口小于 1024 时需要 root 权限。
    |
    */

    'listen_port' => env('LARAVELS_LISTEN_PORT', 5200),

    /*
    |--------------------------------------------------------------------------
    | The socket type of the server
    |--------------------------------------------------------------------------
    |
    | Usually, you don’t need to care about it.
    | Unless you want Nginx to proxy to the UnixSocket Stream file, you need
    | to modify it to SWOOLE_SOCK_UNIX_STREAM, and listen_ip is the path of UnixSocket Stream file.
    | List of socket types:
    | SWOOLE_SOCK_TCP: TCP
    | SWOOLE_SOCK_TCP6: TCP IPv6
    | SWOOLE_SOCK_UDP: UDP
    | SWOOLE_SOCK_UDP6: UDP IPv6
    | SWOOLE_UNIX_DGRAM: Unix socket dgram
    | SWOOLE_UNIX_STREAM: Unix socket stream
    | Enable SSL: $sock_type | SWOOLE_SSL. To enable SSL, check the configuration about SSL.
    | https://www.swoole.co.uk/docs/modules/swoole-server-doc
    | https://www.swoole.co.uk/docs/modules/swoole-server/configuration
    |
    | 【中文说明】服务器的 Socket 类型，通常无需修改。
    |   仅当需要让 Nginx 代理到 UnixSocket Stream 文件时，才改为 SWOOLE_SOCK_UNIX_STREAM，
    |   此时 listen_ip 需填写 UnixSocket Stream 文件的路径。
    |   Socket 类型列表：TCP / TCP(IPv6) / UDP / UDP(IPv6) / Unix Dgram / Unix Stream，
    |   开启 SSL 时使用 $sock_type | SWOOLE_SSL 组合，详见上方 SSL 相关文档链接。
    |
    */

    'socket_type' => defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : 1,

    /*
    |--------------------------------------------------------------------------
    | Server Name
    |--------------------------------------------------------------------------
    |
    | This value represents the name of the server that will be
    | displayed in the header of each request.
    |
    | 【中文说明】服务器名称，将显示在每个请求的响应头（Server Header）中。
    |
    */

    'server' => env('LARAVELS_SERVER', ''),

    /*
    |--------------------------------------------------------------------------
    | Handle Static Resource
    |--------------------------------------------------------------------------
    |
    | Whether handle the static resource by LaravelS(Require Swoole >= 1.7.21, Handle by Swoole if Swoole >= 1.9.17).
    | Suggest that Nginx handles the statics and LaravelS handles the dynamics.
    | The default path of static resource is base_path('public'), you can modify swoole.document_root to change it.
    |
    | 【中文说明】是否由 LaravelS 处理静态资源（需 Swoole >= 1.7.21，Swoole >= 1.9.17 时由 Swoole 处理）。
    |   建议由 Nginx 处理静态资源、LaravelS 处理动态请求。
    |   静态资源默认路径为 base_path('public')，可通过 swoole.document_root 配置项修改。
    |
    */

    'handle_static' => env('LARAVELS_HANDLE_STATIC', false),

    /*
    |--------------------------------------------------------------------------
    | Laravel Base Path
    |--------------------------------------------------------------------------
    |
    | The basic path of Laravel, default base_path(), be used for symbolic link.
    |
    | 【中文说明】Laravel 项目根目录，默认为 base_path()。
    |   使用符号链接（软链部署）时可通过该项指定真实路径。
    |
    */

    'laravel_base_path' => env('LARAVEL_BASE_PATH', base_path()),

    /*
    |--------------------------------------------------------------------------
    | Inotify Reload
    |--------------------------------------------------------------------------
    |
    | This feature requires inotify extension.
    | https://github.com/hhxsv5/laravel-s#automatically-reload-after-modifying-code
    |
    | 【中文说明】Inotify 自动重载功能（需要 inotify 扩展）。
    |   代码修改后自动重载所有 Worker 进程，适用于开发调试。
    |
    */

    'inotify_reload' => [
        // Whether enable the Inotify Reload to reload all worker processes when your code is modified.
        // 是否开启 Inotify 重载：代码修改后重载全部 Worker 进程
        'enable'        => env('LARAVELS_INOTIFY_RELOAD', false),

        // The file path that Inotify watches
        // Inotify 监听的文件路径
        'watch_path'    => base_path(),

        // The file types that Inotify watches
        // Inotify 监听的文件类型（按扩展名）
        'file_types'    => ['.php'],

        // The excluded/ignored directories that Inotify watches
        // Inotify 监听时需要排除/忽略的目录
        'excluded_dirs' => [],

        // Whether output the reload log
        // 是否输出重载日志
        'log'           => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Event Handlers
    |--------------------------------------------------------------------------
    |
    | Configure the event callback function of Swoole, key-value format,
    | key is the event name, and value is the class that implements the event
    | processing interface.
    |
    | https://github.com/hhxsv5/laravel-s#configuring-the-event-callback-function-of-swoole
    |
    | 【中文说明】配置 Swoole 的事件回调函数，键值对格式：
    |   键为事件名称，值为实现对应事件处理接口的类。
    |
    */

    'event_handlers' => [],

    /*
    |--------------------------------------------------------------------------
    | WebSockets
    |--------------------------------------------------------------------------
    |
    | Swoole WebSocket Server settings.
    |
    | https://github.com/hhxsv5/laravel-s#enable-websocket-server
    |
    | 【中文说明】Swoole WebSocket 服务设置。启用后需指定处理类。
    |
    */

    'websocket' => [
        'enable' => true,
        'handler' => \App\Services\WebSocketService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sockets - multi-port mixed protocol
    |--------------------------------------------------------------------------
    |
    | The socket(port) list for TCP/UDP.
    |
    | https://github.com/hhxsv5/laravel-s#multi-port-mixed-protocol
    |
    | 【中文说明】TCP/UDP 多端口混合协议的 Socket（端口）列表。
    |
    */

    'sockets' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Process
    |--------------------------------------------------------------------------
    |
    | Support developers to create custom processes for monitoring,
    | reporting, or other special tasks.
    |
    | https://github.com/hhxsv5/laravel-s#custom-process
    |
    | 【中文说明】自定义进程：支持开发者创建用于监控、上报或其他特殊任务的进程。
    |
    */

    'processes' => [
        'demo' => [
            'class' => DemoProcess::class,
            'redirect' => false,
            'pipe' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timer
    |--------------------------------------------------------------------------
    |
    | Wrapper cron job base on Swoole's Millisecond Timer, replace Linux Crontab.
    |
    | https://github.com/hhxsv5/laravel-s#millisecond-cron-job
    |
    | 【中文说明】定时器：基于 Swoole 毫秒定时器封装的 Cron 任务，可替代 Linux Crontab。
    |
    */

    'timer' => [
        'enable'          => env('LARAVELS_TIMER', false),

        // The list of cron job
        // Cron 定时任务列表
        'jobs'            => [
            // Enable LaravelScheduleJob to run `php artisan schedule:run` every 1 minute, replace Linux Crontab
            // 启用 LaravelScheduleJob 后，每 1 分钟执行一次 `php artisan schedule:run`，替代 Linux Crontab
            // Hhxsv5\LaravelS\Illuminate\LaravelScheduleJob::class,
        ],

        // Max waiting time of reloading
        // 重载时的最大等待时间（秒）
        'max_wait_time'   => 5,

        // Enable the global lock to ensure that only one instance starts the timer
        // when deploying multiple instances.
        // This feature depends on Redis https://laravel.com/docs/8.x/redis
        // 多实例部署时启用全局锁，确保只有一个实例启动定时器；此功能依赖 Redis
        'global_lock'     => false,
        'global_lock_key' => config('app.name', 'Laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Tables
    |--------------------------------------------------------------------------
    |
    | All defined tables will be created before Swoole starting.
    |
    | https://github.com/hhxsv5/laravel-s#use-swooletable
    |
    | 【中文说明】Swoole Table（共享内存表）列表，所有定义的表会在 Swoole 启动前创建，
    |   可用于跨 Worker 进程共享数据。
    |
    */

    'swoole_tables' => [],

    /*
    |--------------------------------------------------------------------------
    | Re-register Providers
    |--------------------------------------------------------------------------
    |
    | The Service Provider list, will be re-registered each request, and run method boot()
    | if it exists. Usually, be used to clear the Service Provider
    | which registers Singleton instances.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#register_providers
    |
    | 【中文说明】重新注册的服务提供者列表：每次请求都会重新注册这些 ServiceProvider，
    |   并在存在 boot() 方法时执行之。通常用于清理注册了单例实例的服务提供者，
    |   避免常驻内存环境下请求间的状态污染。
    |
    */

    'register_providers' => [],

    /*
    |--------------------------------------------------------------------------
    | Cleaners
    |--------------------------------------------------------------------------
    |
    | The list of cleaners for each request is used to clean up some residual
    | global variables, singleton objects, and static properties to avoid
    | data pollution between requests.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#cleaners
    |
    | 【中文说明】清理器（Cleaner）列表：每次请求结束后执行，用于清理残留的
    |   全局变量、单例对象和静态属性，避免请求之间的数据污染。
    |
    */

    'cleaners' => [],

    /*
    |--------------------------------------------------------------------------
    | Destroy Controllers
    |--------------------------------------------------------------------------
    |
    | Automatically destroy the controllers after each request to solve
    | the problem of the singleton controllers.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/KnownIssues.md#singleton-controller
    |
    | 【中文说明】每次请求结束后自动销毁控制器，解决单例控制器问题。
    |   excluded_list 为不需要销毁的控制器名单。
    |
    */

    'destroy_controllers' => [
        'enable'        => false,
        'excluded_list' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Settings
    |--------------------------------------------------------------------------
    |
    | Swoole's original configuration items.
    |
    | More settings
    | Chinese https://wiki.swoole.com/#/server/setting
    | English https://www.swoole.co.uk/docs/modules/swoole-server/configuration
    |
    | 【中文说明】Swoole 原生配置项（直接透传给 Swoole Server），
    |   常用项含义见下方行内中文注释，更多配置见上方文档链接。
    |
    */

    'swoole' => [
        // 是否以守护进程（后台）方式运行
        'daemonize'          => env('LARAVELS_DAEMONIZE', false),
        // 数据包分发模式：1-轮询 2-固定分配 3-抢占式 4-IP 哈希 5-UID 哈希
        'dispatch_mode'      => env('LARAVELS_DISPATCH_MODE', 3),
        // Worker 进程数量
//        'worker_num'         => env('LARAVELS_WORKER_NUM', 30),
        'worker_num'         => swoole_cpu_num() * 2,
        // Task Worker 进程数量（处理异步任务）
        'task_worker_num'    => env('LARAVELS_TASK_WORKER_NUM', 4),
        // Task 进程间通信模式：1-UnixSocket 2-消息队列 3-消息队列+争抢
        'task_ipc_mode'      => 1,
        // Task Worker 处理任务数达到该值后自动重启（防止内存泄漏）
        'task_max_request'   => env('LARAVELS_TASK_MAX_REQUEST', 100000),
        // Task 数据临时目录（优先使用 /dev/shm 共享内存）
        'task_tmpdir'        => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        // Worker 处理请求数达到该值后自动重启（防止内存泄漏）
        'max_request'        => env('LARAVELS_MAX_REQUEST', 3000),
        // 开启 TCP_NODELAY（关闭 Nagle 算法，降低小包延迟）
        'open_tcp_nodelay'   => true,
        // 主进程 PID 文件路径
        'pid_file'           => storage_path('laravels.pid'),
        // Swoole 日志级别：0-debug 1-trace 2-info 3-notice 4-warning 5-error
        'log_level'          => env('LARAVELS_LOG_LEVEL', 4),
        // Swoole 日志文件（按月分文件）
        'log_file'           => storage_path(sprintf('logs/swoole-%s.log', date('Y-m'))),
        // 静态资源根目录（配合 handle_static 使用）
        'document_root'      => base_path('public'),
        // 单次输出缓冲区大小（2MB）
        'buffer_output_size' => 2 * 1024 * 1024,
        // Socket 发送缓冲区大小（8MB）
        'socket_buffer_size' => 8 * 1024 * 1024,
        // 单包最大长度（4MB）
        'package_max_length' => 4 * 1024 * 1024,
        // 异步安全重载 Worker
        'reload_async'       => true,
        // 重载时最大等待时间（秒）
        'max_wait_time'      => 60,
        // 开启端口重用（允许多进程监听同一端口）
        'enable_reuse_port'  => true,
        // 是否开启协程
        'enable_coroutine'   => true,
        // 上传文件临时目录（优先使用 /dev/shm 共享内存）
        'upload_tmp_dir'     => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        // 是否开启 HTTP 压缩（gzip/br）
        'http_compression'   => env('LARAVELS_HTTP_COMPRESSION', false),
        // 全Hook，mysql、redis、file_get_contents、stream自动协程化
        'hook_flags' => SWOOLE_HOOK_ALL,
    ],

    // 协程高并发必开：连接池
    'pool' => [
        'mysql' => [
            'enable' => true,
            'max_active' => 20,
            'max_idle' => 20,
        ],
        'redis' => [
            'enable' => true,
            'max_active' => 20,
        ],
    ],
];
