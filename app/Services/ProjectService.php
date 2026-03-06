<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->with('tasks')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $user, array $data): Project
    {
        return $user->projects()->create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->refresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function bulkCreateTasks(Project $project, array $tasks): void
    {
        $formattedTasks = array_map(function ($task) use ($project) {
            return [
                'project_id'  => $project->id,
                'user_id'     => $project->user_id,
                'title'       => $task['title'],
                'description' => $task['description'] ?? null,
                'status'      => 'pending',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }, $tasks);

        Task::upsert(
            $formattedTasks,
            ['id'],
            ['title', 'description', 'status', 'updated_at']
        );
    }
}
