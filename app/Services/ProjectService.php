<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
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
}
