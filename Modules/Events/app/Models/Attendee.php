<?php

declare(strict_types=1);

namespace Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Events\Enums\AttendeeStatus;

/**
 * Attendee Model
 *
 * Represents a ticket holder for an event. Supports both registered users and guests.
 *
 * @property int $id
 * @property int $event_id
 * @property int $ticket_type_id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property string|null $guest_email
 * @property string|null $guest_phone
 * @property string|null $company_name
 * @property string|null $job_title
 * @property AttendeeStatus $status
 * @property string $reference_no
 * @property string $qr_code_hash
 * @property int|null $invoice_id
 * @property float $amount_paid
 * @property string $currency
 * @property \Carbon\Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property string|null $check_in_method
 * @property bool $ticket_sent
 * @property \Carbon\Carbon|null $ticket_sent_at
 * @property string|null $ticket_pdf_path
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property bool $refund_requested
 * @property bool $refund_processed
 * @property int|null $crm_lead_id
 * @property array|null $custom_fields
 * @property array|null $metadata
 * @property string|null $special_requirements
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Event $event
 * @property-read TicketType $ticketType
 * @property-read User|null $user
 * @property-read User|null $checkedInByUser
 */
class Attendee extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'attendees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'company_name',
        'job_title',
        'status',
        'reference_no',
        'qr_code_hash',
        'invoice_id',
        'amount_paid',
        'currency',
        'checked_in_at',
        'checked_in_by',
        'check_in_method',
        'ticket_sent',
        'ticket_sent_at',
        'ticket_pdf_path',
        'cancelled_at',
        'cancellation_reason',
        'refund_requested',
        'refund_processed',
        'crm_lead_id',
        'custom_fields',
        'metadata',
        'special_requirements',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendeeStatus::class,
            'amount_paid' => 'decimal:2',
            'checked_in_at' => 'datetime',
            'ticket_sent' => 'boolean',
            'ticket_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_requested' => 'boolean',
            'refund_processed' => 'boolean',
            'custom_fields' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Attendee $attendee): void {
            // Generate unique reference number
            if (empty($attendee->reference_no)) {
                $attendee->reference_no = self::generateReferenceNumber();
            }

            // Generate unique QR code hash
            if (empty($attendee->qr_code_hash)) {
                $attendee->qr_code_hash = self::generateQrCodeHash();
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the event this attendee is registered for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the ticket type.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Get the registered user (if not a guest).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who checked in this attendee.
     */
    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Get session attendance records (for multi-session events).
     */
    public function sessionAttendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to confirmed attendees.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', AttendeeStatus::Confirmed);
    }

    /**
     * Scope to checked-in attendees.
     */
    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->where('status', AttendeeStatus::CheckedIn);
    }

    /**
     * Scope to pending payment attendees.
     */
    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', AttendeeStatus::PendingPayment);
    }

    /**
     * Scope to active (valid) attendees.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AttendeeStatus::Confirmed,
            AttendeeStatus::CheckedIn,
        ]);
    }

    /**
     * Scope to guests (no user account).
     */
    public function scopeGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope to registered users.
     */
    public function scopeRegisteredUsers(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    // =========================================================================
    // COMPUTED ATTRIBUTES
    // =========================================================================

    /**
     * Get the attendee's name (user or guest).
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->user) {
                return $this->user->name;
            }

            return $this->guest_name ?? __('Unknown');
        });
    }

    /**
     * Get the attendee's email (user or guest).
     */
    protected function email(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->user) {
                return $this->user->email;
            }

            return $this->guest_email;
        });
    }

    /**
     * Check if attendee is a guest (no user account).
     */
    protected function isGuest(): Attribute
    {
        return Attribute::get(fn (): bool => $this->user_id === null);
    }

    /**
     * Check if the attendee can check in.
     */
    protected function canCheckIn(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status === AttendeeStatus::Confirmed);
    }

    /**
     * Check if the attendee has already checked in.
     */
    protected function hasCheckedIn(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status === AttendeeStatus::CheckedIn);
    }

    /**
     * Get the QR code content for validation.
     */
    protected function qrCodeContent(): Attribute
    {
        return Attribute::get(function (): string {
            // Content includes reference and hash for validation
            return json_encode([
                'ref' => $this->reference_no,
                'hash' => $this->qr_code_hash,
                'event_id' => $this->event_id,
            ], JSON_THROW_ON_ERROR);
        });
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'EVT';
        $year = date('Y');

        do {
            $random = strtoupper(Str::random(6));
            $reference = "{$prefix}-{$year}-{$random}";
        } while (static::where('reference_no', $reference)->exists());

        return $reference;
    }

    /**
     * Generate a unique QR code hash.
     */
    public static function generateQrCodeHash(): string
    {
        do {
            $hash = hash('sha256', Str::uuid()->toString().microtime(true));
        } while (static::where('qr_code_hash', $hash)->exists());

        return $hash;
    }

    /**
     * Check in the attendee.
     *
     * @param  int|null  $checkedInBy  User ID of who performed the check-in
     * @param  string  $method  Check-in method (qr_scan, manual, bulk)
     */
    public function checkIn(?int $checkedInBy = null, string $method = 'manual'): bool
    {
        if (! $this->can_check_in) {
            return false;
        }

        $this->update([
            'status' => AttendeeStatus::CheckedIn,
            'checked_in_at' => now(),
            'checked_in_by' => $checkedInBy,
            'check_in_method' => $method,
        ]);

        return true;
    }

    /**
     * Undo check-in (revert to confirmed).
     */
    public function undoCheckIn(): bool
    {
        if ($this->status !== AttendeeStatus::CheckedIn) {
            return false;
        }

        $this->update([
            'status' => AttendeeStatus::Confirmed,
            'checked_in_at' => null,
            'checked_in_by' => null,
            'check_in_method' => null,
        ]);

        return true;
    }

    /**
     * Cancel the ticket.
     */
    public function cancel(string $reason = ''): bool
    {
        if (in_array($this->status, [AttendeeStatus::Cancelled, AttendeeStatus::CheckedIn], true)) {
            return false;
        }

        $this->update([
            'status' => AttendeeStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Refund the ticket to stock
        if ($this->status === AttendeeStatus::Confirmed) {
            $this->ticketType->refundTickets(1);
        } elseif ($this->status === AttendeeStatus::PendingPayment) {
            $this->ticketType->releaseReservedTickets(1);
        }

        return true;
    }

    /**
     * Confirm the ticket (after payment).
     */
    public function confirm(): bool
    {
        if ($this->status !== AttendeeStatus::PendingPayment) {
            return false;
        }

        $this->ticketType->confirmTicketSale(1);

        $this->update([
            'status' => AttendeeStatus::Confirmed,
        ]);

        return true;
    }

    /**
     * Mark ticket as sent.
     */
    public function markTicketSent(?string $pdfPath = null): void
    {
        $this->update([
            'ticket_sent' => true,
            'ticket_sent_at' => now(),
            'ticket_pdf_path' => $pdfPath,
        ]);
    }

    /**
     * Find attendee by QR code data.
     */
    public static function findByQrCode(string $qrData): ?self
    {
        try {
            $data = json_decode($qrData, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($data['ref'], $data['hash'])) {
                return null;
            }

            return static::where('reference_no', $data['ref'])
                ->where('qr_code_hash', $data['hash'])
                ->first();
        } catch (\JsonException) {
            // Try finding by reference number directly
            return static::where('reference_no', $qrData)
                ->orWhere('qr_code_hash', $qrData)
                ->first();
        }
    }

    /**
     * Find attendee by reference number.
     */
    public static function findByReference(string $reference): ?self
    {
        return static::where('reference_no', $reference)->first();
    }
}
