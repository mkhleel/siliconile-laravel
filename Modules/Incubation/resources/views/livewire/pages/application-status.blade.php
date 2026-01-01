<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Modules\Incubation\Models\Application;

new class extends Component
{
    public Application $application;

    public function mount(string $applicationCode): void
    {
        $this->application = Application::where('application_code', $applicationCode)
            ->with(['cohort', 'statusHistory'])
            ->firstOrFail();
    }

    public function getStatusTimelineProperty(): array
    {
        return $this->application->statusHistory()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($history) => [
                'status' => $history->to_status->getLabel(),
                'color' => $history->to_status->getColor(),
                'icon' => $history->to_status->getIcon(),
                'date' => $history->created_at->format('M j, Y g:i A'),
                'notes' => $history->notes,
            ])
            ->toArray();
    }
}; ?>

<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('Application Status') }}
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ __('Track your application progress') }}
        </p>
    </div>

    {{-- Application Summary Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                    {{ $application->application_code }}
                </p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ $application->startup_name }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $application->cohort->name }}
                </p>
            </div>

            <div class="text-right">
                <span @class([
                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $application->status->getColor() === 'gray',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $application->status->getColor() === 'info',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' => $application->status->getColor() === 'warning',
                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $application->status->getColor() === 'primary',
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $application->status->getColor() === 'success',
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $application->status->getColor() === 'danger',
                ])>
                    {{ $application->status->getLabel() }}
                </span>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Applied {{ $application->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        @if($application->interview_scheduled_at && $application->interview_scheduled_at->isFuture())
            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-calendar class="w-6 h-6 text-yellow-600" />
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('Interview Scheduled') }}
                        </p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            {{ $application->interview_scheduled_at->format('l, F j, Y \a\t g:i A') }}
                        </p>
                        @if($application->interview_meeting_link)
                            <a
                                href="{{ $application->interview_meeting_link }}"
                                target="_blank"
                                class="text-sm text-yellow-600 hover:underline mt-1 inline-block"
                            >
                                Join Meeting →
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Status Timeline --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            {{ __('Status History') }}
        </h3>

        <div class="space-y-4">
            @forelse($this->statusTimeline as $index => $entry)
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div @class([
                            'w-10 h-10 rounded-full flex items-center justify-center',
                            'bg-gray-100 dark:bg-gray-700' => $entry['color'] === 'gray',
                            'bg-blue-100 dark:bg-blue-900' => $entry['color'] === 'info',
                            'bg-yellow-100 dark:bg-yellow-900' => $entry['color'] === 'warning',
                            'bg-purple-100 dark:bg-purple-900' => $entry['color'] === 'primary',
                            'bg-green-100 dark:bg-green-900' => $entry['color'] === 'success',
                            'bg-red-100 dark:bg-red-900' => $entry['color'] === 'danger',
                        ])>
                            <x-dynamic-component
                                :component="$entry['icon']"
                                @class([
                                    'w-5 h-5',
                                    'text-gray-600 dark:text-gray-400' => $entry['color'] === 'gray',
                                    'text-blue-600 dark:text-blue-400' => $entry['color'] === 'info',
                                    'text-yellow-600 dark:text-yellow-400' => $entry['color'] === 'warning',
                                    'text-purple-600 dark:text-purple-400' => $entry['color'] === 'primary',
                                    'text-green-600 dark:text-green-400' => $entry['color'] === 'success',
                                    'text-red-600 dark:text-red-400' => $entry['color'] === 'danger',
                                ])
                            />
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 h-full bg-gray-200 dark:bg-gray-700 my-2"></div>
                        @endif
                    </div>

                    <div class="flex-1 pb-4">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $entry['status'] }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $entry['date'] }}
                        </p>
                        @if($entry['notes'])
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {{ $entry['notes'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                    {{ __('No status updates yet.') }}
                </p>
            @endforelse
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
        <p>
            Have questions about your application?
            <a href="mailto:incubation@siliconile.com" class="text-primary-600 hover:underline">
                {{ __('Contact us') }}
            </a>
        </p>
    </div>
</div>
