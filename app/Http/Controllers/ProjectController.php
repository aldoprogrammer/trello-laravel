<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Requests\Tasks\BulkStoreTaskRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}



    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $user = $request->user();

        // Default listing: database query scoped to authenticated user.
        if ($search === '') {
            $projects = $this->projectService->listForUser($user, $perPage);

            return response()->json($projects);
        }

        // Search listing: use Scout, but still scope results to current user.
        $projects = Project::search($search)
            ->query(fn ($query) => $query->where('user_id', $user->id))
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($projects);
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

    public function destroy(Project $project)
    {
        // Cek akses user (Policy)
        $this->authorize('delete', $project);

        // Mulai transaksi
        DB::transaction(function () use ($project) {
            // 1. Hapus semua tasks terkait dulu
            $project->tasks()->delete();

            // 2. Hapus project-nya
            $project->delete();
        });

        return response()->json(['message' => 'Project and its tasks deleted successfully.']);
    }
    public function bulkStoreTasks(BulkStoreTaskRequest $request, Project $project): JsonResponse
    {
        // Authorization is now handled inside the BulkStoreTaskRequest
        $this->projectService->bulkCreateTasks($project, $request->validated()['tasks']);

        return ApiResponse::success(null, 'Bulk tasks created successfully');
    }
}
