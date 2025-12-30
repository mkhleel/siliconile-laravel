<?php

declare(strict_types=1);

namespace Modules\Membership\Models;

use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'duration_days',
        'price',
        'currency',
        'wifi_access',
        'meeting_room_access',
        'meeting_hours_included',
        'private_desk',
        'locker_access',
        'guest_passes',
        'is_active',
        'max_members',
        'current_members',
        'sort_order',
        'is_featured',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PlanType::class,
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'wifi_access' => 'boolean',
            'meeting_room_access' => 'boolean',
            'meeting_hours_included' => 'integer',
            'private_desk' => 'boolean',
            'locker_access' => 'boolean',
            'guest_passes' => 'integer',
            'is_active' => 'boolean',
            'max_members' => 'integer',
            'current_members' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Get the users subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_plan_id');
    }

    /**
     * Check if plan has available capacity.
     */
    public function hasCapacity(): bool
    {
        if ($this->max_members === null) {
            return true;
        }

        return $this->current_members < $this->max_members;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2) . ' ' . $this->currency;
    }

    /**
     * Scope for active plans only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope for ordering by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
