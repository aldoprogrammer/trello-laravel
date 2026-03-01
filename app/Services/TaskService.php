<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function listForProject(Project $project, int $perPage = 15): LengthAwarePaginator
    {
        return $project->tasks()
            ->where('user_id', $project->user_id)
            ->latest()
            ->paginate($perPage);
    }

    public function create(Project $project, User $user, array $data): Task
    {
        $payload = array_merge([
            'status' => Task::STATUS_PENDING,
        ], $data, [
            'user_id' => $user->id,
        ]);

        return $project->tasks()->create($payload);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
