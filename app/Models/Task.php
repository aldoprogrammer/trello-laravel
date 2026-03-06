<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Task extends Model
{
    use HasFactory, Searchable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
    ];

    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'description',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id'          => (int) $this->id,
            'title'       => (string) $this->title,       // Prioritas 1
            'description' => (string) $this->description, // Prioritas 2
            'status'      => (string) $this->status,
            'project_id'  => (int) $this->project_id,    // Penting untuk filtering
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
