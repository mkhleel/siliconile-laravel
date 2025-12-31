<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;
use Modules\Events\Services\TicketService;
use UnitEnum;

/**
 * Event Check-In Page
 *
 * Allows administrators to scan QR codes or search attendees
 * to check them into an event.
 */
class EventCheckIn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected string $view = 'events::filament.pages.event-check-in';

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Check-In';

    #[Url]
    public ?int $event = null;

    public ?string $searchQuery = '';

    public ?Attendee $foundAttendee = null;

    public ?string $lastCheckInMessage = null;

    public bool $lastCheckInSuccess = false;

    /**
     * Get the page title.
     */
    public function getTitle(): string|Htmlable
    {
        return $this->selectedEvent
            ? 'Check-In: '.$this->selectedEvent->title
            : 'Event Check-In';
    }

    /**
     * Get the heading for the page.
     */
    public function getHeading(): string|Htmlable
    {
        return $this->selectedEvent
            ? $this->selectedEvent->title
            : 'Select an Event';
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        return $this->event
            ? Event::with(['ticketTypes'])->find($this->event)
            : null;
    }

    #[Computed]
    public function eventStats(): array
    {
        if (! $this->selectedEvent) {
            return [];
        }

        return [
            'total' => $this->selectedEvent->attendees()->count(),
            'confirmed' => $this->selectedEvent->attendees()
                ->where('status', AttendeeStatus::Confirmed)
                ->count(),
            'checked_in' => $this->selectedEvent->attendees()
                ->where('status', AttendeeStatus::CheckedIn)
                ->count(),
            'pending' => $this->selectedEvent->attendees()
                ->where('status', AttendeeStatus::PendingPayment)
                ->count(),
        ];
    }

    #[Computed]
    public function recentCheckIns(): \Illuminate\Support\Collection
    {
        if (! $this->selectedEvent) {
            return collect();
        }

        return $this->selectedEvent->attendees()
            ->where('status', AttendeeStatus::CheckedIn)
            ->orderByDesc('checked_in_at')
            ->limit(10)
            ->get();
    }

    /**
     * Search for an attendee by reference number, QR code, or name/email.
     */
    public function search(): void
    {
        $this->foundAttendee = null;
        $this->lastCheckInMessage = null;

        if (empty($this->searchQuery) || ! $this->selectedEvent) {
            return;
        }

        $query = trim($this->searchQuery);

        // Search by reference number, QR code hash, or name/email
        $this->foundAttendee = Attendee::query()
            ->where('event_id', $this->selectedEvent->id)
            ->where(function ($q) use ($query) {
                $q->where('reference_no', $query)
                    ->orWhere('qr_code_hash', $query)
                    ->orWhere('guest_email', 'like', "%{$query}%")
                    ->orWhere('guest_name', 'like', "%{$query}%")
                    ->orWhereHas('user', fn ($uq) => $uq
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                    );
            })
            ->with(['ticketType', 'user'])
            ->first();

        if (! $this->foundAttendee) {
            $this->lastCheckInMessage = "No attendee found for: {$query}";
            $this->lastCheckInSuccess = false;
        }
    }

    /**
     * Check in the found attendee.
     */
    public function checkIn(): void
    {
        if (! $this->foundAttendee) {
            Notification::make()
                ->title('No Attendee Selected')
                ->warning()
                ->send();

            return;
        }

        try {
            $ticketService = app(TicketService::class);
            $ticketService->checkInAttendee($this->foundAttendee, auth()->user());

            $this->lastCheckInMessage = "✓ {$this->foundAttendee->getName()} checked in successfully!";
            $this->lastCheckInSuccess = true;

            Notification::make()
                ->title('Check-In Successful!')
                ->body($this->foundAttendee->getName().' has been checked in.')
                ->success()
                ->send();

            // Clear for next scan
            $this->searchQuery = '';
            $this->foundAttendee = null;
            unset($this->recentCheckIns);
            unset($this->eventStats);

        } catch (\Exception $e) {
            $this->lastCheckInMessage = $e->getMessage();
            $this->lastCheckInSuccess = false;

            Notification::make()
                ->title('Check-In Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Quick check-in by reference number.
     */
    public function quickCheckIn(string $referenceNo): void
    {
        $this->searchQuery = $referenceNo;
        $this->search();

        if ($this->foundAttendee && $this->foundAttendee->status === AttendeeStatus::Confirmed) {
            $this->checkIn();
        }
    }

    /**
     * Clear the search and found attendee.
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->foundAttendee = null;
        $this->lastCheckInMessage = null;
    }

    /**
     * Header actions for the page.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('select_event')
                ->label('Change Event')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->form([
                    Select::make('event_id')
                        ->label('Select Event')
                        ->options(
                            Event::query()
                                ->where('status', 'published')
                                ->orderByDesc('start_date')
                                ->pluck('title', 'id')
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $this->event = $data['event_id'];
                    $this->clearSearch();
                }),

            Action::make('back_to_event')
                ->label('View Event')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(fn () => $this->selectedEvent
                    ? route('filament.admin.resources.events.view', $this->selectedEvent)
                    : null
                )
                ->visible(fn () => $this->selectedEvent !== null),
        ];
    }

    /**
     * Get the events for the select dropdown.
     */
    public function getUpcomingEvents(): \Illuminate\Support\Collection
    {
        return Event::query()
            ->where('status', 'published')
            ->where('start_date', '>=', now()->subDay())
            ->orderBy('start_date')
            ->get(['id', 'title', 'start_date']);
    }
}
