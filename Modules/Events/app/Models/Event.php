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
use Modules\Events\Enums\EventStatus;
use Modules\Events\Enums\EventType;
use Modules\Events\Enums\LocationType;

/**
 * Event Model
 *
 * Represents events such as workshops, courses, meetups, and conferences.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property EventType $type
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string $timezone
 * @property LocationType $location_type
 * @property string|null $location_name
 * @property string|null $location_address
 * @property string|null $location_link
 * @property int|null $room_id
 * @property string|null $banner_image
 * @property string|null $thumbnail_image
 * @property int|null $total_capacity
 * @property int $registered_count
 * @property bool $is_free
 * @property string $currency
 * @property bool $is_multi_session
 * @property int|null $session_count
 * @property EventStatus $status
 * @property bool $is_featured
 * @property bool $allow_waitlist
 * @property bool $require_approval
 * @property \Carbon\Carbon|null $registration_start_date
 * @property \Carbon\Carbon|null $registration_end_date
 * @property bool $allow_guest_registration
 * @property int $max_tickets_per_order
 * @property int|null $organizer_id
 * @property string|null $organizer_name
 * @property string|null $organizer_email
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array|null $tags
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketType> $ticketTypes
 * @property-read \Illuminate\Database\Eloquent\Collection<Attendee> $attendees
 * @property-read \Illuminate\Database\Eloquent\Collection<EventSession> $sessions
 * @property-read User|null $organizer
 */
class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'type',
        'start_date',
        'end_date',
        'timezone',
        'location_type',
        'location_name',
        'location_address',
        'location_link',
        'room_id',
        'banner_image',
        'thumbnail_image',
        'total_capacity',
        'registered_count',
        'is_free',
        'currency',
        'is_multi_session',
        'session_count',
        'status',
        'is_featured',
        'allow_waitlist',
        'require_approval',
        'registration_start_date',
        'registration_end_date',
        'allow_guest_registration',
        'max_tickets_per_order',
        'organizer_id',
        'organizer_name',
        'organizer_email',
        'meta_title',
        'meta_description',
        'tags',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'location_type' => LocationType::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_start_date' => 'datetime',
            'registration_end_date' => 'datetime',
            'is_free' => 'boolean',
            'is_multi_session' => 'boolean',
            'is_featured' => 'boolean',
            'allow_waitlist' => 'boolean',
            'require_approval' => 'boolean',
            'allow_guest_registration' => 'boolean',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }

            // Ensure unique slug
            $originalSlug = $event->slug;
            $counter = 1;
            while (static::where('slug', $event->slug)->exists()) {
                $event->slug = $originalSlug.'-'.$counter++;
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the ticket types for this event.
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort_order');
    }

    /**
     * Get all attendees for this event.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Get all sessions for this event.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('sort_order')->orderBy('start_time');
    }

    /**
     * Get the event organizer.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to published events only.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published);
    }

    /**
     * Scope to upcoming events.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>=', now());
    }

    /**
     * Scope to past events.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_date', '<', now());
    }

    /**
     * Scope to featured events.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to events with available tickets.
     */
    public function scopeWithAvailableTickets(Builder $query): Builder
    {
        return $query->whereHas('ticketTypes', function (Builder $q) {
            $q->where('status', 'active')
                ->where(function (Builder $q2) {
                    $q2->whereNull('quantity')
                        ->orWhereRaw('quantity > (quantity_sold + quantity_reserved)');
                });
        });
    }

    /**
     * Scope by event type.
     */
    public function scopeOfType(Builder $query, EventType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to events open for registration.
     */
    public function scopeRegistrationOpen(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published)
            ->where(function (Builder $q) {
                $q->whereNull('registration_start_date')
                    ->orWhere('registration_start_date', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('registration_end_date')
                    ->orWhere('registration_end_date', '>=', now());
            });
    }

    // =========================================================================
    // COMPUTED ATTRIBUTES
    // =========================================================================

    /**
     * Get the minimum ticket price.
     */
    protected function minPrice(): Attribute
    {
        return Attribute::get(function (): ?float {
            $minTicket = $this->ticketTypes()
                ->where('status', 'active')
                ->orderBy('price')
                ->first();

            return $minTicket?->price;
        });
    }

    /**
     * Get the maximum ticket price.
     */
    protected function maxPrice(): Attribute
    {
        return Attribute::get(function (): ?float {
            $maxTicket = $this->ticketTypes()
                ->where('status', 'active')
                ->orderByDesc('price')
                ->first();

            return $maxTicket?->price;
        });
    }

    /**
     * Get formatted price range.
     */
    protected function priceRange(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->is_free) {
                return __('Free');
            }

            $min = $this->min_price;
            $max = $this->max_price;

            if ($min === null && $max === null) {
                return __('Free');
            }

            if ($min === $max || $max === null) {
                return number_format($min ?? 0, 2).' '.$this->currency;
            }

            return number_format($min ?? 0, 2).' - '.number_format($max, 2).' '.$this->currency;
        });
    }

    /**
     * Get available spots count.
     */
    protected function availableSpots(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->total_capacity === null) {
                return null; // Unlimited
            }

            return max(0, $this->total_capacity - $this->registered_count);
        });
    }

    /**
     * Check if registration is currently open.
     */
    protected function isRegistrationOpen(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->status !== EventStatus::Published) {
                return false;
            }

            $now = now();

            if ($this->registration_start_date && $now < $this->registration_start_date) {
                return false;
            }

            if ($this->registration_end_date && $now > $this->registration_end_date) {
                return false;
            }

            return true;
        });
    }

    /**
     * Check if event is sold out.
     */
    protected function isSoldOut(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->total_capacity === null) {
                return false;
            }

            return $this->registered_count >= $this->total_capacity;
        });
    }

    /**
     * Get the duration in hours.
     */
    protected function durationHours(): Attribute
    {
        return Attribute::get(function (): ?float {
            if (! $this->end_date) {
                return null;
            }

            return $this->start_date->diffInMinutes($this->end_date) / 60;
        });
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if a user can register for this event.
     */
    public function canRegister(?User $user = null): bool
    {
        if (! $this->is_registration_open) {
            return false;
        }

        if ($this->is_sold_out && ! $this->allow_waitlist) {
            return false;
        }

        if ($user === null && ! $this->allow_guest_registration) {
            return false;
        }

        return true;
    }

    /**
     * Increment the registered count atomically.
     */
    public function incrementRegisteredCount(int $amount = 1): bool
    {
        return $this->increment('registered_count', $amount) > 0;
    }

    /**
     * Decrement the registered count atomically.
     */
    public function decrementRegisteredCount(int $amount = 1): bool
    {
        return $this->where('registered_count', '>=', $amount)
            ->decrement('registered_count', $amount) > 0;
    }

    /**
     * Get the URL for the event page.
     */
    public function getUrl(): string
    {
        return route('events.show', $this->slug);
    }

    /**
     * Get confirmed attendees count.
     */
    public function getConfirmedAttendeesCount(): int
    {
        return $this->attendees()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();
    }

    /**
     * Get checked-in attendees count.
     */
    public function getCheckedInCount(): int
    {
        return $this->attendees()
            ->where('status', 'checked_in')
            ->count();
    }
}
