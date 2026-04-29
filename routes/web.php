<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

$healthPath = config('bloom.health_path', '/health/bloom');

Route::get($healthPath, function () {
    try {
        // Quick check if RedisBloom is responsive
        Redis::command('BF.RESERVE', ['__health_check__', 0.01, 1]);
        Redis::del('__health_check__');
        
        return response()->json([
            'status' => 'ok',
            'redisbloom' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'fail',
            'redisbloom' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
})->name('bloom.health');
