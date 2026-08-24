<?php

/*
|--------------------------------------------------------------------------
| 服务提供者配置文件 (bootstrap/providers.php)
|--------------------------------------------------------------------------
|
| 文件用途：声明应用启动时需要加载的服务提供者列表。
| 框架启动过程中，Application 会自动实例化并依次调用每个提供者的
| register() 与 boot() 方法，完成服务绑定、事件监听、中间件注册等初始化工作。
|
| 加载流程：
|   1. 框架基础提供者（由 Illuminate\Foundation\Application 内部注册）
|   2. 本文件数组中的提供者（按数组顺序）
|   3. 包发现（package discover）注册的第三方包提供者（见 composer.json extra.laravel.providers）
|
| 添加自定义提供者：
|   直接将类名追加到本数组即可（无需额外注册步骤）。
|   例如：App\Providers\RouteServiceProvider::class
|
| 相关 Laravel 官方文档：
|   https://laravel.com/docs/13.x/providers
|
*/

use App\Providers\AppServiceProvider;

// 服务提供者类名数组：框架启动时按顺序实例化并调用 register() / boot()
return [
    // 应用级服务提供者：用于应用全局服务绑定、DB 查询监听、
    // Schema 默认字符串长度设置等（具体见 app/Providers/AppServiceProvider.php）
    AppServiceProvider::class,
];
