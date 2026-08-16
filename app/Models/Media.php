<?php

namespace App\Models;

use App\Models\Concerns\Viewable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory, Viewable;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'title',
        'file_name',
        'original_name',
        'mime_type',
        'disk',
        'path',
        'local_disk',
        'local_path',
        'local_cached_at',
        'local_deleted_at',
        'file_size',
        'collection',
        'sort_order',
        'description',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'local_cached_at' => 'datetime',
            'local_deleted_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the parent mediable model (Order, Material, etc.). Null for standalone media
     * not attached to a specific record, e.g. SHEQ Compliance documents.
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Companies this document is restricted to. An empty set means visible to every client
     * (the default, matching pre-existing behaviour); a non-empty set restricts visibility
     * to client-role users belonging to one of these companies. Used by SHEQ Compliance.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'media_company');
    }

    /**
     * Get the file URL for accessing the file.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        if ($this->disk === 'public') {
            return $disk->url($this->path);
        }

        if ($this->disk === 'local' || $this->disk === 'wasabi') {
            return route('media.download', $this->id);
        }

        return $disk->url($this->path);
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
        static::deleting(function ($media) {
            $disk = Storage::disk($media->disk);
            if ($disk->exists($media->path)) {
                $disk->delete($media->path);
            }
        });
    }
}
