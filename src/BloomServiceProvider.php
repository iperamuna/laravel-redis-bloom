<?php

namespace Iperamuna\LaravelRedisBloom;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Iperamuna\LaravelRedisBloom\Console\BloomDoctorCommand;
use Iperamuna\LaravelRedisBloom\Console\BloomFillCommand;
use Iperamuna\LaravelRedisBloom\Console\BloomStatsCommand;
use Iperamuna\LaravelRedisBloom\Middleware\BloomMiddleware;

class BloomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bloom.php',
            'bloom'
        );

        $this->app->singleton(BloomManager::class, function ($app) {
            return new BloomManager($app['config']['bloom']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-redis-bloom');

        if (config('bloom.health_enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bloom.php' => config_path('bloom.php'),
            ], 'bloom-config');

            $this->commands([
                BloomFillCommand::class,
                BloomStatsCommand::class,
                BloomDoctorCommand::class,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MIDDLEWARE REGISTRATION
        |--------------------------------------------------------------------------
        */
        $this->app['router']->aliasMiddleware(
            'bloom',
            BloomMiddleware::class
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDATION EXTENSION
        |--------------------------------------------------------------------------
        |
        | Usage:
        |   'email' => 'bloom:emails'
        |
        */
        Validator::extend('bloom', function ($attribute, $value, $parameters) {
            $filter = $parameters[0] ?? null;
            if (! $filter) {
                return true;
            }

            try {
                $bloom = app(BloomManager::class)->filter($filter);

                // Fast path: definitely not exists
                if (! $bloom->exists($value)) {
                    return true;
                }
            } catch (\Throwable $e) {
                return true; // Fail safe if RedisBloom is missing
            }

            // Bloom says "maybe exists" → fail validation
            return false;
        }, 'The :attribute might already exist (Bloom filter match).');
    }
}
