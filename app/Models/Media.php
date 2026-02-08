<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'file_name',
        'original_name',
        'mime_type',
        'disk',
        'path',
        'file_size',
        'collection',
        'description',
    ];

    /**
     * Get the parent mediable model (Order, Material, etc.).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the file URL for accessing the file.
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
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
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
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
