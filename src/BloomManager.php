<?php

namespace Iperamuna\LaravelRedisBloom;

class BloomManager
{
    public function __construct(protected array $config) {}

    public function filter(string $name): BloomFilter
    {
        $filters = $this->config['filters'] ?? [];

        if (! isset($filters[$name])) {
            throw new \InvalidArgumentException("Bloom filter [$name] not defined in config/bloom.php.");
        }

        return new BloomFilter(
            $filters[$name],
            $this->config['error_rate'] ?? 0.01,
            $this->config['capacity'] ?? 1000000,
            $this->config['rotation_keep_versions'] ?? 3,
            $this->config['tracking'] ?? true,
            $this->config['redis_connection'] ?? null
        );
    }
}
