<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ClientHubAdvert extends Model
{
    /** @use HasFactory<\Database\Factories\ClientHubAdvertFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'details',
        'contact_email',
        'file_name',
        'original_name',
        'mime_type',
        'disk',
        'path',
        'file_size',
        'is_active',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ClientHubAdvertView::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (ClientHubAdvert $advert) {
            $disk = Storage::disk($advert->disk);
            if ($disk->exists($advert->path)) {
                $disk->delete($advert->path);
            }
        });
    }
}
