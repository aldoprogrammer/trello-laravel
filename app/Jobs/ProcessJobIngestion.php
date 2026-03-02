<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessJobIngestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function handle(): void
    {
        Log::info('Queue: Job Ingestion started.');

        // Fetch data dengan error handling
        $response = Http::withoutVerifying()->get('https://jsonplaceholder.typicode.com/posts');

        if ($response->failed()) {
            Log::error('Queue: Failed to fetch external jobs.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("API Error: Unable to fetch external jobs.");
        }

        $externalJobs = $response->json();

        // Ensure Idempotency for Default Company
        $company = Company::firstOrCreate(['name' => 'PT Teknologi Jaya']);

        foreach ($externalJobs as $data) {
            try {
                // ATOMIC PROCESSING: Menggunakan DB Transaction
                DB::transaction(function () use ($data, $company) {

                    // IDEMPOTENCY: updateOrCreate memastikan data tidak duplikat
                    // dan melakukan update jika data sudah ada.
                    Job::updateOrCreate(
                        [
                            'title' => $data['title'],
                            'company_id' => $company->id,
                            'location' => 'Remote'
                        ],
                        [
                            'description' => $data['body'],
                            'updated_at' => now()
                        ]
                    );
                });

            } catch (\Exception $e) {
                // CONTEXTUAL LOGGING: Tracking failure for async monitoring
                Log::error('Queue: Ingestion failed for a specific job record.', [
                    'job_title' => $data['title'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ]);

                // Continue to the next record instead of failing the whole batch
                continue;
            }
        }

        Log::info('Queue: Job Ingestion completed successfully.', [
            'total_processed' => count($externalJobs)
        ]);
    }
}
