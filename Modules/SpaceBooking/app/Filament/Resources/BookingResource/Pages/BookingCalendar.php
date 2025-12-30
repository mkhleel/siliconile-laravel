<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\BookingResource\Pages;

use BackedEnum;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Filament\Resources\BookingResource;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Models\SpaceResource;

/**
 * Booking Calendar Page - Visual calendar view of bookings.
 *
 * This page provides a calendar interface for viewing and managing bookings.
 * It uses FullCalendar.js integration via Livewire or a custom implementation.
 *
 * For production, consider using `saade/filament-fullcalendar` package
 * when v4 support is available. This implementation provides a basic
 * calendar view that can be enhanced.
 */
class BookingCalendar extends Page
{
    protected static string $resource = BookingResource::class;

    protected string $view = 'spacebooking::filament.pages.booking-calendar';

    protected static ?string $title = 'Booking Calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public ?int $selectedResourceId = null;

    public string $viewMode = 'week'; // day, week, month

    public string $currentDate;

    public function mount(): void
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('listView')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->url(fn () => BookingResource::getUrl('index'))
                ->color('gray'),

            Actions\CreateAction::make()
                ->label('New Booking'),
        ];
    }

    /**
     * Get bookings for calendar display.
     *
     * @return array<array{id: int, title: string, start: string, end: string, color: string, resourceId: int}>
     */
    public function getCalendarEvents(): array
    {
        $query = Booking::with(['resource', 'bookable'])
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::CONFIRMED->value,
            ]);

        if ($this->selectedResourceId) {
            $query->where('space_resource_id', $this->selectedResourceId);
        }

        // Filter by current view range
        $startDate = match ($this->viewMode) {
            'day' => now()->parse($this->currentDate)->startOfDay(),
            'week' => now()->parse($this->currentDate)->startOfWeek(),
            'month' => now()->parse($this->currentDate)->startOfMonth(),
        };

        $endDate = match ($this->viewMode) {
            'day' => now()->parse($this->currentDate)->endOfDay(),
            'week' => now()->parse($this->currentDate)->endOfWeek(),
            'month' => now()->parse($this->currentDate)->endOfMonth(),
        };

        $bookings = $query
            ->where('start_time', '>=', $startDate)
            ->where('start_time', '<=', $endDate)
            ->get();

        return $bookings->map(fn (Booking $booking) => [
            'id' => $booking->id,
            'title' => $booking->resource->name . ' - ' . $booking->getBookerName(),
            'start' => $booking->start_time->toIso8601String(),
            'end' => $booking->end_time->toIso8601String(),
            'color' => $this->getStatusColor($booking->status),
            'resourceId' => $booking->space_resource_id,
            'status' => $booking->status->label(),
            'url' => BookingResource::getUrl('edit', ['record' => $booking]),
        ])->toArray();
    }

    /**
     * Get available resources for filter dropdown.
     *
     * @return array<int, string>
     */
    public function getResourceOptions(): array
    {
        return SpaceResource::active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get color for booking status.
     */
    private function getStatusColor(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::PENDING => '#f59e0b', // Amber
            BookingStatus::CONFIRMED => '#10b981', // Green
            BookingStatus::CANCELLED => '#ef4444', // Red
            BookingStatus::COMPLETED => '#6b7280', // Gray
            BookingStatus::NO_SHOW => '#dc2626', // Dark Red
        };
    }

    public function previousPeriod(): void
    {
        $date = now()->parse($this->currentDate);
        $this->currentDate = match ($this->viewMode) {
            'day' => $date->subDay()->format('Y-m-d'),
            'week' => $date->subWeek()->format('Y-m-d'),
            'month' => $date->subMonth()->format('Y-m-d'),
        };
    }

    public function nextPeriod(): void
    {
        $date = now()->parse($this->currentDate);
        $this->currentDate = match ($this->viewMode) {
            'day' => $date->addDay()->format('Y-m-d'),
            'week' => $date->addWeek()->format('Y-m-d'),
            'month' => $date->addMonth()->format('Y-m-d'),
        };
    }

    public function today(): void
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function filterByResource(?int $resourceId): void
    {
        $this->selectedResourceId = $resourceId;
    }
}
