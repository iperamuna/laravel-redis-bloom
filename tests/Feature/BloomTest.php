<?php

use Iperamuna\LaravelRedisBloom\BloomManager;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

it('can access the bloom facade', function () {
    expect(Bloom::getFacadeRoot())->toBeInstanceOf(BloomManager::class);
});

it('throws exception when filter is not defined', function () {
    Bloom::filter('non-existent');
})->throws(InvalidArgumentException::class);
