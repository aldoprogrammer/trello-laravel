<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis; // <-- Tambahkan ini
use Illuminate\Support\Str; // <-- Tambahkan ini
use Illuminate\Support\Facades\Cache; // Tambahkan ini (PENTING)
use Illuminate\Support\Facades\Log;   // Tambahan (opsional untuk logging)

class RedisNotificationWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:redis-notification-worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Service B is listening for job notifications...");

        // Subscribe ke channel Redis
        Redis::subscribe(['job-notifications'], function ($message) {
            $data = json_decode($message);
            $key = $data->idempotency_key;

            // SENIOR TOUCH: Idempotency Check
            // Cek apakah key ini sudah pernah diproses di Redis cache
            if (Cache::has("processed_event:$key")) {
                $this->warn("Duplicate event detected for Key: $key. Skipping...");
                return;
            }

            // Simulasi Logika Bisnis (Kirim Email/Slack)
            $this->line(" [x] Processing Job: {$data->title}");
            $this->info(" [v] Notification sent for Job ID: {$data->job_id}");

            // Tandai sebagai sudah diproses (biar nggak dobel kalau ada retry)
            Cache::put("processed_event:$key", true, now()->addHours(24));
        });
    }
}
