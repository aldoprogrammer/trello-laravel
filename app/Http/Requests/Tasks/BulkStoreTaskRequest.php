<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        // Ensure user owns the project they are adding tasks to
        return $project && $this->user()->id === $project->user_id;
    }

    public function rules(): array
    {
        return [
            'tasks'               => ['required', 'array', 'min:1'],
            'tasks.*.title'       => ['required', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
        ];
    }
}
