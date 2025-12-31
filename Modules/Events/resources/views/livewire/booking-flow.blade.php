<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;
use Modules\Events\Models\TicketType;
use Modules\Events\Services\EventBookingService;
use Modules\Events\Services\TicketService;

/**
 * Event Booking Flow Component
 *
 * Multi-step booking process for event registration.
 * Step 1: Select tickets
 * Step 2: Guest details (if not logged in)
 * Step 3: Payment (if not free)
 * Step 4: Confirmation
 */
new #[Layout('layouts.app')] class extends Component {
    public Event $event;

    public int $currentStep = 1;

    // Step 1: Ticket selection
    public array $selectedTickets = [];

    // Step 2: Guest details
    public ?string $guestName = null;

    public ?string $guestEmail = null;

    public ?string $guestPhone = null;

    public ?string $companyName = null;

    public ?string $jobTitle = null;

    public ?string $specialRequirements = null;

    // Step 3: Payment
    public ?string $paymentMethod = null;

    // Created attendees after successful booking
    public array $createdAttendees = [];

    /**
     * Mount the component.
     */
    public function mount(string $slug): mixed
    {
        $this->event = Event::query()
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->with([
                'ticketTypes' => fn ($q) => $q->available()->orderBy('sort_order'),
            ])
            ->firstOrFail();

        // Check if registration is open
        if (! $this->event->is_registration_open) {
            session()->flash('error', 'Registration for this event is not currently open.');

            return redirect()->route('events.show', $this->event->slug);
        }

        // Initialize ticket selection
        foreach ($this->event->ticketTypes as $ticket) {
            if (! $ticket->is_hidden && $ticket->is_purchasable) {
                $this->selectedTickets[$ticket->id] = 0;
            }
        }

        // Pre-fill guest details if logged in
        if (auth()->check()) {
            $user = auth()->user();
            $this->guestName = $user->name;
            $this->guestEmail = $user->email;
        }

        return null;
    }

    /**
     * Get the total quantity of tickets selected.
     */
    #[Computed]
    public function totalQuantity(): int
    {
        return array_sum($this->selectedTickets);
    }

    /**
     * Get the total price.
     */
    #[Computed]
    public function totalPrice(): float
    {
        $total = 0;

        foreach ($this->selectedTickets as $ticketId => $quantity) {
            if ($quantity > 0) {
                $ticket = $this->event->ticketTypes->find($ticketId);
                if ($ticket) {
                    $total += $ticket->price * $quantity;
                }
            }
        }

        return $total;
    }

    /**
     * Check if this is a free order.
     */
    #[Computed]
    public function isFreeOrder(): bool
    {
        return $this->totalPrice == 0;
    }

    /**
     * Get selected ticket details.
     */
    #[Computed]
    public function selectedTicketDetails(): array
    {
        $details = [];

        foreach ($this->selectedTickets as $ticketId => $quantity) {
            if ($quantity > 0) {
                $ticket = $this->event->ticketTypes->find($ticketId);
                if ($ticket) {
                    $details[] = [
                        'ticket' => $ticket,
                        'quantity' => $quantity,
                        'subtotal' => $ticket->price * $quantity,
                    ];
                }
            }
        }

        return $details;
    }

    /**
     * Increment ticket quantity.
     */
    public function incrementTicket(int $ticketId): void
    {
        $ticket = $this->event->ticketTypes->find($ticketId);

        if (! $ticket) {
            return;
        }

        $currentQty = $this->selectedTickets[$ticketId] ?? 0;
        $maxPerOrder = min($ticket->max_per_order, $this->event->max_tickets_per_order);
        $available = $ticket->quantity_available;

        // Check max per order
        if ($currentQty >= $maxPerOrder) {
            $this->addError('tickets', "Maximum {$maxPerOrder} tickets per order for {$ticket->name}");

            return;
        }

        // Check availability
        if ($available !== null && $currentQty >= $available) {
            $this->addError('tickets', "Only {$available} tickets available for {$ticket->name}");

            return;
        }

        // Check total order limit
        if ($this->totalQuantity >= $this->event->max_tickets_per_order) {
            $this->addError('tickets', "Maximum {$this->event->max_tickets_per_order} tickets per order");

            return;
        }

        $this->selectedTickets[$ticketId] = $currentQty + 1;
        $this->resetErrorBag('tickets');
    }

    /**
     * Decrement ticket quantity.
     */
    public function decrementTicket(int $ticketId): void
    {
        $currentQty = $this->selectedTickets[$ticketId] ?? 0;

        if ($currentQty > 0) {
            $this->selectedTickets[$ticketId] = $currentQty - 1;
        }

        $this->resetErrorBag('tickets');
    }

    /**
     * Go to next step.
     */
    public function nextStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => $this->processPayment(),
            default => null,
        };
    }

    /**
     * Go to previous step.
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Validate step 1 (ticket selection).
     */
    protected function validateStep1(): void
    {
        if ($this->totalQuantity === 0) {
            $this->addError('tickets', 'Please select at least one ticket.');

            return;
        }

        // Validate each ticket's min/max
        foreach ($this->selectedTickets as $ticketId => $quantity) {
            if ($quantity > 0) {
                $ticket = $this->event->ticketTypes->find($ticketId);
                if ($ticket && $quantity < $ticket->min_per_order) {
                    $this->addError('tickets', "Minimum {$ticket->min_per_order} tickets required for {$ticket->name}");

                    return;
                }
            }
        }

        // If logged in, skip to step 3 (payment) or complete if free
        if (auth()->check()) {
            if ($this->isFreeOrder) {
                $this->completeBooking();
            } else {
                $this->currentStep = 3;
            }
        } else {
            $this->currentStep = 2;
        }
    }

    /**
     * Validate step 2 (guest details).
     */
    protected function validateStep2(): void
    {
        $this->validate([
            'guestName' => ['required', 'string', 'max:255'],
            'guestEmail' => ['required', 'email', 'max:255'],
            'guestPhone' => ['nullable', 'string', 'max:50'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'specialRequirements' => ['nullable', 'string', 'max:1000'],
        ]);

        // If free order, complete booking
        if ($this->isFreeOrder) {
            $this->completeBooking();
        } else {
            $this->currentStep = 3;
        }
    }

    /**
     * Process payment.
     */
    protected function processPayment(): mixed
    {
        $this->validate([
            'paymentMethod' => ['required', Rule::in(['paytabs', '2checkout', 'bank_transfer'])],
        ]);

        // Create invoice via Billing module and redirect to payment
        try {
            $bookingService = app(EventBookingService::class);

            $invoice = $bookingService->createInvoiceForBooking(
                event: $this->event,
                tickets: $this->selectedTickets,
                userId: auth()->id(),
                guestData: [
                    'name' => $this->guestName,
                    'email' => $this->guestEmail,
                    'phone' => $this->guestPhone,
                    'company' => $this->companyName,
                    'job_title' => $this->jobTitle,
                    'special_requirements' => $this->specialRequirements,
                ]
            );

            // Redirect to payment gateway
            return redirect()->route('payment.checkout', [
                'invoice' => $invoice->id,
                'gateway' => $this->paymentMethod,
            ]);

        } catch (\Exception $e) {
            $this->addError('payment', $e->getMessage());
        }
    }

    /**
     * Complete booking (for free events).
     */
    protected function completeBooking(): void
    {
        try {
            DB::transaction(function () {
                $bookingService = app(EventBookingService::class);
                $ticketService = app(TicketService::class);

                // Create attendees for each ticket
                $this->createdAttendees = $bookingService->createAttendees(
                    event: $this->event,
                    tickets: $this->selectedTickets,
                    userId: auth()->id(),
                    guestData: [
                        'name' => $this->guestName,
                        'email' => $this->guestEmail,
                        'phone' => $this->guestPhone,
                        'company' => $this->companyName,
                        'job_title' => $this->jobTitle,
                        'special_requirements' => $this->specialRequirements,
                    ],
                    status: AttendeeStatus::Confirmed
                );

                // Issue tickets (generate QR codes, send emails)
                foreach ($this->createdAttendees as $attendee) {
                    $ticketService->issueTicket($attendee);
                }
            });

            $this->currentStep = 4; // Confirmation step

        } catch (\Exception $e) {
            $this->addError('booking', $e->getMessage());
        }
    }
}; ?>

