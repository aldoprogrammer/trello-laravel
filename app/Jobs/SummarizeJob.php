<?php

namespace App\Jobs;

use App\Models\Job;
use App\Contracts\AIServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\RateLimited;

class SummarizeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 90];
    protected $jobId;

    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    public function middleware(): array
    {
        return [new RateLimited('gemini-api')];
    }

    /**
     * Dependency Injection AIServiceInterface secara otomatis memanggil GeminiService
     * sesuai binding di AppServiceProvider.
     */
    public function handle(AIServiceInterface $aiService): void
    {
        $job = Job::findOrFail($this->jobId);

        try {
            // Logic utama: Panggil Service (Bukan HTTP langsung)
            $prompt = "Summarize this job posting in 2-concise sentences:\nTitle: {$job->title}\nLocation: {$job->location}\nDescription: {$job->description}";
            $summary = $aiService->summarize($prompt);

            // Failover: Jika AI mengembalikan string kosong, gunakan format standar (Mock)
            if (empty($summary)) {
                $summary = sprintf(
                    'Job opening: %s in %s. %s',
                    $job->title,
                    $job->location,
                    trim(substr($job->description, 0, 150)) . '...'
                );
            }

            // Update Database & Cache
            $job->update(['summary' => $summary]);
            Cache::put("job_summary_{$this->jobId}", $summary, 3600);

            Log::info("Summary successful for Job ID: {$this->jobId}");

        } catch (\Exception $e) {
            Log::error("SummarizeJob Error (ID {$this->jobId}): " . $e->getMessage());
            throw $e; // Throw agar job bisa di-retry otomatis
        }
    }
}
