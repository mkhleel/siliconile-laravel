<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Services;

use Carbon\Carbon;
use Modules\Membership\Models\Member;
use Modules\SpaceBooking\Enums\PriceUnit;
use Modules\SpaceBooking\Enums\ResourceType;
use Modules\SpaceBooking\Models\BookingCredit;
use Modules\SpaceBooking\Models\SpaceResource;

/**
 * PricingService - Handles dynamic pricing calculations.
 *
 * Pricing hierarchy:
 * 1. Check if member has free credits from their plan
 * 2. Check if member's plan has a discount percentage
 * 3. Fall back to base resource rates
 */
class PricingService
{
    /**
     * Calculate the price for a booking.
     *
     * @param SpaceResource $resource The resource being booked
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @param Member|null $member The member making the booking (null for guests)
     * @return array{
     *     unit_price: float,
     *     price_unit: PriceUnit,
     *     quantity: int,
     *     base_price: float,
     *     discount_amount: float,
     *     credits_used: float,
     *     total_price: float,
     *     breakdown: array<string, mixed>
     * }
     */
    public function calculatePrice(
        SpaceResource $resource,
        Carbon $start,
        Carbon $end,
        ?Member $member = null
    ): array {
        $priceUnit = $this->determinePriceUnit($resource, $start, $end);
        $quantity = $this->calculateQuantity($start, $end, $priceUnit);
        $unitPrice = $this->getUnitPrice($resource, $priceUnit);
        $basePrice = $unitPrice * $quantity;

        $creditsUsed = 0.0;
        $discountAmount = 0.0;
        $discountPercent = 0;

        // Calculate member benefits
        if ($member) {
            $benefits = $this->calculateMemberBenefits(
                $resource,
                $member,
                $priceUnit,
                $quantity,
                $basePrice
            );

            $creditsUsed = $benefits['credits_used'];
            $discountAmount = $benefits['discount_amount'];
            $discountPercent = $benefits['discount_percent'];
        }

        $totalPrice = max(0, $basePrice - ($creditsUsed * $unitPrice) - $discountAmount);

        return [
            'unit_price' => $unitPrice,
            'price_unit' => $priceUnit,
            'quantity' => $quantity,
            'base_price' => $basePrice,
            'discount_amount' => $discountAmount,
            'credits_used' => $creditsUsed,
            'total_price' => $totalPrice,
            'breakdown' => [
                'duration_minutes' => (int) $start->diffInMinutes($end),
                'discount_percent' => $discountPercent,
                'member_id' => $member?->id,
            ],
        ];
    }

    /**
     * Get a price quote without affecting credits.
     *
     * @return array{
     *     unit_price: float,
     *     price_unit: PriceUnit,
     *     quantity: int,
     *     base_price: float,
     *     available_credits: float,
     *     discount_percent: int,
     *     estimated_total: float,
     *     formatted_total: string
     * }
     */
    public function getQuote(
        SpaceResource $resource,
        Carbon $start,
        Carbon $end,
        ?Member $member = null
    ): array {
        $calculation = $this->calculatePrice($resource, $start, $end, $member);

        $availableCredits = 0.0;
        if ($member) {
            $availableCredits = BookingCredit::getAvailableForMember(
                $member->id,
                $resource->resource_type
            );
        }

        return [
            'unit_price' => $calculation['unit_price'],
            'price_unit' => $calculation['price_unit'],
            'quantity' => $calculation['quantity'],
            'base_price' => $calculation['base_price'],
            'available_credits' => $availableCredits,
            'discount_percent' => $calculation['breakdown']['discount_percent'],
            'estimated_total' => $calculation['total_price'],
            'formatted_total' => number_format($calculation['total_price'], 2) . ' ' . $resource->currency,
        ];
    }

    /**
     * Determine the appropriate price unit based on resource type and duration.
     */
    private function determinePriceUnit(SpaceResource $resource, Carbon $start, Carbon $end): PriceUnit
    {
        $durationMinutes = (int) $start->diffInMinutes($end);
        $durationHours = $durationMinutes / 60;
        $durationDays = $durationMinutes / 1440;

        // For meeting rooms, always use hourly
        if ($resource->resource_type === ResourceType::MEETING_ROOM) {
            return PriceUnit::HOUR;
        }

        // For hot desks, use daily
        if ($resource->resource_type === ResourceType::HOT_DESK) {
            return PriceUnit::DAY;
        }

        // For private offices, determine by duration
        if ($resource->resource_type === ResourceType::PRIVATE_OFFICE) {
            if ($durationDays >= 28 && $resource->monthly_rate) {
                return PriceUnit::MONTH;
            }
            if ($durationDays >= 1 && $resource->daily_rate) {
                return PriceUnit::DAY;
            }
        }

        // Fallback to hourly
        return PriceUnit::HOUR;
    }

    /**
     * Calculate the quantity of units based on duration.
     */
    private function calculateQuantity(Carbon $start, Carbon $end, PriceUnit $unit): int
    {
        $durationMinutes = (int) $start->diffInMinutes($end);

        return (int) ceil($unit->fromMinutes($durationMinutes));
    }

    /**
     * Get the unit price for a resource.
     */
    private function getUnitPrice(SpaceResource $resource, PriceUnit $unit): float
    {
        $rate = $resource->getRateForUnit($unit);

        if ($rate === null) {
            // Fallback: calculate from another rate
            if ($resource->hourly_rate) {
                return match ($unit) {
                    PriceUnit::HOUR => (float) $resource->hourly_rate,
                    PriceUnit::DAY => (float) $resource->hourly_rate * 8, // 8-hour day
                    PriceUnit::WEEK => (float) $resource->hourly_rate * 40,
                    PriceUnit::MONTH => (float) $resource->hourly_rate * 160,
                };
            }

            return 0.0;
        }

        return $rate;
    }

    /**
     * Calculate member benefits (credits and discounts).
     *
     * @return array{credits_used: float, discount_amount: float, discount_percent: int}
     */
    private function calculateMemberBenefits(
        SpaceResource $resource,
        Member $member,
        PriceUnit $priceUnit,
        int $quantity,
        float $basePrice
    ): array {
        $creditsUsed = 0.0;
        $discountAmount = 0.0;
        $discountPercent = 0;

        // Get member's active subscription and plan
        $subscription = $member->activeSubscription();
        if (!$subscription) {
            return [
                'credits_used' => 0.0,
                'discount_amount' => 0.0,
                'discount_percent' => 0,
            ];
        }

        $plan = $subscription->plan;
        $planId = $plan?->id;

        // Check resource-specific pricing rules
        if ($planId && $resource->pricing_rules) {
            $rule = $resource->getPricingRuleForPlan($planId);

            if ($rule) {
                // Apply discount percentage
                if (isset($rule['discount_percent'])) {
                    $discountPercent = (int) $rule['discount_percent'];
                    $discountAmount = $basePrice * ($discountPercent / 100);
                }
            }
        }

        // Check and apply credits
        $availableCredits = BookingCredit::getAvailableForMember(
            $member->id,
            $resource->resource_type
        );

        if ($availableCredits > 0) {
            // Use credits up to the quantity needed
            $creditsToUse = min($availableCredits, $quantity);
            $creditsUsed = $creditsToUse;
        }

        return [
            'credits_used' => $creditsUsed,
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
        ];
    }
}
