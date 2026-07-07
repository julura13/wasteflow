<?php

namespace App\Models;

use App\Models\Concerns\Viewable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory, Viewable;

    protected $fillable = [
        'title',
        'description',
        'original_name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'file_size',
        'uploaded_by',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
        static::deleting(function (Document $document) {
            $disk = Storage::disk($document->disk);
            if ($disk->exists($document->path)) {
                $disk->delete($document->path);
            }
        });
    }
}
