<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListCompaniesAction
{
    public function execute(array $filters, int $perPage): LengthAwarePaginator
    {
        // Gunakan 'search' atau 'name' sebagai input pencarian Scout
        $searchTerm = $filters['search'] ?? $filters['name'] ?? null;

        if ($searchTerm) {
            // MEILISEARCH ACTION (High Performance & Fuzzy Search)
            return Company::search($searchTerm)
                ->paginate($perPage)
                ->withQueryString();
        }

        // DEFAULT ACTION (Cached List)
        $page = request()->get('page', 1);
        $cacheKey = "companies_list_p{$page}_{$perPage}";

        return Cache::remember($cacheKey, 3600, function () use ($perPage) {
            return Company::query()
                ->latest()
                ->paginate($perPage)
                ->withQueryString();
        });
    }
}
