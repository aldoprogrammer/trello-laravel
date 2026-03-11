<?php

namespace App\Jobs;

use App\Models\Company;
use App\Mail\CompanyWelcomeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCompanyWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Tentukan jumlah percobaan kalau gagal
    public $tries = 3;

    public function __construct(protected Company $company) {}

    public function handle(): void
    {
        // 1. Logika kirim email beneran
        // Kita kirim ke email admin atau email company (asumsi ada field email)
        Mail::to('admin@trello-laravel.com')->send(new CompanyWelcomeMail($this->company));

        // 2. Tetap kasih logger buat bukti di storage/logs/laravel.log
        logger("Job Success: Welcome email sent for Company ID: {$this->company->id} - Name: {$this->company->name}");
    }

    public function failed(\Throwable $exception): void
    {
        logger("Job Failed: Could not send email for {$this->company->name}. Error: " . $exception->getMessage());
    }
}
