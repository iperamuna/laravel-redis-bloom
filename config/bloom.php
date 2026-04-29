<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Bloom Filter Settings
    |--------------------------------------------------------------------------
    |
    | error_rate: The desired probability for false positives (0.01 = 1%).
    | capacity:   The number of items to reserve for in the initial filter.
    |
    */
    'error_rate' => 0.01,
    'capacity' => 1000000,
    'redis_connection' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Bloom Filters Definition
    |--------------------------------------------------------------------------
    |
    | Define your bloom filters here. The key is the name used in code,
    | and the value is the base Redis key for the filter.
    |
    */
    'filters' => [
        'emails' => 'bf:emails',
        'phones' => 'bf:phones',
        'ips' => 'bf:ips',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Rotation Settings
    |--------------------------------------------------------------------------
    |
    | rotation_keep_versions: How many old versions of the filter to keep
    | when the capacity is reached and a new version is created.
    |
    */
    'rotation_keep_versions' => 3,

    /*
    |--------------------------------------------------------------------------
    | Metrics Tracking
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will track hits, misses, and false positives
    | for each filter in Redis.
    |
    */
    'tracking' => true,

    /*
    |--------------------------------------------------------------------------
    | Graceful Fallback
    |--------------------------------------------------------------------------
    |
    | If set to true, the package will fail gracefully (returning false/null)
    | instead of throwing an exception when RedisBloom is not available.
    |
    */
    'bloom_fallback' => false,

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    |
    | health_enabled: Whether to register the health check route.
    | health_path:    The URL path for the health check.
    |
    */
    'health_enabled' => true,
    'health_path' => '/health/bloom',
];
