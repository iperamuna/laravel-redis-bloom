<?php

namespace Iperamuna\LaravelRedisBloom;

use Closure;
use Illuminate\Support\Facades\Redis;
use Iperamuna\LaravelRedisBloom\Exceptions\RedisBloomNotAvailableException;

class BloomFilter
{
    protected static bool $bloomChecked = false;

    protected static bool $bloomAvailable = false;

    public function __construct(
        protected string $baseKey,
        protected float $errorRate = 0.01,
        protected int $capacity = 1000000,
        protected int $keepVersions = 3,
        protected bool $tracking = true,
        protected ?string $connection = null
    ) {
        $this->connection ??= config('bloom.redis_connection', 'default');
    }

    protected function redis()
    {
        return Redis::connection($this->connection);
    }

    protected function getPrefix(): string
    {
        // Try global options first
        $prefix = config('database.redis.options.prefix', '');

        // Then connection specific
        if (empty($prefix)) {
            $prefix = config("database.redis.{$this->connection}.prefix", '');
        }

        return $prefix;
    }

    protected function prefixed(string $key): string
    {
        return $this->getPrefix().$key;
    }

    protected function ensureBloomAvailable(): void
    {
        if (self::$bloomChecked) {
            if (! self::$bloomAvailable) {
                $this->handleMissingModule();
            }

            return;
        }

        self::$bloomChecked = true;
        try {
            // Lightweight check using BF.RESERVE on a temporary key
            $this->redis()->executeRaw(['BF.RESERVE', $this->prefixed('__bloom_check__'), '0.001', '1']);
            self::$bloomAvailable = true;
            $this->redis()->del('__bloom_check__');
        } catch (\Throwable $e) {
            self::$bloomAvailable = false;
            $this->handleMissingModule();
        }
    }

    protected function handleMissingModule(): void
    {
        if (config('bloom.bloom_fallback', false)) {
            return;
        }

        throw RedisBloomNotAvailableException::make();
    }

    protected function versionKey(): string
    {
        return "{$this->baseKey}:current";
    }

    protected function currentVersion(): int
    {
        return (int) ($this->redis()->get($this->versionKey()) ?? 1);
    }

    protected function setVersion(int $v): void
    {
        $this->redis()->set($this->versionKey(), $v);
    }

    protected function key(int $v): string
    {
        return "{$this->baseKey}:v{$v}";
    }

    protected function metricsKey(string $type): string
    {
        return "{$this->baseKey}:metrics:{$type}";
    }

    protected function track(string $type): void
    {
        if ($this->tracking) {
            $this->redis()->incr($this->metricsKey($type));
        }
    }

    protected function ensure(int $v): void
    {
        $key = $this->key($v);

        try {
            $this->redis()->executeRaw(['BF.INFO', $this->prefixed($key)]);
        } catch (\Exception) {
            $this->redis()->executeRaw([
                'BF.RESERVE',
                $this->prefixed($key),
                (string) $this->errorRate,
                (string) $this->capacity,
            ]);
        }
    }

    protected function rotateIfNeeded(): string
    {
        $v = $this->currentVersion();
        $key = $this->key($v);

        $this->ensure($v);

        try {
            $info = $this->redis()->executeRaw(['BF.INFO', $this->prefixed($key)]);
            $info = collect($info)->chunk(2)->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
            $count = (int) ($info['Number of items inserted'] ?? 0);
        } catch (\Exception) {
            $count = 0;
        }

        if ($count >= $this->capacity) {
            $v++;
            $this->setVersion($v);

            $newKey = $this->key($v);

            $this->redis()->executeRaw([
                'BF.RESERVE',
                $this->prefixed($newKey),
                (string) $this->errorRate,
                (string) $this->capacity,
            ]);

            $this->cleanup($v);

            return $newKey;
        }

        return $key;
    }

    protected function cleanup(int $current): void
    {
        $min = max(1, $current - $this->keepVersions);
 
        for ($i = 1; $i < $min; $i++) {
            $this->redis()->del($this->key($i));
        }
    }

    public function add(string $value): void
    {
        $this->ensureBloomAvailable();
        if (! self::$bloomAvailable) {
            return;
        }

        $key = $this->rotateIfNeeded();
        $this->redis()->executeRaw(['BF.ADD', $this->prefixed($key), $value]);
    }

    public function exists(string $value): bool
    {
        $this->ensureBloomAvailable();
        if (! self::$bloomAvailable) {
            return false;
        }

        $current = $this->currentVersion();

        for ($i = $current; $i >= 1; $i--) {
            try {
                $result = $this->redis()->executeRaw(['BF.EXISTS', $this->prefixed($this->key($i)), $value]);

                if ($result) {
                    $this->track('hit');

                    return true;
                }
            } catch (\Exception) {
                continue;
            }
        }

        $this->track('miss');

        return false;
    }

    public function check(string $value, Closure $fallback): bool
    {
        if (! $this->exists($value)) {
            return false;
        }

        // Bloom says maybe exists → verify truth
        $actual = $fallback($value);

        if (! $actual) {
            $this->track('false_positive');
        }

        return $actual;
    }

    public function addMany(array $values): void
    {
        $this->ensureBloomAvailable();
        if (! self::$bloomAvailable) {
            return;
        }

        $key = $this->rotateIfNeeded();

        foreach ($values as $value) {
            $this->redis()->executeRaw(['BF.ADD', $this->prefixed($key), $value]);
        }
    }

    public function stats(): array
    {
        return [
            'hits' => (int) $this->redis()->get($this->metricsKey('hit')),
            'misses' => (int) $this->redis()->get($this->metricsKey('miss')),
            'false_positives' => (int) $this->redis()->get($this->metricsKey('false_positive')),
        ];
    }
}
