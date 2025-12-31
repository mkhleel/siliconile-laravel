<?php

declare(strict_types=1);

namespace Modules\Incubation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Incubation\Database\Factories\MentorshipSessionFactory;
use Modules\Incubation\Enums\SessionStatus;
use Modules\Incubation\Enums\SessionType;

/**
 * MentorshipSession model representing a mentoring session.
 *
 * @property int $id
 * @property string $session_code
 * @property int $mentor_id
 * @property int $application_id
 * @property string|null $title
 * @property string|null $description
 * @property SessionType $type
 * @property \Carbon\Carbon $scheduled_at
 * @property int $duration_minutes
 * @property \Carbon\Carbon|null $ended_at
 * @property string $location_type
 * @property string|null $location
 * @property string|null $meeting_link
 * @property SessionStatus $status
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by_user_id
 * @property string|null $mentor_notes
 * @property string|null $startup_notes
 * @property string|null $summary
 * @property array|null $action_items
 * @property int|null $startup_rating
 * @property string|null $startup_feedback
 * @property int|null $mentor_rating
 * @property string|null $mentor_feedback
 * @property int|null $booked_by_user_id
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class MentorshipSession extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'session_code',
        'mentor_id',
        'application_id',
        'title',
        'description',
        'type',
        'scheduled_at',
        'duration_minutes',
        'ended_at',
        'location_type',
        'location',
        'meeting_link',
        'status',
        'cancellation_reason',
        'cancelled_by_user_id',
        'mentor_notes',
        'startup_notes',
        'summary',
        'action_items',
        'startup_rating',
        'startup_feedback',
        'mentor_rating',
        'mentor_feedback',
        'booked_by_user_id',
        'confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SessionType::class,
            'status' => SessionStatus::class,
            'scheduled_at' => 'datetime',
            'ended_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'duration_minutes' => 'integer',
            'action_items' => 'array',
            'startup_rating' => 'integer',
            'mentor_rating' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MentorshipSession $session): void {
            if (empty($session->session_code)) {
                $session->session_code = self::generateSessionCode();
            }
        });
    }

    /**
     * Generate a unique session code.
     */
    public static function generateSessionCode(): string
    {
        $year = now()->year;
        $lastSession = self::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastSession
            ? ((int) substr($lastSession->session_code, -4)) + 1
            : 1;

        return sprintf('MS-%d-%04d', $year, $sequence);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): MentorshipSessionFactory
    {
        return MentorshipSessionFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the mentor for this session.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    /**
     * Get the application (startup) for this session.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who booked this session.
     */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /**
     * Get the user who cancelled this session.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by status.
     */
    public function scopeStatus(Builder $query, SessionStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to only include upcoming sessions.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>', now())
            ->whereNotIn('status', [
                SessionStatus::CANCELLED,
                SessionStatus::COMPLETED,
                SessionStatus::NO_SHOW,
            ])
            ->orderBy('scheduled_at');
    }

    /**
     * Scope to only include past sessions.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now())
            ->orderByDesc('scheduled_at');
    }

    /**
     * Scope to filter by mentor.
     */
    public function scopeForMentor(Builder $query, int $mentorId): Builder
    {
        return $query->where('mentor_id', $mentorId);
    }

    /**
     * Scope to filter by application.
     */
    public function scopeForApplication(Builder $query, int $applicationId): Builder
    {
        return $query->where('application_id', $applicationId);
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    /**
     * Get the expected end time.
     */
    public function getExpectedEndTimeAttribute(): \Carbon\Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * Get the actual duration in minutes.
     */
    public function getActualDurationAttribute(): ?int
    {
        if (! $this->ended_at) {
            return null;
        }

        return (int) $this->scheduled_at->diffInMinutes($this->ended_at);
    }

    /**
     * Check if the session is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->scheduled_at->isFuture() && ! $this->status->isTerminal();
    }

    /**
     * Check if the session can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Confirm the session.
     */
    public function confirm(): void
    {
        $this->update([
            'status' => SessionStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Start the session.
     */
    public function start(): void
    {
        $this->update([
            'status' => SessionStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Complete the session.
     */
    public function complete(?string $summary = null, ?array $actionItems = null): void
    {
        $this->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
            'summary' => $summary ?? $this->summary,
            'action_items' => $actionItems ?? $this->action_items,
        ]);

        // Update mentor statistics
        $this->mentor->updateStatistics();
    }

    /**
     * Cancel the session.
     */
    public function cancel(?int $userId = null, ?string $reason = null): void
    {
        $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancelled_by_user_id' => $userId,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark as no-show.
     */
    public function markNoShow(): void
    {
        $this->update([
            'status' => SessionStatus::NO_SHOW,
        ]);
    }

    /**
     * Add startup feedback.
     */
    public function addStartupFeedback(int $rating, ?string $feedback = null): void
    {
        $this->update([
            'startup_rating' => $rating,
            'startup_feedback' => $feedback,
        ]);

        // Update mentor's average rating
        $this->mentor->updateStatistics();
    }

    /**
     * Add mentor feedback.
     */
    public function addMentorFeedback(int $rating, ?string $feedback = null): void
    {
        $this->update([
            'mentor_rating' => $rating,
            'mentor_feedback' => $feedback,
        ]);
    }
}
