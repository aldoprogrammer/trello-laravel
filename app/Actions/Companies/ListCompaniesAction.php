<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListCompaniesAction
{
    public function execute(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $name = $filters['name'] ?? null;

        if ($search) {
            return Company::search($search)->paginate($perPage)->withQueryString();
        }

        $page = request()->get('page', 1);
        $cacheKey = "companies_list_p{$page}_{$perPage}_" . ($name ?? 'all');

        return Cache::remember($cacheKey, 3600, function () use ($name, $perPage) {
            return Company::query()
                ->latest()
                ->when($name, function ($query, $name) {
                    $query->where('name', 'like', "%{$name}%");
                })
                ->paginate($perPage)
                ->withQueryString();
        });
    }
}
