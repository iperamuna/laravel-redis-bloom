<?php

namespace Iperamuna\LaravelRedisBloom\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class BloomStatsCommand extends Command
{
    protected $signature = 'bloom:stats {filter? : Filter name (optional)}';

    protected $description = 'Show Bloom filter statistics and health metrics';

    public function handle()
    {
        $filter = $this->argument('filter');

        $this->info('====================================');
        $this->info(' Bloom Filter Stats Dashboard');
        $this->info('====================================');

        if ($filter) {
            $this->showFilter($filter);

            return self::SUCCESS;
        }

        $this->info('Available filters (active in Redis):');
        $this->line('');

        // Auto-discover filters (simple prefix scan)
        $keys = Redis::keys('bf:*:current');

        foreach ($keys as $key) {
            $name = str_replace(['bf:', ':current'], '', $key);
            $this->line(" - {$name}");
        }

        $this->line('');
        $this->comment('Run: php artisan bloom:stats [name]');

        return self::SUCCESS;
    }

    protected function showFilter(string $filter): void
    {
        // Try to find the base key from config first, otherwise guess
        $configFilters = config('bloom.filters', []);
        $baseKey = $configFilters[$filter] ?? "bf:{$filter}";
        $metricsBase = "{$baseKey}:metrics";

        $stats = [
            'hits' => Redis::get("{$metricsBase}:hit") ?? 0,
            'misses' => Redis::get("{$metricsBase}:miss") ?? 0,
            'false_positives' => Redis::get("{$metricsBase}:false_positive") ?? 0,
        ];

        $total = array_sum($stats);

        $this->line('');
        $this->info("Filter: {$filter} ({$baseKey})");
        $this->line('------------------------------------');

        foreach ($stats as $k => $v) {
            $this->line(str_pad($k, 20).": {$v}");
        }

        $this->line('------------------------------------');
        $this->info("Total operations: {$total}");

        if ($total > 0) {
            $fpRate = round(($stats['false_positives'] / $total) * 100, 2);
            $this->info("False positive rate: {$fpRate}%");
        }

        $this->line('');
    }
}
