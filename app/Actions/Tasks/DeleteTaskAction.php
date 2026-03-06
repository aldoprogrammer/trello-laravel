<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class DeleteTaskAction
{
    public function execute(Task $task): bool
    {
        return $task->delete();
    }
}
