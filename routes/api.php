<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('statuses', [TaskController::class, 'statuses']);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

    Route::get('projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show']);
    Route::put('projects/{project}/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('projects/{project}/tasks/{task}', [TaskController::class, 'destroy']);
});

Route::get('/jobs', [JobController::class, 'index']);
Route::post('/jobs', [JobController::class, 'store']);
Route::post('/jobs/{id}/summarize', [JobController::class, 'summarize']);
Route::get('/jobs/{id}/summary', [JobController::class, 'summary']);
