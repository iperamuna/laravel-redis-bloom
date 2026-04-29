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
        protected bool $tracking = true
    ) {}

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
            Redis::executeRaw(['BF.RESERVE', '__bloom_check__', '0.001', '1']);
            self::$bloomAvailable = true;
            Redis::del('__bloom_check__');
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
        return (int) (Redis::get($this->versionKey()) ?? 1);
    }

    protected function setVersion(int $v): void
    {
        Redis::set($this->versionKey(), $v);
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
            Redis::incr($this->metricsKey($type));
        }
    }

    protected function ensure(int $v): void
    {
        $key = $this->key($v);

        try {
            Redis::executeRaw(['BF.INFO', $key]);
        } catch (\Exception) {
            Redis::executeRaw([
                'BF.RESERVE',
                $key,
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
            $info = Redis::executeRaw(['BF.INFO', $key]);
            $info = collect($info)->chunk(2)->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
            $count = (int) ($info['Number of items inserted'] ?? 0);
        } catch (\Exception) {
            $count = 0;
        }

        if ($count >= $this->capacity) {
            $v++;
            $this->setVersion($v);

            $newKey = $this->key($v);

            Redis::executeRaw([
                'BF.RESERVE',
                $newKey,
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
            Redis::del($this->key($i));
        }
    }

    public function add(string $value): void
    {
        $this->ensureBloomAvailable();
        if (! self::$bloomAvailable) {
            return;
        }

        $key = $this->rotateIfNeeded();
        Redis::executeRaw(['BF.ADD', $key, $value]);
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
                $result = Redis::executeRaw(['BF.EXISTS', $this->key($i), $value]);

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
            Redis::executeRaw(['BF.ADD', $key, $value]);
        }
    }

    public function stats(): array
    {
        return [
            'hits' => (int) Redis::get($this->metricsKey('hit')),
            'misses' => (int) Redis::get($this->metricsKey('miss')),
            'false_positives' => (int) Redis::get($this->metricsKey('false_positive')),
        ];
    }
}
