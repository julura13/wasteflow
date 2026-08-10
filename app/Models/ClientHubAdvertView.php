<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user view state for a ClientHubAdvert. Tracks two independent flags:
 * dismissed_at (the popup was closed - stops it auto-popping again) and
 * read_at (the advert was actually opened/viewed - clears the bell badge).
 * Closing the popup only sets dismissed_at, so the badge stays unread until
 * the user opens the advert again from the notification bell.
 */
class ClientHubAdvertView extends Model
{
    protected $fillable = [
        'client_hub_advert_id',
        'user_id',
        'dismissed_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function clientHubAdvert(): BelongsTo
    {
        return $this->belongsTo(ClientHubAdvert::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
