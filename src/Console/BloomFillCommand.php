<?php

namespace Iperamuna\LaravelRedisBloom\Console;

use Illuminate\Console\Command;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

class BloomFillCommand extends Command
{
    protected $signature = 'bloom:fill
        {filter : Bloom filter name (defined in config)}
        {model : Eloquent model class}
        {column : Column to index}
        {--chunk=1000 : Chunk size for processing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Fill a Bloom filter from a database column (safe chunked loader)';

    public function handle()
    {
        $this->info('====================================');
        $this->info(' Bloom Filter Bulk Loader');
        $this->info('====================================');

        $filter = $this->argument('filter');
        $model = $this->argument('model');
        $column = $this->argument('column');
        $chunk = (int) $this->option('chunk');

        $this->line('');
        $this->info("Filter : {$filter}");
        $this->info("Model  : {$model}");
        $this->info("Column : {$column}");
        $this->info("Chunk  : {$chunk}");
        $this->line('');

        if (! class_exists($model)) {
            $this->error("Model [$model] not found.");

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("This will load data into Redis Bloom filter [$filter]. Continue?")) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $this->info('Starting ingestion...');
        $total = $model::query()->count();
        $this->withProgressBar($total, function ($bar) use ($model, $column, $filter, $chunk) {
            $model::query()->select($column)->chunk($chunk, function ($results) use ($bar, $filter, $column) {
                foreach ($results as $record) {
                    $value = $record->{$column};
                    if ($value) {
                        Bloom::filter($filter)->add((string) $value);
                    }
                }
                $bar->advance($results->count());
            });
        });

        $this->line('');
        $this->info('====================================');
        $this->info(" DONE - Total inserted: {$total}");
        $this->info('====================================');

        return self::SUCCESS;
    }
}
