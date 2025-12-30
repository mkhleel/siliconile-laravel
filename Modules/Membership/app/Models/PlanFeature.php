<?php

declare(strict_types=1);

namespace Modules\Membership\Models;

use Modules\Membership\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_name',
        'feature_type',
        'feature_value',
        'description',
        'sort_order',
        'is_visible',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Get the plan that owns this feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope a query to only include visible features.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to order features by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the typed value based on feature_type.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->feature_type) {
            'boolean' => filter_var($this->feature_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->feature_value,
            'float' => (float) $this->feature_value,
            default => $this->feature_value,
        };
    }

    /**
     * Check if feature is enabled (for boolean features).
     */
    public function isEnabled(): bool
    {
        if ($this->feature_type !== 'boolean') {
            return false;
        }

        return filter_var($this->feature_value, FILTER_VALIDATE_BOOLEAN);
    }
}
