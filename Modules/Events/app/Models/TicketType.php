<?php

declare(strict_types=1);

namespace Modules\Events\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Modules\Events\Enums\TicketTypeStatus;

/**
 * TicketType Model
 *
 * Represents a pricing tier for an event (e.g., "Early Bird", "VIP", "Standard").
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property string $currency
 * @property bool $is_free
 * @property int|null $quantity
 * @property int $quantity_sold
 * @property int $quantity_reserved
 * @property int $min_per_order
 * @property int $max_per_order
 * @property \Carbon\Carbon|null $sale_start_date
 * @property \Carbon\Carbon|null $sale_end_date
 * @property TicketTypeStatus $status
 * @property bool $is_hidden
 * @property bool $requires_promo_code
 * @property array|null $benefits
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<Attendee> $attendees
 */
class TicketType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ticket_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'currency',
        'is_free',
        'quantity',
        'quantity_sold',
        'quantity_reserved',
        'min_per_order',
        'max_per_order',
        'sale_start_date',
        'sale_end_date',
        'status',
        'is_hidden',
        'requires_promo_code',
        'benefits',
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
            'status' => TicketTypeStatus::class,
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'is_hidden' => 'boolean',
            'requires_promo_code' => 'boolean',
            'sale_start_date' => 'datetime',
            'sale_end_date' => 'datetime',
            'benefits' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the event this ticket type belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get attendees who purchased this ticket type.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to active ticket types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TicketTypeStatus::Active);
    }

    /**
     * Scope to available (in stock) ticket types.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', TicketTypeStatus::Active)
            ->where(function (Builder $q) {
                $q->whereNull('quantity')
                    ->orWhereRaw('quantity > (quantity_sold + quantity_reserved)');
            });
    }

    /**
     * Scope to ticket types currently on sale.
     */
    public function scopeOnSale(Builder $query): Builder
    {
        $now = now();

        return $query->where(function (Builder $q) use ($now) {
            $q->whereNull('sale_start_date')
                ->orWhere('sale_start_date', '<=', $now);
        })->where(function (Builder $q) use ($now) {
            $q->whereNull('sale_end_date')
                ->orWhere('sale_end_date', '>=', $now);
        });
    }

    /**
     * Scope to visible (non-hidden) ticket types.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to free ticket types.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true)->orWhere('price', 0);
    }

    // =========================================================================
    // COMPUTED ATTRIBUTES
    // =========================================================================

    /**
     * Get the quantity available for purchase.
     */
    protected function quantityAvailable(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->quantity === null) {
                return null; // Unlimited
            }

            return max(0, $this->quantity - $this->quantity_sold - $this->quantity_reserved);
        });
    }

    /**
     * Check if the ticket type is sold out.
     */
    protected function isSoldOut(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->quantity === null) {
                return false;
            }

            return ($this->quantity_sold + $this->quantity_reserved) >= $this->quantity;
        });
    }

    /**
     * Check if the ticket is currently on sale.
     */
    protected function isOnSale(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->status !== TicketTypeStatus::Active) {
                return false;
            }

            $now = now();

            if ($this->sale_start_date && $now < $this->sale_start_date) {
                return false;
            }

            if ($this->sale_end_date && $now > $this->sale_end_date) {
                return false;
            }

            return true;
        });
    }

    /**
     * Check if this ticket type can be purchased.
     */
    protected function isPurchasable(): Attribute
    {
        return Attribute::get(function (): bool {
            return $this->is_on_sale && ! $this->is_sold_out;
        });
    }

    /**
     * Get formatted price with currency.
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->is_free || $this->price == 0) {
                return __('Free');
            }

            return number_format((float) $this->price, 2).' '.$this->currency;
        });
    }

    // =========================================================================
    // STOCK MANAGEMENT METHODS
    // =========================================================================

    /**
     * Reserve tickets (during checkout process).
     *
     * Uses database transaction and lockForUpdate to prevent race conditions.
     *
     * @throws \RuntimeException If not enough tickets available
     */
    public function reserveTickets(int $quantity): bool
    {
        return DB::transaction(function () use ($quantity): bool {
            // Lock the row for update to prevent race conditions
            $ticketType = static::query()
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (! $ticketType) {
                throw new \RuntimeException('Ticket type not found.');
            }

            $available = $ticketType->quantity_available;

            if ($available !== null && $available < $quantity) {
                throw new \RuntimeException(
                    "Not enough tickets available. Requested: {$quantity}, Available: {$available}"
                );
            }

            $ticketType->increment('quantity_reserved', $quantity);

            return true;
        });
    }

    /**
     * Release reserved tickets (when payment fails or expires).
     */
    public function releaseReservedTickets(int $quantity): bool
    {
        return DB::transaction(function () use ($quantity): bool {
            $ticketType = static::query()
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (! $ticketType) {
                return false;
            }

            $newReserved = max(0, $ticketType->quantity_reserved - $quantity);
            $ticketType->update(['quantity_reserved' => $newReserved]);

            return true;
        });
    }

    /**
     * Confirm ticket sale (move from reserved to sold).
     */
    public function confirmTicketSale(int $quantity): bool
    {
        return DB::transaction(function () use ($quantity): bool {
            $ticketType = static::query()
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (! $ticketType) {
                return false;
            }

            // Decrease reserved, increase sold
            $newReserved = max(0, $ticketType->quantity_reserved - $quantity);
            $ticketType->update([
                'quantity_reserved' => $newReserved,
                'quantity_sold' => $ticketType->quantity_sold + $quantity,
            ]);

            // Also update the event's registered count
            $ticketType->event->incrementRegisteredCount($quantity);

            // Check if sold out and update status
            if ($ticketType->quantity !== null) {
                $newAvailable = $ticketType->quantity - ($ticketType->quantity_sold + $quantity) - $newReserved;
                if ($newAvailable <= 0) {
                    $ticketType->update(['status' => TicketTypeStatus::SoldOut]);
                }
            }

            return true;
        });
    }

    /**
     * Refund tickets (when a confirmed ticket is cancelled).
     */
    public function refundTickets(int $quantity): bool
    {
        return DB::transaction(function () use ($quantity): bool {
            $ticketType = static::query()
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (! $ticketType) {
                return false;
            }

            $newSold = max(0, $ticketType->quantity_sold - $quantity);
            $ticketType->update(['quantity_sold' => $newSold]);

            // Decrement event's registered count
            $ticketType->event->decrementRegisteredCount($quantity);

            // If was sold out, might be available again
            if ($ticketType->status === TicketTypeStatus::SoldOut) {
                $ticketType->update(['status' => TicketTypeStatus::Active]);
            }

            return true;
        });
    }

    /**
     * Get maximum quantity a user can purchase.
     */
    public function getMaxPurchasableQuantity(): int
    {
        $available = $this->quantity_available ?? PHP_INT_MAX;
        $maxAllowed = min($this->max_per_order, $this->event->max_tickets_per_order);

        return min($available, $maxAllowed);
    }
}
