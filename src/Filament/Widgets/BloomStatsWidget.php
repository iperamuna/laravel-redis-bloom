<?php

namespace Iperamuna\LaravelRedisBloom\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Redis;

class BloomStatsWidget extends Widget
{
    public ?string $filter = 'emails';

    protected static string $view = 'laravel-redis-bloom::filament.widgets.bloom-stats';

    protected function getViewData(): array
    {
        $configFilters = config('bloom.filters', []);
        $baseKey = $configFilters[$this->filter] ?? "bf:{$this->filter}";
        $metricsBase = "{$baseKey}:metrics";

        return [
            'filterName' => $this->filter,
            'hits' => Redis::get("{$metricsBase}:hit") ?? 0,
            'misses' => Redis::get("{$metricsBase}:miss") ?? 0,
            'false' => Redis::get("{$metricsBase}:false_positive") ?? 0,
        ];
    }
}