<div class="min-h-screen bg-background py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('events.show', $event->slug) }}" wire:navigate
               class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
                Back to Event
            </a>
            <h1 class="mt-4 text-2xl font-bold text-foreground">{{ $event->title }}</h1>
            <p class="text-muted-foreground">{{ $event->start_date->format('l, F j, Y \a\t g:i A') }}</p>
        </div>

        {{-- Progress Steps --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                @php
                    $steps = [
                        1 => 'Select Tickets',
                        2 => auth()->check() ? 'Payment' : 'Your Details',
                        3 => auth()->check() ? 'Confirmation' : ($this->isFreeOrder ? 'Confirmation' : 'Payment'),
                        4 => 'Confirmation',
                    ];
                    $totalSteps = auth()->check() ? ($this->isFreeOrder ? 2 : 3) : ($this->isFreeOrder ? 3 : 4);
                @endphp

                @for($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2
                            {{ $currentStep > $i ? 'bg-success-600 border-success-600 text-white' :
                               ($currentStep == $i ? 'bg-primary border-primary text-primary-foreground' :
                               'bg-card border-border text-muted-foreground') }}">
                            @if($currentStep > $i)
                                <x-heroicon-o-check class="w-5 h-5" />
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        @if($i < $totalSteps)
                            <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $i ? 'bg-success-600' : 'bg-border' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        {{-- Step Content --}}
        <div class="bg-card rounded-xl shadow-sm border border-border">
            {{-- Step 1: Ticket Selection --}}
            @if($currentStep === 1)
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-6">Select Tickets</h2>

                    @error('tickets')
                        <div class="mb-4 p-4 bg-danger-50 text-danger-700 rounded-lg">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-4">
                        @foreach($event->ticketTypes->filter(fn($t) => !$t->is_hidden && $t->is_purchasable) as $ticket)
                            <div class="border border-border rounded-lg p-4 {{ !$ticket->is_purchasable ? 'opacity-60' : '' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-foreground">{{ $ticket->name }}</h3>
                                        @if($ticket->description)
                                            <p class="text-sm text-muted-foreground mt-1">{{ $ticket->description }}</p>
                                        @endif

                                        {{-- Sale Period --}}
                                        @if($ticket->sale_end_date)
                                            <p class="text-xs text-warning-600 mt-2">
                                                Sales end {{ $ticket->sale_end_date->format('M j, Y') }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="text-right">
                                        @if($ticket->is_free)
                                            <span class="text-lg font-bold text-success-600">Free</span>
                                        @else
                                            <span class="text-lg font-bold text-foreground">
                                                {{ number_format($ticket->price, 0) }} {{ $ticket->currency }}
                                            </span>
                                        @endif

                                        @if($ticket->quantity_available !== null && $ticket->quantity_available <= 10)
                                            <p class="text-xs text-warning-600">
                                                {{ $ticket->quantity_available }} left
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Quantity Selector --}}
                                <div class="mt-4 flex items-center justify-end">
                                    @if($ticket->is_purchasable)
                                        <div class="flex items-center space-x-3">
                                            <button
                                                wire:click="decrementTicket({{ $ticket->id }})"
                                                class="w-10 h-10 rounded-full border border-border flex items-center justify-center hover:bg-muted disabled:opacity-50"
                                                {{ ($selectedTickets[$ticket->id] ?? 0) === 0 ? 'disabled' : '' }}>
                                                <x-heroicon-o-minus class="w-5 h-5" />
                                            </button>

                                            <span class="w-12 text-center text-lg font-semibold text-foreground">
                                                {{ $selectedTickets[$ticket->id] ?? 0 }}
                                            </span>

                                            <button
                                                wire:click="incrementTicket({{ $ticket->id }})"
                                                class="w-10 h-10 rounded-full border border-border flex items-center justify-center hover:bg-muted">
                                                <x-heroicon-o-plus class="w-5 h-5" />
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-danger-600 font-medium">Sold Out</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Step 2: Guest Details --}}
            @if($currentStep === 2)
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-6">Your Details</h2>

                    <div class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <flux:input
                                wire:model="guestName"
                                label="Full Name"
                                placeholder="John Doe"
                                required
                            />

                            <flux:input
                                wire:model="guestEmail"
                                type="email"
                                label="Email Address"
                                placeholder="john@example.com"
                                required
                            />
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <flux:input
                                wire:model="guestPhone"
                                type="tel"
                                label="Phone Number"
                                placeholder="+249 123 456 789"
                            />

                            <flux:input
                                wire:model="companyName"
                                label="Company/Organization"
                                placeholder="Acme Inc."
                            />
                        </div>

                        <flux:input
                            wire:model="jobTitle"
                            label="Job Title"
                            placeholder="Software Developer"
                        />

                        <flux:textarea
                            wire:model="specialRequirements"
                            label="Special Requirements"
                            placeholder="Dietary requirements, accessibility needs, etc."
                            rows="3"
                        />
                    </div>
                </div>
            @endif

            {{-- Step 3: Payment --}}
            @if($currentStep === 3 && !$this->isFreeOrder)
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-6">Payment Method</h2>

                    @error('payment')
                        <div class="mb-4 p-4 bg-danger-50 text-danger-700 rounded-lg">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-4">
                        <label class="block border border-border rounded-lg p-4 cursor-pointer hover:border-primary {{ $paymentMethod === 'paytabs' ? 'border-primary bg-primary/5' : '' }}">
                            <input type="radio" wire:model="paymentMethod" value="paytabs" class="sr-only" />
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <span class="font-semibold text-foreground">Credit/Debit Card</span>
                                    <p class="text-sm text-muted-foreground">Pay securely with your card via Paytabs</p>
                                </div>
                                <div class="w-6 h-6 border-2 rounded-full {{ $paymentMethod === 'paytabs' ? 'border-primary bg-primary' : 'border-border' }}">
                                    @if($paymentMethod === 'paytabs')
                                        <x-heroicon-s-check class="w-5 h-5 text-white" />
                                    @endif
                                </div>
                            </div>
                        </label>

                        <label class="block border border-border rounded-lg p-4 cursor-pointer hover:border-primary {{ $paymentMethod === 'bank_transfer' ? 'border-primary bg-primary/5' : '' }}">
                            <input type="radio" wire:model="paymentMethod" value="bank_transfer" class="sr-only" />
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <span class="font-semibold text-foreground">Bank Transfer</span>
                                    <p class="text-sm text-muted-foreground">Pay via bank transfer (manual confirmation)</p>
                                </div>
                                <div class="w-6 h-6 border-2 rounded-full {{ $paymentMethod === 'bank_transfer' ? 'border-primary bg-primary' : 'border-border' }}">
                                    @if($paymentMethod === 'bank_transfer')
                                        <x-heroicon-s-check class="w-5 h-5 text-white" />
                                    @endif
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            @endif

            {{-- Step 4: Confirmation --}}
            @if($currentStep === 4)
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <x-heroicon-o-check-circle class="w-10 h-10 text-success-600" />
                    </div>

                    <h2 class="text-2xl font-bold text-foreground mb-2">Registration Complete!</h2>
                    <p class="text-muted-foreground mb-6">
                        Your tickets have been sent to <strong>{{ $guestEmail ?? auth()->user()?->email }}</strong>
                    </p>

                    <div class="bg-muted rounded-lg p-6 mb-6 text-left">
                        <h3 class="font-semibold text-foreground mb-4">Your Tickets</h3>
                        @foreach($createdAttendees as $attendee)
                            <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                                <div>
                                    <span class="font-medium text-foreground">{{ $attendee->ticketType->name }}</span>
                                    <span class="text-muted-foreground text-sm ml-2">#{{ $attendee->reference_no }}</span>
                                </div>
                                <x-heroicon-o-ticket class="w-5 h-5 text-success-600" />
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('events.show', $event->slug) }}" wire:navigate
                           class="px-6 py-3 bg-muted text-foreground font-semibold rounded-lg hover:bg-muted/80 transition-colors">
                            Back to Event
                        </a>
                        <a href="{{ route('events.my-tickets') }}" wire:navigate
                           class="px-6 py-3 bg-primary text-primary-foreground font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                            View My Tickets
                        </a>
                    </div>
                </div>
            @endif

            {{-- Order Summary & Navigation --}}
            @if($currentStep < 4)
                <div class="border-t border-border p-6">
                    {{-- Order Summary --}}
                    @if($this->totalQuantity > 0)
                        <div class="bg-muted rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-foreground mb-3">Order Summary</h3>
                            @foreach($this->selectedTicketDetails as $item)
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-muted-foreground">
                                        {{ $item['quantity'] }}x {{ $item['ticket']->name }}
                                    </span>
                                    <span class="text-foreground">
                                        @if($item['ticket']->is_free)
                                            Free
                                        @else
                                            {{ number_format($item['subtotal'], 0) }} {{ $item['ticket']->currency }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                            <div class="border-t border-border mt-3 pt-3 flex justify-between font-semibold">
                                <span class="text-foreground">Total</span>
                                <span class="text-foreground">
                                    @if($this->isFreeOrder)
                                        Free
                                    @else
                                        {{ number_format($this->totalPrice, 0) }} {{ $event->currency }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Navigation Buttons --}}
                    <div class="flex justify-between">
                        @if($currentStep > 1)
                            <flux:button wire:click="previousStep" variant="ghost">
                                ← Previous
                            </flux:button>
                        @else
                            <div></div>
                        @endif

                        <flux:button wire:click="nextStep" variant="primary" :disabled="$this->totalQuantity === 0">
                            {{ $currentStep === 3 ? 'Pay Now' : ($this->isFreeOrder && $currentStep >= 2 ? 'Complete Registration' : 'Continue →') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
