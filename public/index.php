<?php

/*
|--------------------------------------------------------------------------
| Laravel 应用入口文件 (index.php)
|--------------------------------------------------------------------------
|
| 文件用途：所有 HTTP 请求的统一入口点。
| Web 服务器（Nginx / Apache）通过配置将所有未直接命中的静态资源请求
| 重写到本文件，由本文件引导启动 Laravel 框架并处理请求。
|
| 请求处理流程：
|   1. 记录应用启动时间戳（LARAVEL_START）
|   2. 检查维护模式，若处于维护模式则加载维护页面
|   3. 注册 Composer 自动加载器（vendor/autoload.php）
|   4. 引导启动 Laravel 应用（bootstrap/app.php 返回 Application 实例）
|   5. 捕获并处理 HTTP 请求，输出响应
|
| 相关 Laravel 官方文档：
|   https://laravel.com/docs/13.x/lifecycle
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// ===== 区块：记录应用启动时间 =====
// 定义 LARAVEL_START 常量为当前时间戳（微秒级），
// 用于后续计算请求处理耗时（如 Laravel Debugbar、性能分析工具）。
// 该常量在框架生命周期最早期记录，确保耗时统计涵盖完整请求过程。
define('LARAVEL_START', microtime(true));

// ===== 区块：维护模式检查 =====
// Laravel is in maintenance mode...
// 检查是否存在 maintenance.php 文件（由 `php artisan down` 命令生成）。
// 若存在则说明应用处于维护模式，立即加载该文件并终止后续流程：
//   - maintenance.php 会输出 503 维护页面给客户端
//   - 允许通过 secret 参数绕过（维护模式期间预览）
//   - `php artisan up` 命令会删除该文件，恢复正常访问
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// ===== 区块：注册 Composer 自动加载器 =====
// Register the Composer autoloader...
// 引入 Composer 生成的自动加载器，负责按需加载所有依赖类文件。
// 这是使用框架类（如 Application、Request、Eloquent 等）的前提。
// 路径指向项目根目录下的 vendor/autoload.php。
require __DIR__.'/../vendor/autoload.php';

// ===== 区块：引导启动 Laravel 应用 =====
// Bootstrap Laravel and handle the request...
// 引入 bootstrap/app.php，完成应用初始化：
//   - 创建 Application 实例（服务容器）
//   - 配置路由、中间件、异常处理等
//   - 返回配置完成的 Application 实例
// 使用 require_once 确保该文件在单次请求中只加载一次。
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// ===== 区块：处理 HTTP 请求 =====
// 调用 Request::capture() 从 PHP 全局变量（$_GET / $_POST / $_SERVER 等）
// 捕获当前 HTTP 请求并构造为 Request 实例，
// 再交给 Application::handleRequest() 进入框架请求处理流程：
//   - 经过全局中间件管道
//   - 路由分发到对应控制器 / 闭包
//   - 中间件后置处理
//   - 生成并输出最终响应给客户端
$app->handleRequest(Request::capture());
