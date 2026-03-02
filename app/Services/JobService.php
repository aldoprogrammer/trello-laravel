<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class JobService
{
    private const SUMMARY_TTL_SECONDS = 3600;

    public function listJobs(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        if ($search) {
            // Gunakan Laravel Scout untuk Fuzzy Search
            return Job::search($search)
                ->query(fn ($query) => $query->with('company'))
                ->paginate($perPage)
                ->withQueryString();
        }

        // Default query jika tidak ada search
        return Job::query()
            ->latest()
            ->when($filters['location'] ?? null, function ($query, $location) {
                $query->where('location', 'like', "%{$location}%");
            })
            ->with('company')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createJob(array $data): Job
    {
        return Job::create($data);
    }

    public function getSummaryFromCacheOrDatabase(int $id): array
    {
        $cacheKey = $this->summaryCacheKey($id);
        $cachedSummary = Cache::get($cacheKey);

        if (is_string($cachedSummary) && trim($cachedSummary) !== '') {
            return [
                'summary' => $cachedSummary,
                'source' => 'cache',
            ];
        }

        $job = Job::findOrFail($id);
        $dbSummary = is_string($job->summary) ? trim($job->summary) : '';

        if ($dbSummary !== '') {
            Cache::put($cacheKey, $dbSummary, self::SUMMARY_TTL_SECONDS);

            return [
                'summary' => $dbSummary,
                'source' => 'database',
            ];
        }

        return [
            'summary' => null,
            'source' => null,
        ];
    }

    public function summaryCacheKey(int $id): string
    {
        return "job_summary_{$id}";
    }
}
