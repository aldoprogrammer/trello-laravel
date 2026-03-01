<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $projects = $this->projectService->listForUser($request->user(), $perPage);

        $message = $projects->isEmpty() ? 'No projects found' : 'Projects fetched';

        return ApiResponse::success($projects, $message);
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create($request->user(), $request->validated());

        return ApiResponse::success($project, 'Project created', 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        if ($project->user_id !== request()->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        $project->load('tasks');

        return ApiResponse::success($project, 'Project detail');
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update($project, $request->validated());

        return ApiResponse::success($project, 'Project updated');
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        if ($project->user_id !== request()->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        $this->projectService->delete($project);

        return ApiResponse::success(null, 'Project deleted');
    }
}
