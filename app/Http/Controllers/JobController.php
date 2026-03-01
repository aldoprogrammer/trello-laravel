<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Http\Resources\JobResource;
use App\Services\JobService;

class JobController extends Controller {
    public function __construct(protected JobService $jobService) {}

    public function store(StoreJobRequest $request) {
        $job = $this->jobService->createJob($request->validated());
        return new JobResource($job);
    }
}
