<?php

namespace App\Models\Concerns;

use App\Models\ContentView;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Viewable
{
    public function views(): MorphMany
    {
        return $this->morphMany(ContentView::class, 'viewable');
    }

    public function scopeUnseenByUser(Builder $query, int $userId): Builder
    {
        return $query->whereDoesntHave('views', fn ($q) => $q->where('user_id', $userId));
    }

    public function markSeenBy(User $user): void
    {
        ContentView::firstOrCreate(
            ['viewable_type' => static::class, 'viewable_id' => $this->getKey(), 'user_id' => $user->id],
            ['viewed_at' => now()],
        );
    }
}
