<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Companies\UpdateCompanyAction;
use App\Http\Resources\CompanyResource;
use App\Actions\Companies\DeleteCompanyAction;
use App\Actions\Companies\ListCompaniesAction; // Tambah ini
use App\Http\Requests\Companies\UpdateCompanyRequest; // Import ini
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function index(Request $request, ListCompaniesAction $action): AnonymousResourceCollection
    {
        // Tambahin 'name' di sini biar ditangkep sama Action
        $filters = $request->only(['search', 'name']);
        $perPage = min((int) $request->query('per_page', 15), 100);

        $companies = $action->execute($filters, $perPage);

        return CompanyResource::collection($companies);
    }
    public function show(Company $company): JsonResponse
    {
        return response()->json($company);
    }

    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): JsonResponse
    {
        $company = $action->execute($request->validated());
        return response()->json(['message' => 'Success', 'data' => $company], 201);
    }

public function update(UpdateCompanyRequest $request, Company $company, UpdateCompanyAction $action): JsonResponse
{
    $updated = $action->execute($company, $request->validated());

    return response()->json([
        'message' => 'Updated',
        'data' => new CompanyResource($updated)
    ]);
}

    public function destroy(Company $company, DeleteCompanyAction $action): JsonResponse
    {
        $action->execute($company);
        return response()->json(['message' => 'Deleted']);
    }
}
