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
use Modules\Incubation\Database\Factories\MentorFactory;

/**
 * Mentor model representing a mentor in the incubation program.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $title
 * @property string|null $company
 * @property string|null $bio
 * @property string|null $profile_photo
 * @property array|null $expertise
 * @property bool $is_active
 * @property array|null $availability
 * @property int $max_sessions_per_week
 * @property string|null $linkedin_url
 * @property string|null $twitter_url
 * @property string|null $website_url
 * @property int $total_sessions
 * @property int $total_mentees
 * @property float|null $avg_rating
 * @property string|null $internal_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Mentor extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'title',
        'company',
        'bio',
        'profile_photo',
        'expertise',
        'is_active',
        'availability',
        'max_sessions_per_week',
        'linkedin_url',
        'twitter_url',
        'website_url',
        'total_sessions',
        'total_mentees',
        'avg_rating',
        'internal_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'availability' => 'array',
            'is_active' => 'boolean',
            'max_sessions_per_week' => 'integer',
            'total_sessions' => 'integer',
            'total_mentees' => 'integer',
            'avg_rating' => 'decimal:2',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): MentorFactory
    {
        return MentorFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user account linked to this mentor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all mentorship sessions for this mentor.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class);
    }

    /**
     * Get upcoming sessions for this mentor.
     */
    public function upcomingSessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class)
            ->where('scheduled_at', '>', now())
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
            ->orderBy('scheduled_at');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only include active mentors.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by expertise.
     */
    public function scopeWithExpertise(Builder $query, string $expertise): Builder
    {
        return $query->whereJsonContains('expertise', $expertise);
    }

    /**
     * Scope to only include available mentors.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereRaw('total_sessions < max_sessions_per_week * 4'); // Roughly monthly limit
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    /**
     * Get the expertise as a comma-separated string.
     */
    public function getExpertiseStringAttribute(): string
    {
        return implode(', ', $this->expertise ?? []);
    }

    /**
     * Get the display name with title.
     */
    public function getFullTitleAttribute(): string
    {
        $parts = array_filter([$this->name, $this->title, $this->company]);

        if (count($parts) === 1) {
            return $this->name;
        }

        return sprintf('%s - %s', $this->name, implode(', ', array_slice($parts, 1)));
    }

    /**
     * Check if the mentor has availability for a given day.
     */
    public function hasAvailabilityOn(string $day): bool
    {
        $availability = $this->availability ?? [];

        return isset($availability[strtolower($day)]) && ! empty($availability[strtolower($day)]);
    }

    /**
     * Get sessions count for the current week.
     */
    public function getWeeklySessionsCount(): int
    {
        return $this->sessions()
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotIn('status', ['cancelled'])
            ->count();
    }

    /**
     * Check if the mentor can accept more sessions this week.
     */
    public function canAcceptMoreSessions(): bool
    {
        return $this->getWeeklySessionsCount() < $this->max_sessions_per_week;
    }

    /**
     * Update statistics after a session.
     */
    public function updateStatistics(): void
    {
        $completedSessions = $this->sessions()
            ->where('status', 'completed')
            ->count();

        $uniqueMentees = $this->sessions()
            ->distinct('application_id')
            ->count('application_id');

        $avgRating = $this->sessions()
            ->where('status', 'completed')
            ->whereNotNull('startup_rating')
            ->avg('startup_rating');

        $this->update([
            'total_sessions' => $completedSessions,
            'total_mentees' => $uniqueMentees,
            'avg_rating' => $avgRating,
        ]);
    }
}
