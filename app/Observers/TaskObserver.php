<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    public function created(Task $task): void
    {
        Log::info("Task created: ID {$task->id}");
        // Dispatch saat ada task baru agar total pembagi dihitung ulang
        \App\Jobs\UpdateProjectProgress::dispatch($task->project);
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('status')) {
            \App\Jobs\UpdateProjectProgress::dispatch($task->project);
        }
    }

    public function deleted(Task $task): void
    {
        Log::warning("Task deleted: ID {$task->id}");
        // Dispatch saat task dihapus agar persentase update
        \App\Jobs\UpdateProjectProgress::dispatch($task->project);
    }

    public function restored(Task $task): void
    {
        Log::info("Task restored: ID {$task->id}");
    }

    public function forceDeleted(Task $task): void
    {
        Log::error("Task permanently deleted: ID {$task->id}");
    }
}
