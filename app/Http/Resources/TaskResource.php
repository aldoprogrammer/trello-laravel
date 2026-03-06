<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'status'     => $this->status,
            'project_id' => $this->project_id,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
