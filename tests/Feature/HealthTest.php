<?php

use Illuminate\Support\Facades\Redis;

it('registers the health check route', function () {
    Redis::shouldReceive('executeRaw')->atLeast()->once()->andReturn(true);
    Redis::shouldReceive('del')->atLeast()->once()->andReturn(1);

    $this->get('/health/bloom')->assertStatus(200);
});

it('returns success when redisbloom is available', function () {
    Redis::shouldReceive('executeRaw')
        ->with(Mockery::on(fn ($args) => $args[0] === 'BF.RESERVE' && str_ends_with($args[1], '__health_check__')))
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
    Redis::shouldReceive('executeRaw')
        ->with(Mockery::on(fn ($args) => $args[0] === 'BF.RESERVE' && str_ends_with($args[1], '__health_check__')))
        ->andThrow(new Exception('Redis error'));

    $this->get('/health/bloom')
        ->assertStatus(500)
        ->assertJson([
            'status' => 'fail',
            'redisbloom' => false,
            'error' => 'Redis error',
        ]);
});
