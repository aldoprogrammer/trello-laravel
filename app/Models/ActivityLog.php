<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    // Allow these fields to be filled
    protected $fillable = [
        'user_id',
        'subject_id',
        'subject_type',
        'description',
        'properties'
    ];

    // Automatically convert JSON string to PHP Array
    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * The "Subject" of the log (could be a Task or Project).
     * This is the Polymorphic Relationship.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The User who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
