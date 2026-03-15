<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * Create an audit log entry. Use this for consistent audit trail across the app.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(string $logName, string $description, ?Model $subject = null, array $properties = []): self
    {
        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'causer_id' => auth()->id(),
            'properties' => $properties,
        ]);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
