<?php

use Illuminate\Support\Facades\Validator;
use Iperamuna\LaravelRedisBloom\BloomManager;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;
use Iperamuna\LaravelRedisBloom\Rules\BloomRule;

beforeEach(function () {
    config(['bloom.filters' => ['emails' => 'bf:emails']]);
});

it('passes validation when value does not exist in bloom', function () {
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(false);

    Bloom::shouldReceive('filter')->with('emails')->andReturn($mock);

    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => new BloomRule('emails')]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails validation when value exists in bloom', function () {
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(true);

    Bloom::shouldReceive('filter')->with('emails')->andReturn($mock);

    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => new BloomRule('emails')]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('The email might already exist.');
});

it('fails validation with strict mode when value exists', function () {
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(true);

    Bloom::shouldReceive('filter')->with('emails')->andReturn($mock);

    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => new BloomRule('emails', strict: true)]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('The email is already in use (Bloom detected).');
});

it('supports bloom string rule syntax', function () {
    $mock = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomFilter');
    $mock->shouldReceive('exists')->with('test@example.com')->andReturn(true);

    // We need to use app() binding for the validator extension to work in tests
    $this->app->instance(BloomManager::class, $manager = Mockery::mock('Iperamuna\LaravelRedisBloom\BloomManager'));
    $manager->shouldReceive('filter')->with('emails')->andReturn($mock);

    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => 'bloom:emails']
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('The email might already exist (Bloom filter match).');
});
