<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class CreateTaskAction
{
    /**
     * Execute the action to create a task for a project.
     */
    public function execute(Project $project, array $data): Task
    {
        $userId = Auth::id();

        return $project->tasks()->create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'pending',
            'user_id'     => $userId,
        ]);
    }
}
