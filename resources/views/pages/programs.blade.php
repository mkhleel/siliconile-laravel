<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Models\Cohort;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Programs & Support')]
class extends Component
{
    #[Computed]
    public function openCohorts()
    {
        return Cohort::query()
            ->acceptingApplications()
            ->orderBy('application_end_date')
            ->get();
    }

    #[Computed]
    public function upcomingCohorts()
    {
        return Cohort::query()
            ->where('status', CohortStatus::DRAFT)
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->take(3)
            ->get();
    }

    #[Computed]
    public function activeCohorts()
    {
        return Cohort::query()
            ->where('status', CohortStatus::ACTIVE)
            ->withCount('applications')
            ->orderBy('start_date', 'desc')
            ->take(2)
            ->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'total_cohorts' => Cohort::whereIn('status', [CohortStatus::ACTIVE, CohortStatus::COMPLETED])->count(),
            'total_accepted' => Application::where('status', ApplicationStatus::ACCEPTED)->count(),
            'total_graduated' => Cohort::where('status', CohortStatus::COMPLETED)->sum('accepted_count'),
            'success_rate' => 85, // This could be calculated from actual data
        ];
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Our <span class="text-primary">Programs</span></h1>
                <p class="text-xl md:text-2xl text-muted-foreground">{{ __('Comprehensive support programs designed to take your startup from idea to scale, with world-class resources and expert guidance every step of the way.') }}</p>
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    <section class="py-16 bg-card border-y">
        <div class="container px-4 md:px-6">
            <div class="grid gap-8 md:grid-cols-4 text-center">
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['total_cohorts'] ?: '10+' }}</div>
                    <div class="text-lg font-semibold">{{ __('Program Cohorts') }}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['total_accepted'] ?: '50+' }}</div>
                    <div class="text-lg font-semibold">{{ __('Startups Accepted') }}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['total_graduated'] ?: '40+' }}</div>
                    <div class="text-lg font-semibold">Graduates</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['success_rate'] }}%</div>
                    <div class="text-lg font-semibold">{{ __('Success Rate') }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Open Cohorts Section --}}
    @if($this->openCohorts->isNotEmpty())
    <section class="py-20 bg-gradient-to-b from-primary/5 to-transparent">
        <div class="container px-4 md:px-6">
            <div class="text-center space-y-4 mb-12">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    Applications Open
                </span>
                <h2 class="text-3xl md:text-5xl font-bold">{{ __('Apply Now') }}</h2>
                <p class="text-xl text-muted-foreground max-w-2xl mx-auto">{{ __('Join our next cohort and transform your startup idea into reality') }}</p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-{{ min($this->openCohorts->count(), 3) }}">
                @foreach($this->openCohorts as $cohort)
                <div class="rounded-xl border-2 border-primary/20 bg-card text-card-foreground shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    @if($cohort->cover_image)
                    <div class="aspect-video bg-gradient-to-br from-primary/20 to-secondary/20 overflow-hidden">
                        <img src="{{ Storage::url($cohort->cover_image) }}" alt="{{ $cohort->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    @else
                    <div class="aspect-video bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                        <svg class="w-16 h-16 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    @endif

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
                                {{ __('Open for Applications') }}
                            </span>
                            @if($cohort->application_end_date)
                            <span class="text-sm text-muted-foreground">
                                Closes {{ $cohort->application_end_date->diffForHumans() }}
                            </span>
                            @endif
                        </div>

                        <h3 class="text-2xl font-bold group-hover:text-primary transition-colors">{{ $cohort->name }}</h3>
                        
                        @if($cohort->description)
                        <p class="text-muted-foreground line-clamp-2">{{ $cohort->description }}</p>
                        @endif

                        <div class="grid grid-cols-2 gap-4 py-4 border-t border-b">
                            <div>
                                <div class="text-sm text-muted-foreground">Starts</div>
                                <div class="font-semibold">{{ $cohort->start_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Duration</div>
                                <div class="font-semibold">{{ $cohort->duration_weeks }} weeks</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Capacity</div>
                                <div class="font-semibold">{{ $cohort->capacity }} startups</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Available Spots</div>
                                <div class="font-semibold text-green-600">{{ $cohort->available_spots }}</div>
                            </div>
                        </div>

                        @if($cohort->benefits)
                        <div class="space-y-2">
                            <div class="text-sm font-medium">{{ __('What You Get:') }}</div>
                            <ul class="space-y-1">
                                @foreach(array_slice($cohort->benefits, 0, 4) as $benefit)
                                <li class="flex items-center space-x-2 text-sm">
                                    <svg class="h-4 w-4 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ $benefit }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="pt-4">
                            <a href="{{ route('incubation.apply', $cohort) }}" class="inline-flex items-center justify-center w-full whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8">
                                Apply Now
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Active Programs Section --}}
    @if($this->activeCohorts->isNotEmpty())
    <section class="py-20">
        <div class="container px-4 md:px-6">
            <div class="text-center space-y-4 mb-12">
                <h2 class="text-3xl md:text-4xl font-bold">{{ __('Currently Running') }}</h2>
                <p class="text-xl text-muted-foreground max-w-2xl mx-auto">{{ __('Our active incubation programs nurturing the next generation of startups') }}</p>
            </div>

            <div class="grid gap-8 md:grid-cols-2">
                @foreach($this->activeCohorts as $cohort)
                <div class="rounded-lg border bg-card p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100">
                            {{ __('In Progress') }}
                        </span>
                        <span class="text-sm text-muted-foreground">{{ $cohort->applications_count }} Startups</span>
                    </div>
                    <h3 class="text-xl font-bold">{{ $cohort->name }}</h3>
                    <div class="flex items-center space-x-4 text-sm text-muted-foreground">
                        <span>{{ $cohort->start_date->format('M Y') }} - {{ $cohort->end_date->format('M Y') }}</span>
                    </div>
                    <div class="w-full bg-muted rounded-full h-2">
                        @php
                            $progress = $cohort->start_date->diffInDays(now()) / max(1, $cohort->start_date->diffInDays($cohort->end_date)) * 100;
                            $progress = min(100, max(0, $progress));
                        @endphp
                        <div class="bg-primary h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                    <div class="text-sm text-muted-foreground">{{ round($progress) }}{{ __('% Complete') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Program Details Section --}}
    <section class="py-20 md:py-32 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="space-y-20">
                <div class="grid gap-8 lg:grid-cols-2 items-center">
                    <div class="space-y-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-primary font-semibold text-sm uppercase tracking-wide">{{ __('Flagship Program') }}</div>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold">{{ __('Startup Incubation Program') }}</h2>
                        <p class="text-lg text-muted-foreground">{{ __('Our flagship 6-month intensive program for early-stage startups. Get everything you need to validate your idea, build your MVP, and secure your first customers.') }}</p>
                        
                        <div class="space-y-2">
                            <h3 class="text-xl font-semibold">What You Get:</h3>
                            <ul class="space-y-2">
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ __('Dedicated office space for 6 months') }}</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>$10,000 seed funding</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ __('Weekly 1-on-1 mentorship sessions') }}</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ __('Demo Day presentation to investors') }}</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ __('Access to investor network') }}</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ __('Legal & accounting support') }}</span>
                                </li>
                            </ul>
                        </div>

                        @if($this->openCohorts->isNotEmpty())
                        <a href="{{ route('incubation.apply', $this->openCohorts->first()) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8">
                            Apply for Next Cohort
                        </a>
                        @else
                        <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8">
                            {{ __('Get Notified') }}
                        </a>
                        @endif
                    </div>

                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-8 bg-gradient-to-br from-primary/5 to-secondary/10">
                        <h3 class="text-2xl font-bold mb-6">{{ __('Program Timeline') }}</h3>
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold shrink-0">1</div>
                                <div>
                                    <div class="font-semibold">Application & Selection</div>
                                    <div class="text-sm text-muted-foreground">{{ __('2 weeks • Submit application, interviews, final selection') }}</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold shrink-0">2</div>
                                <div>
                                    <div class="font-semibold">Onboarding & Discovery</div>
                                    <div class="text-sm text-muted-foreground">{{ __('2 weeks • Team building, mentor matching, goal setting') }}</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold shrink-0">3</div>
                                <div>
                                    <div class="font-semibold">Build & Validate</div>
                                    <div class="text-sm text-muted-foreground">{{ __('12 weeks • MVP development, customer validation, iteration') }}</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold shrink-0">4</div>
                                <div>
                                    <div class="font-semibold">Scale & Launch</div>
                                    <div class="text-sm text-muted-foreground">{{ __('6 weeks • Go-to-market, fundraising preparation') }}</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-bold shrink-0">🎓</div>
                                <div>
                                    <div class="font-semibold">Demo Day & Graduation</div>
                                    <div class="text-sm text-muted-foreground">{{ __('1 week • Pitch to investors, alumni network access') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Eligibility Section --}}
    <section class="py-20">
        <div class="container px-4 md:px-6">
            <div class="text-center space-y-4 mb-12">
                <h2 class="text-3xl md:text-4xl font-bold">{{ __('Who Should Apply?') }}</h2>
                <p class="text-xl text-muted-foreground max-w-2xl mx-auto">{{ __('Our program is designed for ambitious founders ready to take their startup to the next level') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Early-Stage Startups') }}</h3>
                    <p class="text-muted-foreground">{{ __('Idea to MVP stage with a clear problem-solution fit') }}</p>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Committed Teams') }}</h3>
                    <p class="text-muted-foreground">{{ __('At least one full-time founder dedicated to the venture') }}</p>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Tech-Enabled Solutions') }}</h3>
                    <p class="text-muted-foreground">{{ __('Technology-driven products or services') }}</p>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Scalable Business Model') }}</h3>
                    <p class="text-muted-foreground">{{ __('Clear path to growth and revenue generation') }}</p>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('MENA Focus') }}</h3>
                    <p class="text-muted-foreground">{{ __('Targeting Egypt or broader MENA region markets') }}</p>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-3">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Pre-Seed / Seed Stage') }}</h3>
                    <p class="text-muted-foreground">Raised less than $500K in funding</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-primary text-primary-foreground">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto text-center space-y-6">
                <h2 class="text-3xl md:text-4xl font-bold">{{ __('Ready to Transform Your Startup?') }}</h2>
                <p class="text-xl opacity-90">{{ __('Join hundreds of founders who have accelerated their journey with Siliconile') }}</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($this->openCohorts->isNotEmpty())
                    <a href="{{ route('incubation.apply', $this->openCohorts->first()) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-white text-primary hover:bg-white/90 h-11 px-8">
                        Apply Now
                    </a>
                    @endif
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-white/30 hover:bg-white/10 h-11 px-8">
                        {{ __('Schedule a Call') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Track Application Section --}}
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-xl mx-auto text-center space-y-6">
                <h3 class="text-2xl font-bold">{{ __('Already Applied?') }}</h3>
                <p class="text-muted-foreground">{{ __('Track the status of your application') }}</p>
                <a href="{{ route('incubation.status') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-6">
                    {{ __('Check Application Status') }}
                </a>
            </div>
        </div>
    </section>
</main>
