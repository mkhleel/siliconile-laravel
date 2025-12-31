<?php

declare(strict_types=1);

namespace Modules\Events\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Events\BookingCompleted;
use Modules\Events\Events\BookingCreated;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;
use Modules\Events\Models\TicketType;

/**
 * EventBookingService
 *
 * Handles the event booking flow including ticket reservation,
 * invoice creation, and payment integration.
 */
class EventBookingService
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * Create a booking (reserve tickets and create invoice).
     *
     * @param  array<int, int>  $tickets  Map of ticket_type_id => quantity
     * @param array{
     *     name: string,
     *     email: string,
     *     phone?: string,
     *     company?: string,
     *     job_title?: string
     * } $guestDetails Guest details (if not logged in)
     * @param  int|null  $userId  Logged-in user ID
     * @return array{attendees: array<Attendee>, invoice: ?Invoice}
     *
     * @throws \RuntimeException
     */
    public function createBooking(
        Event $event,
        array $tickets,
        array $guestDetails = [],
        ?int $userId = null
    ): array {
        // Validate event can accept registrations
        if (! $event->is_registration_open) {
            throw new \RuntimeException(__('Registration is not open for this event.'));
        }

        if (! $event->allow_guest_registration && $userId === null) {
            throw new \RuntimeException(__('This event requires login to register.'));
        }

        // Calculate total and validate tickets
        $totalAmount = 0;
        $isFreeOrder = true;
        $attendeesToCreate = [];

        foreach ($tickets as $ticketTypeId => $quantity) {
            if ($quantity <= 0) {
                continue;
            }

            $ticketType = TicketType::find($ticketTypeId);

            if (! $ticketType || $ticketType->event_id !== $event->id) {
                throw new \RuntimeException(__('Invalid ticket type selected.'));
            }

            if (! $ticketType->is_purchasable) {
                throw new \RuntimeException(__('Ticket ":name" is not available.', [
                    'name' => $ticketType->name,
                ]));
            }

            $maxPurchasable = $ticketType->getMaxPurchasableQuantity();
            if ($quantity > $maxPurchasable) {
                throw new \RuntimeException(__('Maximum :max tickets available for ":name".', [
                    'max' => $maxPurchasable,
                    'name' => $ticketType->name,
                ]));
            }

            // Calculate price
            $price = $ticketType->is_free ? 0 : (float) $ticketType->price;
            $totalAmount += $price * $quantity;

            if ($price > 0) {
                $isFreeOrder = false;
            }

            // Queue attendee creation
            for ($i = 0; $i < $quantity; $i++) {
                $attendeesToCreate[] = [
                    'ticket_type' => $ticketType,
                    'price' => $price,
                ];
            }
        }

        if (empty($attendeesToCreate)) {
            throw new \RuntimeException(__('No tickets selected.'));
        }

        // Use database transaction for atomicity
        return DB::transaction(function () use (
            $event,
            $attendeesToCreate,
            $guestDetails,
            $userId,
            $totalAmount,
            $isFreeOrder
        ) {
            $attendees = [];
            $invoice = null;

            // Reserve tickets first (with locking)
            foreach ($attendeesToCreate as $item) {
                $item['ticket_type']->reserveTickets(1);
            }

            // Create invoice if paid order
            if (! $isFreeOrder) {
                $invoice = $this->createInvoice($event, $attendeesToCreate, $guestDetails, $userId, $totalAmount);
            }

            // Create attendee records
            foreach ($attendeesToCreate as $item) {
                $attendee = Attendee::create([
                    'event_id' => $event->id,
                    'ticket_type_id' => $item['ticket_type']->id,
                    'user_id' => $userId,
                    'guest_name' => $guestDetails['name'] ?? null,
                    'guest_email' => $guestDetails['email'] ?? null,
                    'guest_phone' => $guestDetails['phone'] ?? null,
                    'company_name' => $guestDetails['company'] ?? null,
                    'job_title' => $guestDetails['job_title'] ?? null,
                    'status' => $isFreeOrder ? AttendeeStatus::Confirmed : AttendeeStatus::PendingPayment,
                    'invoice_id' => $invoice?->id,
                    'amount_paid' => $isFreeOrder ? $item['price'] : 0,
                    'currency' => $event->currency,
                ]);

                $attendees[] = $attendee;

                // If free, immediately confirm the ticket
                if ($isFreeOrder) {
                    $item['ticket_type']->confirmTicketSale(1);
                }
            }

            // Fire booking created event
            event(new BookingCreated($event, $attendees, $invoice));

            // If free order, issue tickets immediately
            if ($isFreeOrder) {
                foreach ($attendees as $attendee) {
                    $this->ticketService->issueTicket($attendee);
                }
                event(new BookingCompleted($event, $attendees, $invoice));
            }

            return [
                'attendees' => $attendees,
                'invoice' => $invoice,
            ];
        });
    }

    /**
     * Create invoice for paid event booking.
     *
     * @param  array<array{ticket_type: TicketType, price: float}>  $items
     */
    private function createInvoice(
        Event $event,
        array $items,
        array $guestDetails,
        ?int $userId,
        float $totalAmount
    ): Invoice {
        // Group items by ticket type for invoice line items
        $groupedItems = collect($items)
            ->groupBy(fn ($item) => $item['ticket_type']->id)
            ->map(fn ($group) => [
                'ticket_type' => $group->first()['ticket_type'],
                'quantity' => $group->count(),
                'unit_price' => $group->first()['price'],
            ]);

        // Determine billable type and ID
        if ($userId) {
            $billableType = 'App\\Models\\User';
            $billableId = $userId;
        } else {
            // For guests, we'll use a special handling or Member model
            // For now, create with user reference and guest details in metadata
            $billableType = 'App\\Models\\User';
            $billableId = 1; // System user for guest orders - adjust as needed
        }

        // Create invoice
        $invoice = Invoice::create([
            'billable_type' => $billableType,
            'billable_id' => $billableId,
            'origin_type' => Event::class,
            'origin_id' => $event->id,
            'status' => 'draft',
            'currency' => $event->currency,
            'subtotal' => $totalAmount,
            'tax_rate' => 15.00, // Saudi VAT
            'tax_amount' => $totalAmount * 0.15,
            'total' => $totalAmount * 1.15,
            'billing_details' => [
                'name' => $guestDetails['name'] ?? null,
                'email' => $guestDetails['email'] ?? null,
                'phone' => $guestDetails['phone'] ?? null,
                'company' => $guestDetails['company'] ?? null,
            ],
            'notes' => __('Event Registration: :event', ['event' => $event->title]),
            'metadata' => [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'is_guest_checkout' => $userId === null,
            ],
        ]);

        // Create invoice items
        foreach ($groupedItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $event->title.' - '.$item['ticket_type']->name,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => 0,
                'subtotal' => $item['unit_price'] * $item['quantity'],
                'metadata' => [
                    'ticket_type_id' => $item['ticket_type']->id,
                    'ticket_type_name' => $item['ticket_type']->name,
                ],
            ]);
        }

        // Finalize invoice
        $invoice->update([
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
        ]);

        return $invoice;
    }

    /**
     * Handle successful payment (called by InvoicePaid listener).
     */
    public function handlePaymentCompleted(Invoice $invoice): void
    {
        // Find all attendees linked to this invoice
        $attendees = Attendee::where('invoice_id', $invoice->id)
            ->where('status', AttendeeStatus::PendingPayment)
            ->get();

        if ($attendees->isEmpty()) {
            Log::warning('No pending attendees found for paid invoice', [
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        $event = $attendees->first()->event;

        DB::transaction(function () use ($attendees) {
            foreach ($attendees as $attendee) {
                // Confirm the ticket
                $attendee->confirm();

                // Update amount paid
                $attendee->update([
                    'amount_paid' => $attendee->ticketType->price,
                ]);

                // Issue the ticket (QR code + PDF + email)
                $this->ticketService->issueTicket($attendee);
            }
        });

        event(new BookingCompleted($event, $attendees->all(), $invoice));

        Log::info('Event booking completed after payment', [
            'invoice_id' => $invoice->id,
            'attendees_count' => $attendees->count(),
            'event_id' => $event->id,
        ]);
    }

    /**
     * Handle failed/expired payment.
     */
    public function handlePaymentFailed(Invoice $invoice): void
    {
        // Find all pending attendees and release their tickets
        $attendees = Attendee::where('invoice_id', $invoice->id)
            ->where('status', AttendeeStatus::PendingPayment)
            ->get();

        DB::transaction(function () use ($attendees) {
            foreach ($attendees as $attendee) {
                // Release reserved tickets
                $attendee->ticketType->releaseReservedTickets(1);

                // Mark as expired
                $attendee->update([
                    'status' => AttendeeStatus::Expired,
                ]);
            }
        });

        Log::info('Event booking expired/failed', [
            'invoice_id' => $invoice->id,
            'attendees_count' => $attendees->count(),
        ]);
    }

    /**
     * Cancel a booking and process refund if needed.
     */
    public function cancelBooking(Attendee $attendee, string $reason = ''): bool
    {
        return $attendee->cancel($reason);
    }

    /**
     * Get booking summary for an event.
     *
     * @return array{
     *     total_registered: int,
     *     confirmed: int,
     *     checked_in: int,
     *     pending_payment: int,
     *     cancelled: int,
     *     revenue: float
     * }
     */
    public function getBookingSummary(Event $event): array
    {
        $attendees = $event->attendees();

        return [
            'total_registered' => $attendees->clone()->active()->count(),
            'confirmed' => $attendees->clone()->confirmed()->count(),
            'checked_in' => $attendees->clone()->checkedIn()->count(),
            'pending_payment' => $attendees->clone()->pendingPayment()->count(),
            'cancelled' => $attendees->clone()->where('status', AttendeeStatus::Cancelled)->count(),
            'revenue' => $attendees->clone()->active()->sum('amount_paid'),
        ];
    }

    /**
     * Create attendees for free event registration (no invoice needed).
     *
     * @param  array<int, int>  $tickets  Map of ticket_type_id => quantity
     * @param  array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     company?: string,
     *     job_title?: string,
     *     special_requirements?: string
     * }  $guestData  Guest details
     * @return array<Attendee>
     *
     * @throws \RuntimeException
     */
    public function createAttendees(
        Event $event,
        array $tickets,
        ?int $userId = null,
        array $guestData = [],
        AttendeeStatus $status = AttendeeStatus::Confirmed
    ): array {
        // Validate event can accept registrations
        if (! $event->is_registration_open) {
            throw new \RuntimeException(__('Registration is not open for this event.'));
        }

        if (! $event->allow_guest_registration && $userId === null) {
            throw new \RuntimeException(__('This event requires login to register.'));
        }

        $attendeesToCreate = $this->validateAndPrepareTickets($event, $tickets);

        if (empty($attendeesToCreate)) {
            throw new \RuntimeException(__('No tickets selected.'));
        }

        return DB::transaction(function () use (
            $event,
            $attendeesToCreate,
            $guestData,
            $userId,
            $status
        ) {
            $attendees = [];

            // Reserve and confirm tickets
            foreach ($attendeesToCreate as $item) {
                $item['ticket_type']->reserveTickets(1);
                $item['ticket_type']->confirmTicketSale(1);
            }

            // Create attendee records
            foreach ($attendeesToCreate as $item) {
                $attendee = Attendee::create([
                    'event_id' => $event->id,
                    'ticket_type_id' => $item['ticket_type']->id,
                    'user_id' => $userId,
                    'guest_name' => $guestData['name'] ?? null,
                    'guest_email' => $guestData['email'] ?? null,
                    'guest_phone' => $guestData['phone'] ?? null,
                    'company_name' => $guestData['company'] ?? null,
                    'job_title' => $guestData['job_title'] ?? null,
                    'special_requirements' => $guestData['special_requirements'] ?? null,
                    'status' => $status,
                    'amount_paid' => $item['price'],
                    'currency' => $event->currency,
                ]);

                $attendees[] = $attendee;
            }

            // Fire events
            event(new BookingCreated($event, $attendees, null));
            event(new BookingCompleted($event, $attendees, null));

            Log::info('Free event attendees created', [
                'event_id' => $event->id,
                'attendees_count' => count($attendees),
            ]);

            return $attendees;
        });
    }

    /**
     * Create invoice for paid event booking (attendees created on payment completion).
     *
     * @param  array<int, int>  $tickets  Map of ticket_type_id => quantity
     * @param  array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     company?: string,
     *     job_title?: string,
     *     special_requirements?: string
     * }  $guestData  Guest details
     *
     * @throws \RuntimeException
     */
    public function createInvoiceForBooking(
        Event $event,
        array $tickets,
        ?int $userId = null,
        array $guestData = []
    ): Invoice {
        // Validate event can accept registrations
        if (! $event->is_registration_open) {
            throw new \RuntimeException(__('Registration is not open for this event.'));
        }

        if (! $event->allow_guest_registration && $userId === null) {
            throw new \RuntimeException(__('This event requires login to register.'));
        }

        $attendeesToCreate = $this->validateAndPrepareTickets($event, $tickets);

        if (empty($attendeesToCreate)) {
            throw new \RuntimeException(__('No tickets selected.'));
        }

        // Calculate total
        $totalAmount = array_sum(array_map(fn ($item) => $item['price'], $attendeesToCreate));

        if ($totalAmount <= 0) {
            throw new \RuntimeException(__('Cannot create invoice for free tickets. Use createAttendees instead.'));
        }

        return DB::transaction(function () use (
            $event,
            $attendeesToCreate,
            $guestData,
            $userId,
            $totalAmount
        ) {
            // Reserve tickets
            foreach ($attendeesToCreate as $item) {
                $item['ticket_type']->reserveTickets(1);
            }

            // Create invoice
            $invoice = $this->createInvoice($event, $attendeesToCreate, $guestData, $userId, $totalAmount);

            // Create pending attendees linked to invoice
            foreach ($attendeesToCreate as $item) {
                Attendee::create([
                    'event_id' => $event->id,
                    'ticket_type_id' => $item['ticket_type']->id,
                    'user_id' => $userId,
                    'guest_name' => $guestData['name'] ?? null,
                    'guest_email' => $guestData['email'] ?? null,
                    'guest_phone' => $guestData['phone'] ?? null,
                    'company_name' => $guestData['company'] ?? null,
                    'job_title' => $guestData['job_title'] ?? null,
                    'special_requirements' => $guestData['special_requirements'] ?? null,
                    'status' => AttendeeStatus::PendingPayment,
                    'invoice_id' => $invoice->id,
                    'amount_paid' => 0,
                    'currency' => $event->currency,
                ]);
            }

            // Fire booking created event
            event(new BookingCreated($event, [], $invoice));

            Log::info('Event booking invoice created', [
                'event_id' => $event->id,
                'invoice_id' => $invoice->id,
                'total_amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Validate tickets and prepare items for creation.
     *
     * @param  array<int, int>  $tickets  Map of ticket_type_id => quantity
     * @return array<array{ticket_type: TicketType, price: float}>
     *
     * @throws \RuntimeException
     */
    private function validateAndPrepareTickets(Event $event, array $tickets): array
    {
        $attendeesToCreate = [];

        foreach ($tickets as $ticketTypeId => $quantity) {
            if ($quantity <= 0) {
                continue;
            }

            $ticketType = TicketType::find($ticketTypeId);

            if (! $ticketType || $ticketType->event_id !== $event->id) {
                throw new \RuntimeException(__('Invalid ticket type selected.'));
            }

            if (! $ticketType->is_purchasable) {
                throw new \RuntimeException(__('Ticket ":name" is not available.', [
                    'name' => $ticketType->name,
                ]));
            }

            $maxPurchasable = $ticketType->getMaxPurchasableQuantity();
            if ($quantity > $maxPurchasable) {
                throw new \RuntimeException(__('Maximum :max tickets available for ":name".', [
                    'max' => $maxPurchasable,
                    'name' => $ticketType->name,
                ]));
            }

            // Calculate price
            $price = $ticketType->is_free ? 0 : (float) $ticketType->price;

            // Queue attendee creation
            for ($i = 0; $i < $quantity; $i++) {
                $attendeesToCreate[] = [
                    'ticket_type' => $ticketType,
                    'price' => $price,
                ];
            }
        }

        return $attendeesToCreate;
    }
}
