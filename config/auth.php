<?php

/*
|--------------------------------------------------------------------------
| 用户认证配置文件 (config/auth.php)
|--------------------------------------------------------------------------
|
| 文件用途：本文件是 Laravel 认证 (Authentication) 体系的核心配置入口，
| 集中管理应用默认守卫 (Guard)、用户守卫定义、用户 Provider、密码重置
| (Password Reset) 与敏感操作密码确认超时等配置项。
|
| 管理范围：
|   - 默认守卫与密码重置代理 (defaults)
|   - 守卫 (guards) —— 决定请求如何被认证（如基于 session）
|   - 用户 Provider (providers) —— 决定如何从存储中读取用户记录（Eloquent / Database）
|   - 密码重置代理 (passwords) —— 决定令牌表、有效期、节流时间
|   - 敏感操作密码确认超时 (password_timeout)
|
| 注意事项：
|   - 默认用户模型为 App\Models\User，可经 AUTH_MODEL 环境变量覆盖。
|   - 自定义多用户体系（如前后台分离）时，需在此新增 guards 与 providers。
|   - 修改后可通过 config:cache 重新缓存配置。
|
| 相关文档：https://laravel.com/docs/13.x/configuration
|           https://laravel.com/docs/13.x/authentication
|
*/

use App\Models\User;

return [

    // ===== 区块：默认认证配置 (Authentication Defaults) =====
    // 作用：定义应用默认使用的认证守卫与密码重置代理。
    //   - guard     默认守卫名称，未指定守卫时 Auth 门面使用的守卫。
    //   - passwords  默认密码重置代理名称，发送重置邮件等场景使用。
    // 注意：通过 AUTH_GUARD / AUTH_PASSWORD_BROKER 环境变量可整体覆盖默认值。

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'), // 默认守卫，默认 'web'（基于 session）
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'), // 默认密码重置代理，默认 'users'
    ],

    // ===== 区块：用户守卫 (Authentication Guards) =====
    // 作用：定义应用所有可用守卫。守卫决定每个请求如何被认证。
    // 每个 guard 由 driver（认证驱动）与 provider（用户 Provider 名）组成。
    // 当前 Laravel 内置 driver 仅支持 'session'，第三方包（如 sanctum、passport）可扩展。
    // 配置项：
    //   - driver    守卫驱动，'session' 表示基于会话的 Web 认证。
    //   - provider  守卫使用的用户 Provider 名（指向下方 providers 区块对应 key）。
    // 注意：API 认证通常使用 sanctum（配置在 config/sanctum.php）；自定义 guard 后需在 bootstrap/app.php 中间件层挂载。

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session', // 守卫驱动：基于 session 的 Web 认证
            'provider' => 'users', // 该守卫使用的用户 Provider 名，指向 providers['users']
        ],
    ],

    // ===== 区块：用户 Provider (User Providers) =====
    // 作用：定义如何从存储（数据库 / Eloquent）中读取用户记录。
    // 支持的 driver：'eloquent'（使用 Eloquent 模型）、'database'（直接查询数据库表）。
    // 配置项：
    //   - eloquent: model  使用 Eloquent 时指定的用户模型类（须继承 Illuminate\Foundation\Auth\User）。
    //   - database: table  使用 database 驱动时指定的数据表名（默认注释掉的备选方案）。
    // 注意：可定义多个 providers 以支持多用户体系（如管理员表与会员表分离）。
    //       通过 AUTH_MODEL 环境变量可覆盖默认模型（便于测试或多模型场景）。

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent', // 用户存储驱动：使用 Eloquent ORM 读取用户
            'model' => env('AUTH_MODEL', User::class), // 用户模型类，默认 App\Models\User，可通过 AUTH_MODEL 覆盖
        ],

        // 'users' => [
        //     'driver' => 'database',  // 备选方案：直接查询数据库表（不走 Eloquent）
        //     'table' => 'users',      // 指定用户表名
        // ],
    ],

    // ===== 区块：密码重置 (Resetting Passwords) =====
    // 作用：定义 Laravel 内置密码重置功能（如 ForgotPasswordController、ResetPasswordController）的行为。
    // 每个 broker 配置项：
    //   - provider  重置时所用的用户 Provider 名（指向 providers 中对应 key）。
    //   - table     存储密码重置令牌的数据表名（默认 password_reset_tokens）。
    //   - expire    重置令牌有效期（分钟），过期后令牌失效，需重新申请。
    //   - throttle  节流时间（秒），同一用户两次申请重置令牌之间最小间隔，防止滥用。
    // 注意：
    //   - 表 password_reset_tokens 需通过 migration 创建（Laravel 自带 stub）。
    //   - 节流以秒为单位，过期以分钟为单位，注意单位差异。

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users', // 重置时调用的用户 Provider，指向 providers['users']
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'), // 重置令牌存储表名
            'expire' => 60, // 重置令牌有效期（分钟），默认 60 分钟
            'throttle' => 60, // 重置令牌申请节流时间（秒），默认 60 秒
        ],
    ],

    // ===== 区块：敏感操作密码确认超时 (Password Confirmation Timeout) =====
    // 作用：定义用户在执行敏感操作（如修改邮箱、删除账号）前再次输入密码后的"已确认"状态保持时长。
    // 超过该时长后再次触发敏感操作会重新要求确认密码。
    // 配置项：password_timeout —— 通过 AUTH_PASSWORD_TIMEOUT 读取，单位：秒，默认 10800（即 3 小时）。
    // 注意：调小该值可提升安全性，但会增加用户重复输密码的频率。

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800), // 敏感操作密码确认窗口期（秒），默认 10800（3 小时）

];
