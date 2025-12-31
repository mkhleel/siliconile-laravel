<div
    class="min-h-screen"
    x-data="{
        dragging: null,
        dragOver: null,
        moveApplication(applicationId, newStatus) {
            $wire.dispatch('application-moved', { applicationId, newStatus });
        }
    }"
>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Application Pipeline
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Drag and drop applications to change their status
                </p>
            </div>
            <div class="flex gap-2">
                {{ $this->getHeaderActions()[0] ?? '' }}
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($this->getStatuses() as $status)
            @php
                $applications = $this->getApplicationsByStatus()[$status['value']] ?? [];
                $statusColor = match($status['color']) {
                    'gray' => 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600',
                    'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700',
                    'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700',
                    'primary' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-300 dark:border-purple-700',
                    'success' => 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700',
                    'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700',
                    default => 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600',
                };
                $headerColor = match($status['color']) {
                    'gray' => 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                    'info' => 'bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200',
                    'warning' => 'bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200',
                    'primary' => 'bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200',
                    'success' => 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200',
                    'danger' => 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200',
                    default => 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                };
            @endphp

            <div class="flex-shrink-0 w-72">
                {{-- Column Header --}}
                <div class="rounded-t-lg px-3 py-2 {{ $headerColor }}">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-sm">{{ $status['label'] }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-white/50 dark:bg-black/20">
                            {{ count($applications) }}
                        </span>
                    </div>
                </div>

                {{-- Column Body (Drop Zone) --}}
                <div
                    class="min-h-[500px] rounded-b-lg border-2 border-dashed p-2 space-y-2 transition-colors {{ $statusColor }}"
                    :class="{ 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30': dragOver === '{{ $status['value'] }}' }"
                    x-on:dragover.prevent="dragOver = '{{ $status['value'] }}'"
                    x-on:dragleave="dragOver = null"
                    x-on:drop.prevent="
                        if (dragging) {
                            moveApplication(dragging, '{{ $status['value'] }}');
                            dragging = null;
                            dragOver = null;
                        }
                    "
                >
                    @forelse($applications as $application)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 cursor-move hover:shadow-md transition border border-gray-200 dark:border-gray-700"
                            draggable="true"
                            x-on:dragstart="dragging = {{ $application->id }}"
                            x-on:dragend="dragging = null"
                        >
                            {{-- Card Header --}}
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="min-w-0 flex-1">
                                    <a
                                        href="{{ \Modules\Incubation\Filament\Resources\ApplicationResource::getUrl('view', ['record' => $application]) }}"
                                        class="font-semibold text-gray-900 dark:text-white text-sm hover:text-primary-600 dark:hover:text-primary-400 truncate block"
                                    >
                                        {{ $application->startup_name }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $application->application_code }}
                                    </p>
                                </div>
                                @if($application->score)
                                    <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ number_format($application->score, 0) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Card Body --}}
                            <div class="space-y-2">
                                @if($application->cohort)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        <x-heroicon-o-academic-cap class="inline w-3 h-3" />
                                        {{ $application->cohort->name }}
                                    </p>
                                @endif

                                @if($application->stage)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $application->stage->getLabel() }}
                                    </span>
                                @endif

                                @if($application->interview_scheduled_at)
                                    <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                        <x-heroicon-o-calendar class="inline w-3 h-3" />
                                        {{ $application->interview_scheduled_at->format('M j, g:i A') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Card Footer --}}
                            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <p class="text-xs text-gray-400">
                                    {{ $application->created_at->diffForHumans() }}
                                </p>
                                <div class="flex gap-1">
                                    <a
                                        href="{{ \Modules\Incubation\Filament\Resources\ApplicationResource::getUrl('edit', ['record' => $application]) }}"
                                        class="p-1 text-gray-400 hover:text-primary-600 transition"
                                        title="Edit"
                                    >
                                        <x-heroicon-o-pencil class="w-4 h-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                            No applications
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
