<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Company extends Model
{
    use Searchable;
    protected $fillable = [
        'name',
    ];
    public function jobs() {
        return $this->hasMany(Job::class);
    }

    // Opsi tambahan: pilih data apa aja yang mau dikirim ke Meilisearch
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
        ];
    }
}
