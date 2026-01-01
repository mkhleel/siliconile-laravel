@extends('layouts.app')

@section('title', '{{ __('Incubation Programs') }}')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
            Incubation Programs
        </h1>
        <p class="mt-4 text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            {{ __('Join our incubation program to accelerate your startup\'s growth with mentorship,
            resources, and a supportive community.') }}
        </p>
    </div>

    {{-- Open Cohorts --}}
    @php
        $openCohorts = \Modules\Incubation\Models\Cohort::acceptingApplications()
            ->public()
            ->orderBy('application_end_date')
            ->get();
    @endphp

    @if($openCohorts->isNotEmpty())
        <div class="mb-16">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('Apply Now') }}
            </h2>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($openCohorts as $cohort)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Accepting Applications') }}
                                </span>
                                @if($cohort->application_end_date)
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        Closes {{ $cohort->application_end_date->diffForHumans() }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $cohort->name }}
                            </h3>

                            @if($cohort->description)
                                <p class="mt-2 text-gray-600 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($cohort->description, 150) }}
                                </p>
                            @endif

                            <div class="mt-4 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($cohort->start_date)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-calendar class="w-4 h-4" />
                                        Starts {{ $cohort->start_date->format('M j, Y') }}
                                    </span>
                                @endif
                                @if($cohort->capacity)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-users class="w-4 h-4" />
                                        {{ $cohort->capacity - $cohort->applications()->where('status', 'accepted')->count() }} spots left
                                    </span>
                                @endif
                            </div>

                            <a
                                href="{{ route('incubation.apply', $cohort) }}"
                                class="mt-6 w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition"
                            >
                                {{ __('Apply Now →') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Program Benefits --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-8 mb-16">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
            {{ __('What You\'ll Get') }}
        </h2>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="text-center">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-academic-cap class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Expert Mentorship') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('1-on-1 sessions with industry experts') }}
                </p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-building-office class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Workspace Access') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('Co-working space and meeting rooms') }}
                </p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-currency-dollar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Funding Support') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('Investor introductions and pitch prep') }}
                </p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-user-group class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Community</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('Network with fellow founders') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Check Application Status --}}
    <div class="text-center">
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            {{ __('Already applied? Check your application status.') }}
        </p>
        <form action="{{ route('incubation.application.status', ['applicationCode' => '__CODE__']) }}" method="GET" class="max-w-md mx-auto">
            <div class="flex gap-2">
                <input
                    type="text"
                    name="code"
                    placeholder="{{ __('Enter your application code (e.g., INC-2025-0001)') }}"
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                    required
                />
                <button
                    type="submit"
                    class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition"
                >
                    Check
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
