<?php

/*
|--------------------------------------------------------------------------
| Laravel 应用引导文件 (bootstrap/app.php)
|--------------------------------------------------------------------------
|
| 文件用途：Laravel 11+ 引入的应用引导入口，用于配置和创建 Application 实例。
| 所有 HTTP 请求与 Artisan 命令均通过 public/index.php 引用本文件完成应用启动。
|
| 配置流程（Application::configure 链式调用）：
|   1. 设置应用基础路径（basePath）
|   2. 配置路由（withRouting）
|   3. 配置中间件（withMiddleware）
|   4. 配置异常处理（withExceptions）
|   5. create() 创建并返回 Application 实例
|
| 相关 Laravel 官方文档：
|   https://laravel.com/docs/13.x/structure#the-bootstrap-directory
|
*/

use App\Http\Middleware\TraceIdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// ===== 区块：创建 Application 实例并设置基础路径 =====
// Application::configure(basePath: dirname(__DIR__))
// basePath 指向项目根目录（本文件位于 bootstrap/，dirname(__DIR__) 即根目录），
// 框架据此解析 app/、config/、database/、storage/、routes/ 等子目录路径。
return Application::configure(basePath: dirname(__DIR__))

    // ===== 区块：路由配置（withRouting） =====
    // 注册路由文件与路由相关功能：
    //   web    —— Web 路由文件，自动应用 web 中间件组（Session、CSRF 保护等）
    //   commands —— Artisan 命令路由文件，注册闭包形式控制台命令
    //   health —— 健康检查端点路由（/up），返回 200 表示应用正常
    //            用于负载均衡器、容器编排（K8s）、监控探活
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    // ===== 区块：中间件配置（withMiddleware） =====
    // 自定义全局中间件与中间件组。闭包接收 Middleware 实例用于配置：
    //   prepend() —— 将中间件插入到全局中间件栈最前面（最先执行）
    //   append()  —— 将中间件追加到全局中间件栈末尾（后执行）
    //   使用 alias()/group()/disable() 等方法进行其他配置
    ->withMiddleware(function (Middleware $middleware): void {
        // 注册 trace_id 全局中间件：为每个请求注入唯一标识，全链路日志串联
        //（应用层日志与 SQL 日志均自动携带 trace_id，便于按请求串联检索）
        $middleware->prepend(TraceIdMiddleware::class);
    })

    // ===== 区块：异常处理配置（withExceptions） =====
    // 自定义异常渲染与报告策略。闭包接收 Exceptions 实例用于配置：
    //   shouldRenderJsonWhen() —— 定义何时将异常渲染为 JSON 响应
    //   render()/report() —— 自定义特定异常的渲染与报告
    ->withExceptions(function (Exceptions $exceptions) {
        // API 路由（api/*）或客户端期望 JSON（Accept: application/json）时，
        // 将异常渲染为 JSON 格式（而非 HTML 错误页面），便于前后端分离场景。
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })

    // ===== 区块：创建 Application 实例 =====
    // create() 结束链式调用，返回配置完成的 Application 实例。
    // 该实例随后由 public/index.php 调用 handleRequest() 处理 HTTP 请求，
    // 或由 Artisan 内核处理控制台命令。
    ->create();
