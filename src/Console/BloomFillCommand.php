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
        $this->line('');

        $count = 0;

        $model::query()
            ->select($column)
            ->chunk($chunk, function ($rows) use ($filter, $column, &$count) {
                foreach ($rows as $row) {
                    Bloom::filter($filter)->add($row->$column);
                    $count++;
                }

                $this->info("Processed: {$count}");
            });

        $this->line('');
        $this->info('====================================');
        $this->info(" DONE - Total inserted: {$count}");
        $this->info('====================================');

        return self::SUCCESS;
    }
}
