<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * ResourceAmenity Model - Represents amenities/features that can be attached to resources.
 */
class ResourceAmenity extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'resource_amenities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $amenity) {
            if (empty($amenity->slug)) {
                $amenity->slug = Str::slug($amenity->name);
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get resources that have this amenity.
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(
            SpaceResource::class,
            'space_resource_amenity',
            'resource_amenity_id',
            'space_resource_id'
        );
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to only active amenities.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
