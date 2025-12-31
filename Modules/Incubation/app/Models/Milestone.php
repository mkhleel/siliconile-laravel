<?php

declare(strict_types=1);

namespace Modules\Incubation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Incubation\Database\Factories\MilestoneFactory;

/**
 * Milestone model representing a program milestone.
 *
 * @property int $id
 * @property int $cohort_id
 * @property string $name
 * @property string|null $description
 * @property string|null $category
 * @property \Carbon\Carbon|null $target_date
 * @property int|null $week_number
 * @property int $sort_order
 * @property array|null $requirements
 * @property bool $is_required
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Milestone extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cohort_id',
        'name',
        'description',
        'category',
        'target_date',
        'week_number',
        'sort_order',
        'requirements',
        'is_required',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'week_number' => 'integer',
            'sort_order' => 'integer',
            'requirements' => 'array',
            'is_required' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): MilestoneFactory
    {
        return MilestoneFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the cohort this milestone belongs to.
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * Get the applications that have achieved this milestone.
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_milestone')
            ->withPivot(['achieved_at', 'notes', 'evidence_url', 'verified_by_user_id'])
            ->withTimestamps();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get the count of applications that achieved this milestone.
     */
    public function getAchievementCountAttribute(): int
    {
        return $this->applications()->count();
    }

    /**
     * Mark a milestone as achieved by an application.
     */
    public function markAchieved(
        Application $application,
        ?string $notes = null,
        ?string $evidenceUrl = null,
        ?int $verifiedByUserId = null
    ): void {
        $this->applications()->syncWithoutDetaching([
            $application->id => [
                'achieved_at' => now(),
                'notes' => $notes,
                'evidence_url' => $evidenceUrl,
                'verified_by_user_id' => $verifiedByUserId,
            ],
        ]);
    }

    /**
     * Check if the milestone was achieved by an application.
     */
    public function isAchievedBy(Application $application): bool
    {
        return $this->applications()->where('application_id', $application->id)->exists();
    }
}
