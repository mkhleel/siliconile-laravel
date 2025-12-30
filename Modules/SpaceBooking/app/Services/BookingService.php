<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Models\Member;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PaymentStatus;
use Modules\SpaceBooking\Enums\PriceUnit;
use Modules\SpaceBooking\Enums\ResourceType;
use Modules\SpaceBooking\Events\BookingCancelled;
use Modules\SpaceBooking\Events\BookingConfirmed;
use Modules\SpaceBooking\Events\BookingCreated;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Models\BookingCredit;
use Modules\SpaceBooking\Models\SpaceResource;

/**
 * BookingService - Core business logic for space reservations.
 *
 * This service handles:
 * - Availability checking with buffer time
 * - Dynamic pricing with plan-based discounts
 * - Credit deduction for members
 * - Booking lifecycle management
 */
class BookingService
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {}

    // ========================================
    // AVAILABILITY CHECKING
    // ========================================

    /**
     * Check if a resource is available for the given time slot.
     *
     * @param SpaceResource $resource The resource to check
     * @param Carbon $start Start time of the desired booking
     * @param Carbon $end End time of the desired booking
     * @param int|null $excludeBookingId Booking ID to exclude (for modifications)
     */
    public function isAvailable(
        SpaceResource $resource,
        Carbon $start,
        Carbon $end,
        ?int $excludeBookingId = null
    ): bool {
        // Resource must be active
        if (!$resource->is_active) {
            return false;
        }

        // Check operating hours
        if (!$resource->isWithinOperatingHours($start, $end)) {
            return false;
        }

        // Check duration limits
        $durationMinutes = (int) $start->diffInMinutes($end);
        if ($durationMinutes < $resource->min_booking_minutes) {
            return false;
        }
        if ($resource->max_booking_minutes && $durationMinutes > $resource->max_booking_minutes) {
            return false;
        }

        // Check for overlapping bookings
        return !$resource->hasOverlappingBooking($start, $end, $excludeBookingId);
    }

    /**
     * Get all available resources for a given time slot.
     *
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @param ResourceType|null $type Filter by resource type
     * @param int|null $minCapacity Minimum capacity required
     * @param array<int>|null $amenityIds Required amenity IDs
     * @return \Illuminate\Database\Eloquent\Collection<int, SpaceResource>
     */
    public function getAvailableResources(
        Carbon $start,
        Carbon $end,
        ?ResourceType $type = null,
        ?int $minCapacity = null,
        ?array $amenityIds = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = SpaceResource::query()
            ->active()
            ->availableAt($start, $end)
            ->with('amenities')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($type) {
            $query->ofType($type);
        }

        if ($minCapacity) {
            $query->withCapacity($minCapacity);
        }

        if ($amenityIds && count($amenityIds) > 0) {
            $query->withAmenities($amenityIds);
        }

        return $query->get();
    }

    /**
     * Get available time slots for a resource on a given date.
     *
     * @return array<array{start: Carbon, end: Carbon, available: bool}>
     */
    public function getTimeSlots(
        SpaceResource $resource,
        Carbon $date,
        int $slotDurationMinutes = 60
    ): array {
        return $resource->getAvailableSlots($date, $slotDurationMinutes);
    }

    // ========================================
    // BOOKING CREATION
    // ========================================

    /**
     * Create a new booking.
     *
     * @param SpaceResource $resource Resource to book
     * @param Model $bookable User or Member making the booking
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @param array{notes?: string, attendees_count?: int} $options Additional options
     * @throws \Exception If booking cannot be created
     */
    public function createBooking(
        SpaceResource $resource,
        Model $bookable,
        Carbon $start,
        Carbon $end,
        array $options = []
    ): Booking {
        // Validate availability first
        if (!$this->isAvailable($resource, $start, $end)) {
            throw new \RuntimeException('Resource is not available for the requested time slot.');
        }

        // Calculate pricing
        $priceCalculation = $this->pricingService->calculatePrice(
            $resource,
            $start,
            $end,
            $bookable instanceof Member ? $bookable : null
        );

        return DB::transaction(function () use (
            $resource,
            $bookable,
            $start,
            $end,
            $options,
            $priceCalculation
        ) {
            // Create the booking
            $booking = new Booking();
            $booking->space_resource_id = $resource->id;
            $booking->bookable_type = $bookable->getMorphClass();
            $booking->bookable_id = $bookable->getKey();
            $booking->start_time = $start;
            $booking->end_time = $end;
            $booking->status = $resource->requires_approval
                ? BookingStatus::PENDING
                : BookingStatus::CONFIRMED;
            $booking->unit_price = $priceCalculation['unit_price'];
            $booking->price_unit = $priceCalculation['price_unit'];
            $booking->quantity = $priceCalculation['quantity'];
            $booking->discount_amount = $priceCalculation['discount_amount'];
            $booking->total_price = $priceCalculation['total_price'];
            $booking->credits_used = $priceCalculation['credits_used'];
            $booking->currency = $resource->currency;
            $booking->payment_status = $priceCalculation['total_price'] > 0
                ? PaymentStatus::UNPAID
                : PaymentStatus::PAID;
            $booking->notes = $options['notes'] ?? null;
            $booking->attendees_count = $options['attendees_count'] ?? null;
            $booking->save();

            // Deduct credits if used
            if ($priceCalculation['credits_used'] > 0 && $bookable instanceof Member) {
                $this->deductCredits(
                    $bookable,
                    $resource->resource_type,
                    $priceCalculation['credits_used']
                );
            }

            // Fire event
            event(new BookingCreated($booking));

            Log::info('Booking created', [
                'booking_id' => $booking->id,
                'resource_id' => $resource->id,
                'bookable_type' => $bookable->getMorphClass(),
                'bookable_id' => $bookable->getKey(),
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'total_price' => $priceCalculation['total_price'],
            ]);

            return $booking;
        });
    }

    // ========================================
    // BOOKING MODIFICATIONS
    // ========================================

    /**
     * Reschedule a booking to a new time slot.
     */
    public function rescheduleBooking(
        Booking $booking,
        Carbon $newStart,
        Carbon $newEnd
    ): Booking {
        if (!$booking->canModify()) {
            throw new \RuntimeException('This booking cannot be modified.');
        }

        $resource = $booking->resource;

        // Check availability excluding current booking
        if (!$this->isAvailable($resource, $newStart, $newEnd, $booking->id)) {
            throw new \RuntimeException('Resource is not available for the requested time slot.');
        }

        // Recalculate pricing
        $bookable = $booking->bookable;
        $priceCalculation = $this->pricingService->calculatePrice(
            $resource,
            $newStart,
            $newEnd,
            $bookable instanceof Member ? $bookable : null
        );

        return DB::transaction(function () use ($booking, $newStart, $newEnd, $priceCalculation) {
            // Refund old credits if any
            if ($booking->credits_used > 0 && $booking->bookable instanceof Member) {
                $this->refundCredits(
                    $booking->bookable,
                    $booking->resource->resource_type,
                    (float) $booking->credits_used
                );
            }

            // Update booking
            $booking->start_time = $newStart;
            $booking->end_time = $newEnd;
            $booking->unit_price = $priceCalculation['unit_price'];
            $booking->quantity = $priceCalculation['quantity'];
            $booking->discount_amount = $priceCalculation['discount_amount'];
            $booking->total_price = $priceCalculation['total_price'];
            $booking->credits_used = $priceCalculation['credits_used'];
            $booking->save();

            // Deduct new credits if used
            if ($priceCalculation['credits_used'] > 0 && $booking->bookable instanceof Member) {
                $this->deductCredits(
                    $booking->bookable,
                    $booking->resource->resource_type,
                    $priceCalculation['credits_used']
                );
            }

            return $booking->fresh();
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        if (!$booking->status->canCancel()) {
            throw new \RuntimeException('This booking cannot be cancelled.');
        }

        return DB::transaction(function () use ($booking, $reason) {
            // Refund credits if any
            if ($booking->credits_used > 0 && $booking->bookable instanceof Member) {
                $this->refundCredits(
                    $booking->bookable,
                    $booking->resource->resource_type,
                    (float) $booking->credits_used
                );
            }

            $booking->cancel($reason);

            // Fire event
            event(new BookingCancelled($booking));

            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'reason' => $reason,
            ]);

            return $booking;
        });
    }

    /**
     * Confirm a pending booking.
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if (!$booking->status->canConfirm()) {
            throw new \RuntimeException('This booking cannot be confirmed.');
        }

        $booking->confirm();

        event(new BookingConfirmed($booking));

        Log::info('Booking confirmed', ['booking_id' => $booking->id]);

        return $booking;
    }

    // ========================================
    // CREDIT MANAGEMENT
    // ========================================

    /**
     * Deduct credits from member's allocation.
     */
    private function deductCredits(Member $member, ResourceType $resourceType, float $amount): void
    {
        $credits = BookingCredit::query()
            ->forMember($member->id)
            ->forResourceType($resourceType)
            ->active()
            ->withAvailableCredits()
            ->orderBy('period_end') // Use oldest first
            ->get();

        $remaining = $amount;

        foreach ($credits as $credit) {
            if ($remaining <= 0) {
                break;
            }

            $available = $credit->getRemainingCredits();
            $toDeduct = min($available, $remaining);

            $credit->useCredits($toDeduct);
            $remaining -= $toDeduct;
        }
    }

    /**
     * Refund credits to member's allocation.
     */
    private function refundCredits(Member $member, ResourceType $resourceType, float $amount): void
    {
        // Find the most recent credit record for this period
        $credit = BookingCredit::query()
            ->forMember($member->id)
            ->forResourceType($resourceType)
            ->active()
            ->orderByDesc('period_end')
            ->first();

        if ($credit) {
            $credit->refundCredits($amount);
        }
    }

    // ========================================
    // REPORTING
    // ========================================

    /**
     * Get utilization statistics for a resource over a date range.
     *
     * @return array{total_hours: float, booked_hours: float, utilization_percent: float}
     */
    public function getUtilization(
        SpaceResource $resource,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $bookings = $resource->bookings()
            ->blocking()
            ->where('start_time', '>=', $startDate)
            ->where('end_time', '<=', $endDate)
            ->get();

        $bookedMinutes = $bookings->sum(fn (Booking $b) => $b->getDurationMinutes());

        // Calculate total available hours
        $totalDays = (int) $startDate->diffInDays($endDate) + 1;

        if ($resource->available_from && $resource->available_until) {
            $availableFrom = Carbon::parse($resource->available_from);
            $availableUntil = Carbon::parse($resource->available_until);
            $hoursPerDay = $availableFrom->diffInHours($availableUntil);
        } else {
            $hoursPerDay = 24;
        }

        $totalHours = $totalDays * $hoursPerDay;
        $bookedHours = $bookedMinutes / 60;

        return [
            'total_hours' => $totalHours,
            'booked_hours' => $bookedHours,
            'utilization_percent' => $totalHours > 0
                ? round(($bookedHours / $totalHours) * 100, 2)
                : 0,
        ];
    }
}
