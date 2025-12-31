<x-filament-panels::page>
    {{-- Event Selection (if no event selected) --}}
    @if(!$this->selectedEvent)
        <div class="text-center py-12">
            <x-heroicon-o-calendar class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">Select an Event</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Choose an event to start checking in attendees.
            </p>

            <div class="mt-6 max-w-md mx-auto">
                <select
                    wire:change="$set('event', $event.target.value)"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                >
                    <option value="">-- Select Event --</option>
                    @foreach($this->getUpcomingEvents() as $event)
                        <option value="{{ $event->id }}">
                            {{ $event->title }} ({{ $event->start_date->format('M j, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Registered</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->eventStats['total'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmed</div>
                    <div class="mt-1 text-2xl font-bold text-success-600">{{ $this->eventStats['confirmed'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-medium text-info-600">Checked In</div>
                    <div class="mt-1 text-2xl font-bold text-info-600">{{ $this->eventStats['checked_in'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Check-In Rate</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @php
                            $total = $this->eventStats['total'] ?? 0;
                            $checkedIn = $this->eventStats['checked_in'] ?? 0;
                            $rate = $total > 0 ? round(($checkedIn / $total) * 100) : 0;
                        @endphp
                        {{ $rate }}%
                    </div>
                </div>
            </div>

            {{-- Search Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-qr-code class="inline-block w-5 h-5 mr-2" />
                    Scan QR Code or Search
                </h3>

                <form wire:submit="search" class="flex gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model="searchQuery"
                            placeholder="Scan QR code, enter reference # or search by name/email..."
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-lg py-3"
                            autofocus
                        />
                    </div>
                    <button
                        type="submit"
                        class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Search
                    </button>
                </form>

                {{-- Last Check-In Message --}}
                @if($lastCheckInMessage)
                    <div class="mt-4 p-4 rounded-lg {{ $lastCheckInSuccess ? 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400' : 'bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-400' }}">
                        {{ $lastCheckInMessage }}
                    </div>
                @endif
            </div>

            {{-- Found Attendee --}}
            @if($foundAttendee)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 {{ $foundAttendee->status->value === 'checked_in' ? 'border-info-500' : 'border-success-500' }} p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $foundAttendee->getName() }}
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">{{ $foundAttendee->getEmail() }}</p>
                            @if($foundAttendee->company_name)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $foundAttendee->job_title ? $foundAttendee->job_title . ' at ' : '' }}{{ $foundAttendee->company_name }}
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ match($foundAttendee->status->value) {
                                'confirmed' => 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400',
                                'checked_in' => 'bg-info-100 text-info-800 dark:bg-info-900/20 dark:text-info-400',
                                'pending_payment' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400',
                                'cancelled' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                            } }}">
                                {{ $foundAttendee->status->getLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Reference:</span>
                            <span class="font-mono font-semibold text-gray-900 dark:text-white ml-1">{{ $foundAttendee->reference_no }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Ticket:</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $foundAttendee->ticketType?->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Registered:</span>
                            <span class="text-gray-900 dark:text-white ml-1">{{ $foundAttendee->created_at->format('M j, Y') }}</span>
                        </div>
                        @if($foundAttendee->checked_in_at)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Checked In:</span>
                                <span class="text-gray-900 dark:text-white ml-1">{{ $foundAttendee->checked_in_at->format('M j, H:i') }}</span>
                            </div>
                        @endif
                    </div>

                    @if($foundAttendee->special_requirements)
                        <div class="mt-4 p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                            <span class="text-warning-800 dark:text-warning-400 font-medium">Special Requirements:</span>
                            <p class="text-warning-700 dark:text-warning-300">{{ $foundAttendee->special_requirements }}</p>
                        </div>
                    @endif

                    <div class="mt-6 flex gap-4">
                        @if($foundAttendee->status->value === 'confirmed')
                            <button
                                wire:click="checkIn"
                                class="flex-1 px-6 py-4 bg-success-600 text-white font-bold text-lg rounded-lg hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-500 transition-colors"
                            >
                                <x-heroicon-o-check-circle class="inline-block w-6 h-6 mr-2" />
                                Check In Now
                            </button>
                        @elseif($foundAttendee->status->value === 'checked_in')
                            <div class="flex-1 px-6 py-4 bg-info-100 dark:bg-info-900/20 text-info-800 dark:text-info-400 font-bold text-lg rounded-lg text-center">
                                <x-heroicon-o-check-badge class="inline-block w-6 h-6 mr-2" />
                                Already Checked In
                            </div>
                        @elseif($foundAttendee->status->value === 'pending_payment')
                            <div class="flex-1 px-6 py-4 bg-warning-100 dark:bg-warning-900/20 text-warning-800 dark:text-warning-400 font-bold text-lg rounded-lg text-center">
                                <x-heroicon-o-exclamation-triangle class="inline-block w-6 h-6 mr-2" />
                                Payment Pending - Cannot Check In
                            </div>
                        @else
                            <div class="flex-1 px-6 py-4 bg-danger-100 dark:bg-danger-900/20 text-danger-800 dark:text-danger-400 font-bold text-lg rounded-lg text-center">
                                <x-heroicon-o-x-circle class="inline-block w-6 h-6 mr-2" />
                                Registration {{ $foundAttendee->status->getLabel() }}
                            </div>
                        @endif

                        <button
                            wire:click="clearSearch"
                            class="px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            @endif

            {{-- Recent Check-Ins --}}
            @if($this->recentCheckIns->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <x-heroicon-o-clock class="inline-block w-5 h-5 mr-2" />
                        Recent Check-Ins
                    </h3>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->recentCheckIns as $attendee)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $attendee->getName() }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">({{ $attendee->ticketType?->name }})</span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $attendee->checked_in_at?->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
