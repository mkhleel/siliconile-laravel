<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Models\Cohort;
use Modules\Incubation\Models\Mentor;

new
#[Layout('layouts.app')]
class extends Component
{
    public Cohort $cohort;

    public function mount(Cohort $cohort): void
    {
        // Only show public cohorts
        if (! in_array($cohort->status, [
            CohortStatus::OPEN_FOR_APPLICATIONS,
            CohortStatus::ACTIVE,
            CohortStatus::COMPLETED,
        ])) {
            abort(404);
        }

        $this->cohort = $cohort;
    }

    public function getTitle(): string
    {
        return "Siliconile | {$this->cohort->name}";
    }

    #[Computed]
    public function mentors()
    {
        return Mentor::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(4)
            ->get();
    }

    #[Computed]
    public function milestones()
    {
        return $this->cohort->milestones()
            ->orderBy('sort_order')
            ->get();
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row gap-8 items-center">
                    <div class="flex-1 space-y-6">
                        @php
                            $statusConfig = match($cohort->status) {
                                CohortStatus::OPEN_FOR_APPLICATIONS => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-800 dark:text-green-100', 'label' => 'Applications Open'],
                                CohortStatus::ACTIVE => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-100', 'label' => 'In Progress'],
                                CohortStatus::COMPLETED => ['bg' => 'bg-gray-100 dark:bg-gray-900', 'text' => 'text-gray-800 dark:text-gray-100', 'label' => 'Completed'],
                                default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Coming Soon'],
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                            {{ $statusConfig['label'] }}
                        </span>

                        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">{{ $cohort->name }}</h1>

                        @if($cohort->description)
                        <p class="text-xl text-muted-foreground">{{ $cohort->description }}</p>
                        @endif

                        <div class="flex flex-wrap gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $cohort->start_date->format('M d') }} - {{ $cohort->end_date->format('M d, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $cohort->duration_weeks }} weeks</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>{{ $cohort->capacity }} startups</span>
                            </div>
                        </div>

                        @if($cohort->isAcceptingApplications())
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="{{ route('incubation.apply', $cohort) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-12 px-8">
                                Apply Now
                                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            @if($cohort->application_end_date)
                            <div class="flex items-center text-sm text-muted-foreground">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Applications close {{ $cohort->application_end_date->diffForHumans() }}
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    @if($cohort->cover_image)
                    <div class="w-full md:w-96 aspect-video rounded-xl overflow-hidden shadow-xl">
                        <img src="{{ Storage::url($cohort->cover_image) }}" alt="{{ $cohort->name }}" class="w-full h-full object-cover">
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    @if($cohort->isAcceptingApplications())
    <section class="py-8 bg-card border-y">
        <div class="container px-4 md:px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold text-primary">{{ $cohort->capacity }}</div>
                    <div class="text-sm text-muted-foreground">Total Spots</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-green-600">{{ $cohort->available_spots }}</div>
                    <div class="text-sm text-muted-foreground">Available</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-primary">{{ $cohort->duration_weeks }}</div>
                    <div class="text-sm text-muted-foreground">Weeks</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-primary">{{ $cohort->applications()->count() }}</div>
                    <div class="text-sm text-muted-foreground">Applications</div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Benefits Section --}}
    @if($cohort->benefits)
    <section class="py-16">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">What You'll Get</h2>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($cohort->benefits as $benefit)
                    <div class="flex items-start gap-4 p-4 rounded-lg border bg-card">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium">{{ $benefit }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Milestones/Timeline Section --}}
    @if($this->milestones->isNotEmpty())
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">Program Milestones</h2>

                <div class="relative">
                    <div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-0.5 bg-primary/20"></div>

                    <div class="space-y-8">
                        @foreach($this->milestones as $index => $milestone)
                        <div class="relative flex items-start gap-6 {{ $index % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse' }}">
                            <div class="absolute left-4 md:left-1/2 w-4 h-4 rounded-full bg-primary transform -translate-x-1/2"></div>

                            <div class="ml-12 md:ml-0 md:w-1/2 {{ $index % 2 === 0 ? 'md:pr-12 md:text-right' : 'md:pl-12' }}">
                                <div class="rounded-lg border bg-card p-6 shadow-sm">
                                    <div class="text-sm text-primary font-medium mb-1">Week {{ $milestone->target_week ?? ($index + 1) }}</div>
                                    <h3 class="text-lg font-semibold mb-2">{{ $milestone->title }}</h3>
                                    @if($milestone->description)
                                    <p class="text-muted-foreground text-sm">{{ $milestone->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Eligibility Section --}}
    @if($cohort->eligibility_criteria)
    <section class="py-16">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">Eligibility Criteria</h2>

                <div class="rounded-xl border bg-card p-6 md:p-8">
                    <ul class="space-y-4">
                        @foreach($cohort->eligibility_criteria as $criteria)
                        <li class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-primary mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $criteria }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Mentors Section --}}
    @if($this->mentors->isNotEmpty())
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">Meet Our Mentors</h2>
                    <p class="text-muted-foreground">Get guidance from industry experts who have been there</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @foreach($this->mentors as $mentor)
                    <div class="rounded-lg border bg-card p-6 text-center">
                        @if($mentor->avatar)
                        <img src="{{ Storage::url($mentor->avatar) }}" alt="{{ $mentor->name }}" class="w-20 h-20 rounded-full mx-auto mb-4 object-cover">
                        @else
                        <div class="w-20 h-20 rounded-full mx-auto mb-4 bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-2xl font-bold text-primary">
                            {{ strtoupper(substr($mentor->name, 0, 2)) }}
                        </div>
                        @endif
                        <h3 class="font-semibold">{{ $mentor->name }}</h3>
                        <p class="text-sm text-primary">{{ $mentor->title }}</p>
                        @if($mentor->company)
                        <p class="text-xs text-muted-foreground">{{ $mentor->company }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- CTA Section --}}
    @if($cohort->isAcceptingApplications())
    <section class="py-20 bg-primary text-primary-foreground">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto text-center space-y-6">
                <h2 class="text-3xl md:text-4xl font-bold">Ready to Join {{ $cohort->name }}?</h2>
                <p class="text-xl opacity-90">Take the first step towards transforming your startup idea into reality.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('incubation.apply', $cohort) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-white text-primary hover:bg-white/90 h-11 px-8">
                        Start Your Application
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-white/30 hover:bg-white/10 h-11 px-8">
                        Have Questions?
                    </a>
                </div>
                @if($cohort->application_end_date)
                <p class="text-sm opacity-75">Applications close on {{ $cohort->application_end_date->format('F j, Y') }}</p>
                @endif
            </div>
        </div>
    </section>
    @else
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-xl mx-auto text-center space-y-6">
                <h3 class="text-2xl font-bold">Interested in Future Cohorts?</h3>
                <p class="text-muted-foreground">Get notified when applications open for our next program</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6">
                    Get Notified
                </a>
            </div>
        </div>
    </section>
    @endif
</main>
