<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Calendar Controls --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            {{-- Navigation --}}
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="previousPeriod"
                    icon="heroicon-o-chevron-left"
                    color="gray"
                    size="sm"
                />
                <x-filament::button
                    wire:click="today"
                    color="gray"
                    size="sm"
                >
                    Today
                </x-filament::button>
                <x-filament::button
                    wire:click="nextPeriod"
                    icon="heroicon-o-chevron-right"
                    color="gray"
                    size="sm"
                />
                
                <span class="ml-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($currentDate)->format('F Y') }}
                </span>
            </div>

            {{-- View Mode Toggle --}}
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="setViewMode('day')"
                    :color="$viewMode === 'day' ? 'primary' : 'gray'"
                    size="sm"
                >
                    Day
                </x-filament::button>
                <x-filament::button
                    wire:click="setViewMode('week')"
                    :color="$viewMode === 'week' ? 'primary' : 'gray'"
                    size="sm"
                >
                    Week
                </x-filament::button>
                <x-filament::button
                    wire:click="setViewMode('month')"
                    :color="$viewMode === 'month' ? 'primary' : 'gray'"
                    size="sm"
                >
                    Month
                </x-filament::button>
            </div>

            {{-- Resource Filter --}}
            <div class="w-64">
                <select
                    wire:model.live="selectedResourceId"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                >
                    <option value="">All Resources</option>
                    @foreach($this->getResourceOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            @if($viewMode === 'month')
                {{-- Month View --}}
                <div class="grid grid-cols-7 border-b dark:border-gray-700">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="py-3 px-2 text-center text-sm font-medium text-gray-500 dark:text-gray-400 border-r last:border-r-0 dark:border-gray-700">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>
                
                @php
                    $startOfMonth = \Carbon\Carbon::parse($currentDate)->startOfMonth();
                    $endOfMonth = \Carbon\Carbon::parse($currentDate)->endOfMonth();
                    $startOfCalendar = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                    $endOfCalendar = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                    $events = collect($this->getCalendarEvents());
                @endphp
                
                <div class="grid grid-cols-7">
                    @for($date = $startOfCalendar->copy(); $date->lte($endOfCalendar); $date->addDay())
                        @php
                            $isCurrentMonth = $date->month === $startOfMonth->month;
                            $isToday = $date->isToday();
                            $dayEvents = $events->filter(function ($event) use ($date) {
                                $eventStart = \Carbon\Carbon::parse($event['start']);
                                return $eventStart->isSameDay($date);
                            });
                        @endphp
                        <div class="min-h-[120px] p-2 border-r border-b dark:border-gray-700 last:border-r-0 {{ !$isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900' : '' }}">
                            <div class="flex items-center justify-center mb-1">
                                <span class="text-sm font-medium {{ $isToday ? 'bg-primary-600 text-white rounded-full w-7 h-7 flex items-center justify-center' : ($isCurrentMonth ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600') }}">
                                    {{ $date->day }}
                                </span>
                            </div>
                            <div class="space-y-1 max-h-20 overflow-y-auto">
                                @foreach($dayEvents->take(3) as $event)
                                    <a
                                        href="{{ $event['url'] }}"
                                        class="block text-xs p-1 rounded truncate text-white"
                                        style="background-color: {{ $event['color'] }}"
                                        title="{{ $event['title'] }}"
                                    >
                                        {{ \Carbon\Carbon::parse($event['start'])->format('H:i') }} {{ Str::limit($event['title'], 15) }}
                                    </a>
                                @endforeach
                                @if($dayEvents->count() > 3)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        +{{ $dayEvents->count() - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            @elseif($viewMode === 'week')
                {{-- Week View --}}
                @php
                    $startOfWeek = \Carbon\Carbon::parse($currentDate)->startOfWeek(\Carbon\Carbon::SUNDAY);
                    $endOfWeek = \Carbon\Carbon::parse($currentDate)->endOfWeek(\Carbon\Carbon::SATURDAY);
                    $events = collect($this->getCalendarEvents());
                    $hours = range(8, 20); // 8 AM to 8 PM
                @endphp
                
                <div class="overflow-x-auto">
                    <div class="min-w-[800px]">
                        {{-- Header --}}
                        <div class="grid grid-cols-8 border-b dark:border-gray-700">
                            <div class="py-3 px-2 border-r dark:border-gray-700"></div>
                            @for($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay())
                                <div class="py-3 px-2 text-center border-r last:border-r-0 dark:border-gray-700 {{ $date->isToday() ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                                    <div class="text-sm font-medium {{ $date->isToday() ? 'text-primary-600' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $date->format('D') }}
                                    </div>
                                    <div class="text-lg font-semibold {{ $date->isToday() ? 'text-primary-600' : 'text-gray-900 dark:text-white' }}">
                                        {{ $date->day }}
                                    </div>
                                </div>
                            @endfor
                        </div>
                        
                        {{-- Time Grid --}}
                        @foreach($hours as $hour)
                            <div class="grid grid-cols-8 border-b dark:border-gray-700">
                                <div class="py-4 px-2 text-right text-xs text-gray-500 dark:text-gray-400 border-r dark:border-gray-700">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                                @for($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay())
                                    @php
                                        $slotEvents = $events->filter(function ($event) use ($date, $hour) {
                                            $eventStart = \Carbon\Carbon::parse($event['start']);
                                            return $eventStart->isSameDay($date) && $eventStart->hour === $hour;
                                        });
                                    @endphp
                                    <div class="py-1 px-1 min-h-[60px] border-r last:border-r-0 dark:border-gray-700 {{ $date->isToday() ? 'bg-primary-50/50 dark:bg-primary-900/10' : '' }}">
                                        @foreach($slotEvents as $event)
                                            <a
                                                href="{{ $event['url'] }}"
                                                class="block text-xs p-1 rounded mb-1 text-white truncate"
                                                style="background-color: {{ $event['color'] }}"
                                                title="{{ $event['title'] }}"
                                            >
                                                {{ \Carbon\Carbon::parse($event['start'])->format('H:i') }} - {{ Str::limit($event['title'], 20) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endfor
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Day View --}}
                @php
                    $currentDay = \Carbon\Carbon::parse($currentDate);
                    $events = collect($this->getCalendarEvents());
                    $hours = range(6, 22); // 6 AM to 10 PM
                @endphp
                
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $currentDay->format('l, F j, Y') }}
                    </h3>
                    
                    <div class="space-y-1">
                        @foreach($hours as $hour)
                            @php
                                $slotEvents = $events->filter(function ($event) use ($currentDay, $hour) {
                                    $eventStart = \Carbon\Carbon::parse($event['start']);
                                    return $eventStart->isSameDay($currentDay) && $eventStart->hour === $hour;
                                });
                            @endphp
                            <div class="flex border-b dark:border-gray-700 py-2">
                                <div class="w-20 text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                                <div class="flex-1 min-h-[40px]">
                                    @foreach($slotEvents as $event)
                                        <a
                                            href="{{ $event['url'] }}"
                                            class="block p-2 rounded mb-1 text-white"
                                            style="background-color: {{ $event['color'] }}"
                                        >
                                            <div class="font-medium text-sm">{{ $event['title'] }}</div>
                                            <div class="text-xs opacity-90">
                                                {{ \Carbon\Carbon::parse($event['start'])->format('H:i') }} - {{ \Carbon\Carbon::parse($event['end'])->format('H:i') }}
                                                · {{ $event['status'] }}
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Pending</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Confirmed</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Cancelled</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Completed</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
