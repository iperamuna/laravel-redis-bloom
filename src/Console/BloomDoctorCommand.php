<?php

namespace Iperamuna\LaravelRedisBloom\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class BloomDoctorCommand extends Command
{
    protected $signature = 'bloom:doctor 
                            {--fix : Attempt automatic fixes where possible}
                            {--json : Output machine-readable diagnostics}';

    protected $description = 'Diagnose RedisBloom + Laravel Bloom installation health';

    public function handle(): int
    {
        $report = [
            'redis' => $this->checkRedisRaw(),
            'redisbloom' => $this->checkBloomRaw(),
            'config' => $this->checkConfigRaw(),
            'os' => PHP_OS_FAMILY,
            'arch' => php_uname('m'),
        ];

        if ($this->option('json')) {
            return $this->outputJson($report);
        }

        $this->info('======================================');
        $this->info(' Bloom Doctor - System Diagnostics');
        $this->info('======================================');
        $this->line('');

        $this->displayEnvironment();
        $this->displayRedis($report['redis']);
        $this->displayRedisBloom($report['redisbloom']);
        $this->displayConfig($report['config']);

        if ($this->option('fix')) {
            $this->applyFixes($report);
        }

        $this->line('');
        $this->info('======================================');
        $this->info(' Diagnosis Complete');
        $this->info('======================================');

        return self::SUCCESS;
    }

    protected function checkRedisRaw(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function checkBloomRaw(): bool
    {
        try {
            Redis::command('BF.RESERVE', ['__doctor_test__', 0.01, 1]);
            Redis::del('__doctor_test__');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function checkConfigRaw(): bool
    {
        return config('bloom') !== null;
    }

    protected function displayEnvironment(): void
    {
        $this->info('🖥 Environment');
        $this->line(' OS   : '.PHP_OS_FAMILY);
        $this->line(' ARCH : '.php_uname('m'));
        $this->line('');
    }

    protected function displayRedis(bool $reachable): void
    {
        $this->info('🔌 Redis Connection');
        if ($reachable) {
            $this->info(' ✔ Redis is reachable');
        } else {
            $this->error(' ✖ Redis is NOT reachable');
            $this->line(' Fix: Check redis-server or config/database.php');
        }
        $this->line('');
    }

    protected function displayRedisBloom(bool $available): void
    {
        $this->info('🧠 RedisBloom Module');
        if ($available) {
            $this->info(' ✔ RedisBloom is installed and working');
        } else {
            $this->error(' ✖ RedisBloom NOT available');
            $this->line('');
            $this->line(' Suggested fixes:');
            $this->line('  • brew install redis-stack');
            $this->line('  • redis-stack-server');
            $this->line('  • Docker: redis/redis-stack-server');
        }
        $this->line('');
    }

    protected function displayConfig(bool $loaded): void
    {
        $this->info('⚙️ Laravel Bloom Config');
        if ($loaded) {
            $this->info(' ✔ Config loaded');
            $this->line(' Filters defined: '.count(config('bloom.filters', [])));
        } else {
            $this->error(' ✖ config/bloom.php missing');
            $this->line(' Fix: php artisan vendor:publish --tag=bloom-config');
        }
        $this->line('');
    }

    protected function applyFixes(array $report): void
    {
        $this->info('🔧 Attempting auto-fixes...');

        if (! $report['redis']) {
            $this->line('→ Recommendation: Start Redis using "brew services start redis" or equivalent.');
        }

        if (! $report['redisbloom']) {
            $this->line('→ Recommendation: Install Redis Stack: "brew install redis-stack"');
            $this->line('→ Start: "redis-stack-server"');
        }

        if (! $report['config']) {
            $this->info('→ Publishing config...');
            $this->call('vendor:publish', ['--tag' => 'bloom-config']);
        }
    }

    protected function outputJson(array $report): int
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
