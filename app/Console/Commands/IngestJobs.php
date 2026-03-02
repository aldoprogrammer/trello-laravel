<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessJobIngestion;

class IngestJobs extends Command
{
    protected $signature = 'ingest:jobs';
    protected $description = 'Dispatch job ingestion to Redis';

    public function handle()
    {
        $this->info('Dispatching ingestion to Redis queue...');

        // This triggers the Job above
        ProcessJobIngestion::dispatch();

        $this->info('Job successfully queued! Run "php artisan queue:work" to process.');
    }
}
