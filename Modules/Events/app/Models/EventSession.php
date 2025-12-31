<?php

declare(strict_types=1);

namespace Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EventSession Model
 *
 * Represents individual sessions within a multi-day event or course.
 *
 * @property int $id
 * @property int $event_id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon $start_time
 * @property \Carbon\Carbon|null $end_time
 * @property string|null $location_name
 * @property string|null $location_address
 * @property string|null $location_link
 * @property int|null $room_id
 * @property string|null $speaker_name
 * @property string|null $speaker_bio
 * @property string|null $speaker_image
 * @property int|null $capacity
 * @property int $sort_order
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<SessionAttendance> $attendances
 */
class EventSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'event_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location_name',
        'location_address',
        'location_link',
        'room_id',
        'speaker_name',
        'speaker_bio',
        'speaker_image',
        'capacity',
        'sort_order',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the parent event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get attendance records for this session.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get checked-in count for this session.
     */
    public function getCheckedInCount(): int
    {
        return $this->attendances()->whereNotNull('checked_in_at')->count();
    }

    /**
     * Check if session is currently happening.
     */
    public function isOngoing(): bool
    {
        $now = now();

        return $now >= $this->start_time
            && ($this->end_time === null || $now <= $this->end_time);
    }

    /**
     * Check if session has completed.
     */
    public function isCompleted(): bool
    {
        return $this->end_time !== null && now() > $this->end_time;
    }
}
