<?php

declare(strict_types=1);

namespace Modules\Incubation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Incubation\Database\Factories\ApplicationFactory;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Enums\StartupStage;

/**
 * Application model representing a startup's application to a cohort.
 *
 * @property int $id
 * @property string $application_code
 * @property int $cohort_id
 * @property int|null $user_id
 * @property string $startup_name
 * @property string $email
 * @property string|null $phone
 * @property array $founders_data
 * @property string $problem_statement
 * @property string $solution
 * @property string|null $industry
 * @property string|null $business_model
 * @property string|null $stage
 * @property string|null $traction
 * @property float|null $funding_raised
 * @property string $funding_currency
 * @property string|null $pitch_deck_url
 * @property string|null $pitch_deck_path
 * @property string|null $video_pitch_url
 * @property string|null $website_url
 * @property string|null $why_apply
 * @property array|null $social_links
 * @property string|null $additional_notes
 * @property ApplicationStatus $status
 * @property string|null $previous_status
 * @property float|null $score
 * @property array|null $evaluation_scores
 * @property string|null $internal_notes
 * @property \Carbon\Carbon|null $interview_scheduled_at
 * @property string|null $interview_location
 * @property string|null $interview_meeting_link
 * @property string|null $interview_notes
 * @property \Carbon\Carbon|null $decision_at
 * @property int|null $decided_by_user_id
 * @property string|null $rejection_reason
 * @property int|null $onboarded_member_id
 * @property \Carbon\Carbon|null $onboarded_at
 * @property string|null $source
 * @property string|null $referral_source
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Application extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'application_code',
        'cohort_id',
        'user_id',
        'startup_name',
        'email',
        'phone',
        'founders_data',
        'problem_statement',
        'solution',
        'industry',
        'business_model',
        'stage',
        'traction',
        'funding_raised',
        'funding_currency',
        'pitch_deck_url',
        'pitch_deck_path',
        'video_pitch_url',
        'website_url',
        'why_apply',
        'social_links',
        'additional_notes',
        'status',
        'previous_status',
        'score',
        'evaluation_scores',
        'internal_notes',
        'interview_scheduled_at',
        'interview_location',
        'interview_meeting_link',
        'interview_notes',
        'decision_at',
        'decided_by_user_id',
        'rejection_reason',
        'onboarded_member_id',
        'onboarded_at',
        'source',
        'referral_source',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founders_data' => 'array',
            'social_links' => 'array',
            'evaluation_scores' => 'array',
            'status' => ApplicationStatus::class,
            'stage' => StartupStage::class,
            'funding_raised' => 'decimal:2',
            'score' => 'decimal:2',
            'interview_scheduled_at' => 'datetime',
            'decision_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Application $application): void {
            if (empty($application->application_code)) {
                $application->application_code = self::generateApplicationCode();
            }
        });
    }

    /**
     * Generate a unique application code.
     */
    public static function generateApplicationCode(): string
    {
        $year = now()->year;
        $lastApplication = self::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastApplication
            ? ((int) substr($lastApplication->application_code, -4)) + 1
            : 1;

        return sprintf('APP-%d-%04d', $year, $sequence);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the cohort this application belongs to.
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * Get the user who submitted this application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who made the decision.
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /**
     * Get all mentorship sessions for this application.
     */
    public function mentorshipSessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class);
    }

    /**
     * Get the milestones achieved by this application.
     */
    public function milestones(): BelongsToMany
    {
        return $this->belongsToMany(Milestone::class, 'application_milestone')
            ->withPivot(['achieved_at', 'notes', 'evidence_url', 'verified_by_user_id'])
            ->withTimestamps();
    }

    /**
     * Get the status history for this application.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderByDesc('created_at');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by status.
     */
    public function scopeStatus(Builder $query, ApplicationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to only include active applications (in pipeline).
     */
    public function scopeInPipeline(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::SCREENING,
            ApplicationStatus::INTERVIEW_SCHEDULED,
            ApplicationStatus::INTERVIEWED,
        ]);
    }

    /**
     * Scope to only include accepted applications.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', ApplicationStatus::ACCEPTED);
    }

    /**
     * Scope to filter by cohort.
     */
    public function scopeForCohort(Builder $query, int $cohortId): Builder
    {
        return $query->where('cohort_id', $cohortId);
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    /**
     * Get the primary founder's name.
     */
    public function getPrimaryFounderNameAttribute(): ?string
    {
        $founders = $this->founders_data ?? [];

        return $founders[0]['name'] ?? null;
    }

    /**
     * Get the founders count.
     */
    public function getFoundersCountAttribute(): int
    {
        return count($this->founders_data ?? []);
    }

    /**
     * Check if the application is in an active pipeline state.
     */
    public function isInPipeline(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Check if the application can transition to a given status.
     */
    public function canTransitionTo(ApplicationStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    /**
     * Update the application status with history tracking.
     */
    public function updateStatus(ApplicationStatus $newStatus, ?int $userId = null, ?string $notes = null): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;

        $this->update([
            'previous_status' => $oldStatus->value,
            'status' => $newStatus,
        ]);

        // Log the status change
        $this->statusHistory()->create([
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'changed_by_user_id' => $userId,
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Mark the application as accepted.
     */
    public function accept(?int $userId = null, ?string $notes = null): bool
    {
        $result = $this->updateStatus(ApplicationStatus::ACCEPTED, $userId, $notes);

        if ($result) {
            $this->update([
                'decision_at' => now(),
                'decided_by_user_id' => $userId,
            ]);

            // Increment cohort's accepted count
            $this->cohort->incrementAcceptedCount();
        }

        return $result;
    }

    /**
     * Mark the application as rejected.
     */
    public function reject(?int $userId = null, ?string $reason = null): bool
    {
        $result = $this->updateStatus(ApplicationStatus::REJECTED, $userId, $reason);

        if ($result) {
            $this->update([
                'decision_at' => now(),
                'decided_by_user_id' => $userId,
                'rejection_reason' => $reason,
            ]);
        }

        return $result;
    }

    /**
     * Schedule an interview.
     */
    public function scheduleInterview(
        \DateTimeInterface $scheduledAt,
        ?string $location = null,
        ?string $meetingLink = null
    ): void {
        $this->update([
            'interview_scheduled_at' => $scheduledAt,
            'interview_location' => $location,
            'interview_meeting_link' => $meetingLink,
        ]);

        if ($this->status === ApplicationStatus::SCREENING) {
            $this->updateStatus(ApplicationStatus::INTERVIEW_SCHEDULED);
        }
    }
}
