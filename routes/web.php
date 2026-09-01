<?php

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
