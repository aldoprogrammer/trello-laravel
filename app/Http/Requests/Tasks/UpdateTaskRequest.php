<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $task = $this->route('task');

        // Security Check:
        // 1. User owns the project?
        // 2. Task belongs to that specific project?
        return $project &&
               $task &&
               $this->user()->id === $project->user_id &&
               $task->project_id === $project->id;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'required', 'string', 'in:pending,in_progress,completed'],
        ];
    }
}
