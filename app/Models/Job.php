<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Job extends Model
{
    // protected $fillable = [
    //     'title',
    //     'location',
    //     'description',
    //     'company_id',
    //     'summary',
    // ];
    use Searchable;

    protected $fillable = ['title', 'description', 'location', 'summary'];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    /**
     * Tentukan data yang ingin di-index oleh Meilisearch
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'company' => $this->company?->name,
        ];
    }
}
