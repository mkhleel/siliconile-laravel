<?php

declare(strict_types=1);

use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Enums\EventType;
use Modules\Events\Enums\LocationType;
use Modules\Events\Enums\TicketTypeStatus;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;
use Modules\Events\Models\TicketType;
use Modules\Events\Services\EventBookingService;
use Modules\Events\Services\TicketService;

beforeEach(function () {
    // Create test user if needed
    $this->user = \App\Models\User::factory()->create();
});

test('can create an event with ticket types', function () {
    $event = Event::create([
        'title' => 'Test Workshop',
        'slug' => 'test-workshop',
        'type' => EventType::Workshop,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Physical,
        'location_name' => 'Test Venue',
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(7)->addHours(3),
        'timezone' => 'Africa/Khartoum',
        'is_free' => false,
        'allow_guest_registration' => true,
        'max_tickets_per_order' => 5,
        'currency' => 'EGP',
    ]);

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->title)->toBe('Test Workshop')
        ->and($event->is_registration_open)->toBeTrue();

    // Create ticket type
    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'Early Bird',
        'price' => 100.00,
        'quantity' => 50,
        'status' => TicketTypeStatus::Active,
        'min_per_order' => 1,
        'max_per_order' => 3,
        'is_free' => false,
    ]);

    expect($ticketType)->toBeInstanceOf(TicketType::class)
        ->and($ticketType->quantity_available)->toBe(50)
        ->and($ticketType->is_free)->toBeFalse();
});

test('can create free event registration', function () {
    $event = Event::create([
        'title' => 'Free Meetup',
        'slug' => 'free-meetup',
        'type' => EventType::Meetup,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Virtual,
        'start_date' => now()->addDays(3),
        'timezone' => 'Africa/Khartoum',
        'is_free' => true,
        'allow_guest_registration' => true,
        'max_tickets_per_order' => 10,
        'currency' => 'EGP',
    ]);

    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'Free Entry',
        'price' => 0,
        'quantity' => 100,
        'status' => TicketTypeStatus::Active,
    ]);

    // Use booking service to create attendees
    $bookingService = app(EventBookingService::class);

    $attendees = $bookingService->createAttendees(
        event: $event,
        tickets: [$ticketType->id => 2],
        userId: $this->user->id,
        guestData: [],
        status: AttendeeStatus::Confirmed
    );

    expect($attendees)->toHaveCount(2)
        ->and($attendees[0]->status)->toBe(AttendeeStatus::Confirmed)
        ->and($attendees[0]->reference_no)->not->toBeNull();

    // Verify ticket counts updated
    $ticketType->refresh();
    expect($ticketType->quantity_sold)->toBe(2);
});

test('can generate qr code for attendee', function () {
    $event = Event::create([
        'title' => 'QR Test Event',
        'slug' => 'qr-test-event',
        'type' => EventType::Workshop,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Physical,
        'start_date' => now()->addDays(5),
        'timezone' => 'Africa/Khartoum',
        'is_free' => true,
        'currency' => 'EGP',
    ]);

    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'General',
        'price' => 0,
        'status' => TicketTypeStatus::Active,
    ]);

    $attendee = Attendee::create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'user_id' => $this->user->id,
        'status' => AttendeeStatus::Confirmed,
        'currency' => 'EGP',
    ]);

    $ticketService = app(TicketService::class);
    $qrCode = $ticketService->generateQrCode($attendee);

    expect($qrCode)->toBeString()
        ->and(base64_decode($qrCode))->toContain('svg');
});

test('can check in attendee', function () {
    $event = Event::create([
        'title' => 'Check-in Test Event',
        'slug' => 'checkin-test',
        'type' => EventType::Workshop,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Physical,
        'start_date' => now()->addHours(2),
        'timezone' => 'Africa/Khartoum',
        'is_free' => true,
        'currency' => 'EGP',
    ]);

    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'General',
        'price' => 0,
        'status' => TicketTypeStatus::Active,
    ]);

    $attendee = Attendee::create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'user_id' => $this->user->id,
        'status' => AttendeeStatus::Confirmed,
        'currency' => 'EGP',
    ]);

    expect($attendee->has_checked_in)->toBeFalse();

    // Check in the attendee
    $attendee->checkIn($this->user->id, 'manual');

    expect($attendee->has_checked_in)->toBeTrue()
        ->and($attendee->checked_in_at)->not->toBeNull()
        ->and($attendee->check_in_method)->toBe('manual');
});

test('ticket service validates qr code correctly', function () {
    $event = Event::create([
        'title' => 'Validation Test Event',
        'slug' => 'validation-test',
        'type' => EventType::Workshop,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Physical,
        'start_date' => now()->addDays(1),
        'timezone' => 'Africa/Khartoum',
        'currency' => 'EGP',
    ]);

    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'Test Ticket',
        'price' => 0,
        'status' => TicketTypeStatus::Active,
    ]);

    $attendee = Attendee::create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'user_id' => $this->user->id,
        'status' => AttendeeStatus::Confirmed,
        'currency' => 'EGP',
    ]);

    $ticketService = app(TicketService::class);

    // Validate QR code
    $result = $ticketService->checkInByReference($attendee->reference_no, $event->id);

    expect($result['success'])->toBeTrue()
        ->and($result['attendee']->has_checked_in)->toBeTrue();

    // Try to check in again - should fail
    $result2 = $ticketService->checkInByReference($attendee->reference_no, $event->id);
    expect($result2['success'])->toBeFalse()
        ->and($result2['message'])->toContain('Already checked in');
});

test('booking fails for sold out ticket types', function () {
    $event = Event::create([
        'title' => 'Sold Out Event',
        'slug' => 'sold-out-event',
        'type' => EventType::Workshop,
        'status' => EventStatus::Published,
        'location_type' => LocationType::Physical,
        'start_date' => now()->addDays(5),
        'timezone' => 'Africa/Khartoum',
        'is_free' => true,
        'allow_guest_registration' => true,
        'currency' => 'EGP',
    ]);

    $ticketType = TicketType::create([
        'event_id' => $event->id,
        'name' => 'Limited',
        'price' => 0,
        'quantity' => 1, // Only 1 available
        'status' => TicketTypeStatus::Active,
    ]);

    $bookingService = app(EventBookingService::class);

    // First booking should succeed
    $bookingService->createAttendees(
        event: $event,
        tickets: [$ticketType->id => 1],
        userId: $this->user->id,
    );

    // Second booking should fail - ticket is no longer available
    expect(fn () => $bookingService->createAttendees(
        event: $event,
        tickets: [$ticketType->id => 1],
        userId: $this->user->id,
    ))->toThrow(\RuntimeException::class);
});
