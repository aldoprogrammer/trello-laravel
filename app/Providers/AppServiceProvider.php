<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Task;
use App\Observers\TaskObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Contracts\AIServiceInterface::class, \App\Services\GeminiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
            ];
        });
        // Daftarkan Rate Limiter untuk Gemini
        RateLimiter::for('gemini-api', function (object $job) {
            // Batasi 5 request per menit untuk menjaga kestabilan API
            return Limit::perMinute(5)->by('gemini-ai-key');
        });
        Task::observe(TaskObserver::class);
    }




}
