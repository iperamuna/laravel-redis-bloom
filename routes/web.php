<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

$healthPath = config('bloom.health_path', '/health/bloom');

Route::get($healthPath, function () {
    try {
        // Quick check if RedisBloom is responsive
        $prefix = config('database.redis.options.prefix', '');
        if (empty($prefix)) {
            $connection = config('bloom.redis_connection', 'default');
            $prefix = config("database.redis.{$connection}.prefix", '');
        }

        Redis::executeRaw(['BF.RESERVE', $prefix.'__health_check__', '0.01', '1']);
        Redis::del('__health_check__');

        return response()->json([
            'status' => 'ok',
            'redisbloom' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'fail',
            'redisbloom' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
})->name('bloom.health');
