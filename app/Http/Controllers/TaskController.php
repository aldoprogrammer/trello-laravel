<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function statuses(): JsonResponse
    {
        $payload = collect(Task::STATUSES)->map(fn ($status) => [
            'value' => $status,
            'label' => str_replace('_', ' ', ucfirst($status)),
        ])->values();

        return ApiResponse::success($payload, 'Task statuses');
    }

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        if ($project->user_id !== $request->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $tasks = $this->taskService->listForProject($project, $perPage);

        $message = $tasks->isEmpty() ? 'No tasks found' : 'Tasks fetched';

        return ApiResponse::success($tasks, $message);
    }

    public function store(TaskStoreRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $task = $this->taskService->create($project, $request->user(), $request->validated());

        return ApiResponse::success($task, 'Task created', 201);
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        if ($project->user_id !== request()->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        if ($task->project_id !== $project->id) {
            return ApiResponse::error('Task not found', 404);
        }

        $this->authorize('view', $task);

        return ApiResponse::success($task, 'Task detail');
    }

    public function update(TaskUpdateRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        if ($project->user_id !== $request->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        if ($task->project_id !== $project->id) {
            return ApiResponse::error('Task not found', 404);
        }

        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return ApiResponse::success($task, 'Task updated');
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        if ($project->user_id !== request()->user()->id) {
            return ApiResponse::error('Project not found', 404);
        }

        if ($task->project_id !== $project->id) {
            return ApiResponse::error('Task not found', 404);
        }

        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return ApiResponse::success(null, 'Task deleted');
    }
}
