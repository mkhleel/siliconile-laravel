<?php

declare(strict_types=1);

namespace Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SessionAttendance Model
 *
 * Tracks per-session attendance for multi-day courses/events.
 *
 * @property int $id
 * @property int $attendee_id
 * @property int $event_session_id
 * @property \Carbon\Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property \Carbon\Carbon|null $checked_out_at
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Attendee $attendee
 * @property-read EventSession $session
 * @property-read User|null $checkedInByUser
 */
class SessionAttendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'session_attendances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'attendee_id',
        'event_session_id',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the attendee.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * Get the session.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'event_session_id');
    }

    /**
     * Get the user who performed the check-in.
     */
    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check in to session.
     */
    public function checkIn(?int $userId = null): bool
    {
        return $this->update([
            'checked_in_at' => now(),
            'checked_in_by' => $userId,
        ]);
    }

    /**
     * Check out of session.
     */
    public function checkOut(): bool
    {
        return $this->update([
            'checked_out_at' => now(),
        ]);
    }
}
