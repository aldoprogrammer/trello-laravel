<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Job extends Model
{
    use Searchable;

    protected $fillable = ['title', 'description', 'location', 'summary'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Fields indexed by Scout / Meilisearch.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'description' => (string) $this->description,
            'location' => (string) $this->location,
            'company' => (string) ($this->company?->name ?? ''),
            'created_at' => optional($this->created_at)?->timestamp,
        ];
    }
}
