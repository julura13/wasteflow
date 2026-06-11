<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringOrder extends Model
{
    use SoftDeletes;

    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected $fillable = [
        'company_id',
        'branch_id',
        'site_id',
        'service_provider_id',
        'created_by',
        'order_type',
        'days_of_week',
        'quantity_lines',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'quantity_lines' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Whether this template fires on the given lowercase day name (e.g. 'monday'). */
    public function firesOnDay(string $day): bool
    {
        return in_array(strtolower($day), $this->days_of_week ?? [], true);
    }

    /** Human-readable day list, e.g. "Mon, Wed, Fri". */
    public function daysLabel(): string
    {
        $abbrev = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];

        return implode(', ', array_map(
            fn ($d) => $abbrev[$d] ?? ucfirst($d),
            $this->days_of_week ?? []
        ));
    }
}
