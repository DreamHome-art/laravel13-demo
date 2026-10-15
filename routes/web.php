<?php

use App\Http\Controllers\BloomFilterController;
use App\Http\Controllers\CacheTestController;
use App\Http\Controllers\RedisTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('redis')->group(function () {
    Route::get('/setMsg', [RedisTestController::class, 'setMsg']);
    Route::get('/pipelineMsg', [RedisTestController::class, 'pipelineMsg']);
    Route::get('/multiMsg', [RedisTestController::class, 'multiMsg']);
});

Route::prefix('cache')->group(function () {
    Route::get('/cacheMsg', [CacheTestController::class, 'CacheMsg']);
});

// 布隆过滤器路由组
// 提供 HTTP API 入口，对接 BloomFilterService 服务
Route::prefix('bloom')->group(function () {
    Route::any('/add', [BloomFilterController::class, 'add']);            // 添加单个元素
    Route::post('/addBatch', [BloomFilterController::class, 'addBatch']);   // 批量添加元素
    Route::get('/exists', [BloomFilterController::class, 'exists']);        // 查询单个元素
    Route::post('/existsBatch', [BloomFilterController::class, 'existsBatch']); // 批量查询元素
    Route::get('/count', [BloomFilterController::class, 'count']);         // 统计近似元素数量
    Route::delete('/clear', [BloomFilterController::class, 'clear']);       // 清空过滤器
});
