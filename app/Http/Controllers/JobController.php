<?php

namespace App\Http\Controllers;

use App\Jobs\SummarizeJob;
use App\Http\Requests\StoreJobRequest;
use App\Http\Resources\JobResource;
use App\Services\JobService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(protected JobService $jobService) {}

    public function index(Request $request)
    {
        // Mengambil filter dari query string
        $filters = $request->only(['title', 'location', 'search']);
        $perPage = min((int) $request->query('per_page', 15), 100);

        // Memanggil logic search di service
        $jobs = $this->jobService->listJobs($filters, $perPage);

        return JobResource::collection($jobs);
    }

    public function store(StoreJobRequest $request)
    {
        $job = $this->jobService->createJob($request->validated());

        return (new JobResource($job))
            ->response()
            ->setStatusCode(201);
    }

    public function summarize(int $id)
    {
        $resolved = $this->jobService->getSummaryFromCacheOrDatabase($id);
        if (is_string($resolved['summary'])) {
            return response()->json([
                'message' => "Summary already available ({$resolved['source']}).",
                'summary' => $resolved['summary'],
            ]);
        }

        SummarizeJob::dispatchSync($id);
        $resolved = $this->jobService->getSummaryFromCacheOrDatabase($id);

        return response()->json([
            'message' => 'Summary generated.',
            'summary' => $resolved['summary'],
        ]);
    }

    public function summary(int $id)
    {
        $resolved = $this->jobService->getSummaryFromCacheOrDatabase($id);

        return response()->json([
            'summary' => $resolved['summary'],
        ]);
    }
}
