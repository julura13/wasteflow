<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseNote extends Model
{
    protected $fillable = [
        'version',
        'type',
        'title',
        'description',
        'released_at',
    ];

    public function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ReleaseNoteRead::class);
    }

    public function scopeUnreadByUser(mixed $query, int $userId): mixed
    {
        return $query->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId));
    }
}
