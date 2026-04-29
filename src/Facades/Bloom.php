<?php

namespace Iperamuna\LaravelRedisBloom\Facades;

use Illuminate\Support\Facades\Facade;
use Iperamuna\LaravelRedisBloom\BloomManager;

/**
 * @method static \Iperamuna\LaravelRedisBloom\BloomFilter filter(string $name)
 *
 * @see BloomManager
 */
class Bloom extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BloomManager::class;
    }
}
