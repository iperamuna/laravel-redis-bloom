<?php

use Illuminate\Support\Facades\Route;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;
use Iperamuna\LaravelRedisBloom\Middleware\BloomMiddleware;

beforeEach(function () {
    // Register a test route with the middleware
    Route::post('/test-middleware', function () {
        return response()->json(['message' => 'success']);
    })->middleware(BloomMiddleware::class.':emails,email');

    config(['bloom.filters' => ['emails' => 'bf:emails']]);
});

it('passes when value does not exist in bloom', function () {
    // Mock Bloom::filter('emails')->exists('test@example.com') to return false
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(false);

    Bloom::shouldReceive('filter')->with('emails')->andReturn($mock);

    $this->postJson('/test-middleware', ['email' => 'test@example.com'])
        ->assertStatus(200)
        ->assertJson(['message' => 'success']);
});

it('fails when value exists in bloom', function () {
    // Mock Bloom::filter('emails')->exists('test@example.com') to return true
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(true);

    Bloom::shouldReceive('filter')->with('emails')->andReturn($mock);

    $this->postJson('/test-middleware', ['email' => 'test@example.com'])
        ->assertStatus(409)
        ->assertJson(['message' => 'Possible duplicate detected (Bloom filter match).']);
});
