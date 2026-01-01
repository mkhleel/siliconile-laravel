<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PriceUnit;
use Modules\SpaceBooking\Enums\ResourceType;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Models\SpaceResource;

beforeEach(function () {
    // Create a test space for all tests
    $this->space = SpaceResource::create([
        'name' => 'Test Conference Room',
        'slug' => 'test-conference-room',
        'resource_type' => ResourceType::MEETING_ROOM,
        'is_active' => true,
        'hourly_rate' => 100,
        'currency' => 'EGP',
        'capacity' => 10,
        'min_booking_minutes' => 60,
        'max_booking_minutes' => 480,
        'available_from' => '08:00',
        'available_until' => '22:00',
    ]);
});

test('booking create page requires authentication', function () {
    $this->get(route('member.bookings.create', $this->space->slug))
        ->assertRedirect(route('login'));
});

test('booking create page loads for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('member.bookings.create', $this->space->slug))
        ->assertStatus(200);
});

test('booking create page shows space details', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('member.bookings.create', ['space' => $this->space->slug])
        ->assertSee('Test Conference Room');
});

test('booking show page requires authentication', function () {
    $user = User::factory()->create();
    $booking = Booking::create([
        'space_resource_id' => $this->space->id,
        'bookable_type' => get_class($user),
        'bookable_id' => $user->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(12, 0),
        'status' => BookingStatus::PENDING,
        'unit_price' => 100,
        'price_unit' => PriceUnit::HOUR,
        'quantity' => 2,
        'total_price' => 200,
        'currency' => 'EGP',
    ]);

    $this->get(route('member.bookings.show', $booking))
        ->assertRedirect(route('login'));
});

test('booking show page loads for authenticated user', function () {
    $user = User::factory()->create();
    $booking = Booking::create([
        'space_resource_id' => $this->space->id,
        'bookable_type' => get_class($user),
        'bookable_id' => $user->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(12, 0),
        'status' => BookingStatus::PENDING,
        'unit_price' => 100,
        'price_unit' => PriceUnit::HOUR,
        'quantity' => 2,
        'total_price' => 200,
        'currency' => 'EGP',
    ]);

    $this->actingAs($user)
        ->get(route('member.bookings.show', $booking))
        ->assertStatus(200);
});

test('user cannot view another users booking', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $booking = Booking::create([
        'space_resource_id' => $this->space->id,
        'bookable_type' => get_class($otherUser),
        'bookable_id' => $otherUser->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(12, 0),
        'status' => BookingStatus::PENDING,
        'unit_price' => 100,
        'price_unit' => PriceUnit::HOUR,
        'quantity' => 2,
        'total_price' => 200,
        'currency' => 'EGP',
    ]);

    $this->actingAs($user)
        ->get(route('member.bookings.show', $booking))
        ->assertForbidden();
});
