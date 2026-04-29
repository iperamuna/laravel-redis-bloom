<?php

use Illuminate\Support\Facades\Redis;

it('registers the health check route', function () {
    Redis::shouldReceive('command')->andReturn(true);
    Redis::shouldReceive('del')->andReturn(1);

    $this->get('/health/bloom')->assertStatus(200);
});

it('returns success when redisbloom is available', function () {
    Redis::shouldReceive('command')
        ->with('BF.RESERVE', ['__health_check__', 0.01, 1])
        ->andReturn(true);
    
    Redis::shouldReceive('del')
        ->with('__health_check__')
        ->andReturn(1);

    $this->get('/health/bloom')
        ->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'redisbloom' => true,
        ]);
});

it('returns failure when redisbloom is not available', function () {
    Redis::shouldReceive('command')
        ->with('BF.RESERVE', ['__health_check__', 0.01, 1])
        ->andThrow(new Exception('Redis error'));

    $this->get('/health/bloom')
        ->assertStatus(500)
        ->assertJson([
            'status' => 'fail',
            'redisbloom' => false,
            'error' => 'Redis error',
        ]);
});
