<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Actions\Tasks\CreateTaskAction; // New Action Path
use App\Http\Requests\Tasks\StoreTaskRequest; // New Request Path
use App\Http\Resources\TaskResource;
use App\Actions\Tasks\UpdateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response; // Important: Fixes your error
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{

    public function statuses(): JsonResponse
    {
        $payload = Cache::remember('task_statuses:v1', now()->addDay(), function () {
            return collect(Task::STATUSES)->map(fn($status) => [
                'value' => $status,
                'label' => str_replace('_', ' ', ucfirst($status)),
            ])->values();
        });

        return ApiResponse::success($payload, 'Task statuses');
    }

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        // Langsung ambil dari relasi & paginate
        $tasks = $project->tasks()->paginate($request->query('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function store(
        StoreTaskRequest $request,
        Project $project,
        CreateTaskAction $createTaskAction
    ): TaskResource {

        // At this point, Authorization & Validation are already done by StoreTaskRequest.

        $task = $createTaskAction->execute($project, $request->validated());

        return new TaskResource($task);
    }


    public function show(Project $project, Task $task): TaskResource
    {
        $this->authorize('view', $project);

        // Security check singkat
        abort_if($task->project_id !== $project->id, 404);

        return new TaskResource($task);
    }

    public function update(
        UpdateTaskRequest $request,
        Task $task,
        UpdateTaskAction $updateTaskAction
    ): TaskResource {

        // Authorization is already handled by UpdateTaskRequest
        $updatedTask = $updateTaskAction->execute($task, $request->validated());

        return new TaskResource($updatedTask);
    }

    // DESTROY: Pakai DeleteTaskAction
    public function destroy(Project $project, Task $task, DeleteTaskAction $action): Response
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $action->execute($task);

        return response()->noContent(); // Status 204
    }
    public function bulkStore(Request $request)
{
    // Simulasi data banyak task dari request
    $tasks = [
        ['id' => 1, 'title' => 'Review Job Search', 'status' => 'pending', 'project_id' => 1, 'user_id' => 1],
        ['id' => 2, 'title' => 'Audit Data Pipeline', 'status' => 'done', 'project_id' => 1, 'user_id' => 1],
        ['id' => 3, 'title' => 'Refactor AI SDK', 'status' => 'pending', 'project_id' => 1, 'user_id' => 1],
    ];

    // Menggunakan Upsert (Update or Insert)
    // Parameter 1: Data yang dimasukkan
    // Parameter 2: Kolom unik untuk pengecekan (id)
    // Parameter 3: Kolom yang diupdate jika id sudah ada
    \App\Models\Task::upsert($tasks, ['id'], ['title', 'status']);

    return response()->json(['message' => 'Bulk tasks processed with Atomic Upsert!']);
}
}
