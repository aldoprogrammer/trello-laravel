<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

Route::get('/health', function () {
    try {
        // 1. Cek Database
        DB::connection()->getPdo();

        // 2. Cek Redis
        Redis::connection()->ping();

        return response()->json([
            'status' => 'UP',
            'services' => [
                'database' => 'OK',
                'redis' => 'OK',
            ],
            'timestamp' => now()->toIso8601String()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'DOWN',
            'error' => 'One or more services are unavailable',
            'message' => $e->getMessage(),
        ], 500); // K8s butuh 500 buat restart pod!
    }
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Project Routes
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

    // Bulk Task Route (Pindahkan ke sini)
    Route::post('projects/{project}/tasks/bulk', [ProjectController::class, 'bulkStoreTasks']);

    // Task Routes
    Route::get('projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show']);
    Route::put('projects/{project}/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('projects/{project}/tasks/{task}', [TaskController::class, 'destroy']);

    Route::get('statuses', [TaskController::class, 'statuses']);

    Route::apiResource('companies', CompanyController::class);

    // Job Routes
    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::post('/jobs/{id}/summarize', [JobController::class, 'summarize']);
    Route::get('/jobs/{id}/summary', [JobController::class, 'summary']);
});

Route::get('/test-job', function () {
    $project = \App\Models\Project::first();
    \App\Jobs\UpdateProjectProgress::dispatch($project);
    return "Job dikirim!";
});

Route::post('/tasks/bulk', [TaskController::class, 'bulkStore']);
