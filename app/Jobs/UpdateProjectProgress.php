<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProjectProgress implements ShouldQueue
{
    use Queueable;

    public function __construct(public $project) {}

    public function handle(): void
    {
        $total = $this->project->tasks()->count();
        $done = $this->project->tasks()->where('status', 'done')->count();

        $percentage = $total > 0 ? ($done / $total) * 100 : 0;

        $this->project->update(['progress' => $percentage]);
    }
}
