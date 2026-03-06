<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Senior Move: Check if user owns the project before allowing task creation
        $project = $this->route('project');
        return $project && $this->user()->id === $project->user_id;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'string', 'in:pending,in_progress,completed'],
        ];
    }
}
