<?php

declare(strict_types=1);

namespace Modules\Incubation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Incubation\Database\Factories\CohortFactory;
use Modules\Incubation\Enums\CohortStatus;

/**
 * Cohort model representing a program cycle.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Carbon\Carbon|null $application_start_date
 * @property \Carbon\Carbon|null $application_end_date
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property int $capacity
 * @property int $accepted_count
 * @property CohortStatus $status
 * @property array|null $eligibility_criteria
 * @property array|null $benefits
 * @property string|null $program_manager
 * @property int|null $program_manager_user_id
 * @property string|null $cover_image
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Cohort extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'application_start_date',
        'application_end_date',
        'start_date',
        'end_date',
        'capacity',
        'accepted_count',
        'status',
        'eligibility_criteria',
        'benefits',
        'program_manager',
        'program_manager_user_id',
        'cover_image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'application_start_date' => 'date',
            'application_end_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'accepted_count' => 'integer',
            'status' => CohortStatus::class,
            'eligibility_criteria' => 'array',
            'benefits' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Cohort $cohort): void {
            if (empty($cohort->slug)) {
                $cohort->slug = Str::slug($cohort->name);
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CohortFactory
    {
        return CohortFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the program manager user.
     */
    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_user_id');
    }

    /**
     * Get all applications for this cohort.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get all milestones for this cohort.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only include cohorts accepting applications.
     */
    public function scopeAcceptingApplications(Builder $query): Builder
    {
        return $query->where('status', CohortStatus::OPEN_FOR_APPLICATIONS)
            ->where(function ($q) {
                $q->whereNull('application_end_date')
                    ->orWhere('application_end_date', '>=', now());
            });
    }

    /**
     * Scope to only include active cohorts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CohortStatus::ACTIVE);
    }

    /**
     * Scope to only include public cohorts.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CohortStatus::OPEN_FOR_APPLICATIONS,
            CohortStatus::ACTIVE,
            CohortStatus::COMPLETED,
        ]);
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    /**
     * Check if the cohort is accepting applications.
     */
    public function isAcceptingApplications(): bool
    {
        if ($this->status !== CohortStatus::OPEN_FOR_APPLICATIONS) {
            return false;
        }

        if ($this->application_end_date && $this->application_end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the cohort has capacity for more startups.
     */
    public function hasCapacity(): bool
    {
        return $this->accepted_count < $this->capacity;
    }

    /**
     * Get the number of available spots.
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->capacity - $this->accepted_count);
    }

    /**
     * Get the duration in weeks.
     */
    public function getDurationWeeksAttribute(): int
    {
        return (int) $this->start_date->diffInWeeks($this->end_date);
    }

    /**
     * Increment the accepted count.
     */
    public function incrementAcceptedCount(): void
    {
        $this->increment('accepted_count');
    }
}
