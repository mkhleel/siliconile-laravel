<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Models\Application;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Startups & Portfolio')]
class extends Component
{
    public string $selectedFilter = 'all';

    public string $selectedIndustry = '';

    public string $search = '';

    #[Computed]
    public function startups()
    {
        return Application::query()
            ->where('status', ApplicationStatus::ACCEPTED)
            ->when($this->selectedIndustry, fn ($q) => $q->where('industry', $this->selectedIndustry))
            ->when($this->search, fn ($q) => $q->where('startup_name', 'like', "%{$this->search}%"))
            ->when($this->selectedFilter === 'graduated', fn ($q) => $q->whereHas('cohort', fn ($cq) => $cq->where('status', CohortStatus::COMPLETED)))
            ->when($this->selectedFilter === 'current', fn ($q) => $q->whereHas('cohort', fn ($cq) => $cq->where('status', CohortStatus::ACTIVE)))
            ->with('cohort:id,name,status,end_date')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function industries()
    {
        return Application::query()
            ->where('status', ApplicationStatus::ACCEPTED)
            ->whereNotNull('industry')
            ->distinct()
            ->pluck('industry')
            ->filter()
            ->values();
    }

    #[Computed]
    public function stats()
    {
        $accepted = Application::where('status', ApplicationStatus::ACCEPTED);

        return [
            'portfolio_count' => $accepted->count() ?: 12,
            'total_funding' => '2M+ EGP',
            'jobs_created' => '200+',
            'success_rate' => '85%',
        ];
    }

    public function setFilter(string $filter): void
    {
        $this->selectedFilter = $filter;
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Our <span class="text-primary">Portfolio</span></h1>
                <p class="text-xl md:text-2xl text-muted-foreground">Meet the innovative startups that are part of the Siliconile family. These companies are driving digital transformation across Egypt and the MENA region.</p>
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    <section class="py-20">
        <div class="container px-4 md:px-6">
            <div class="grid gap-8 md:grid-cols-4 text-center mb-16">
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['portfolio_count'] }}+</div>
                    <div class="text-lg font-semibold">Portfolio Companies</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['total_funding'] }}</div>
                    <div class="text-lg font-semibold">Total Funding Raised</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['jobs_created'] }}</div>
                    <div class="text-lg font-semibold">Jobs Created</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-5xl font-bold text-primary">{{ $this->stats['success_rate'] }}</div>
                    <div class="text-lg font-semibold">Success Rate</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filter & Search Section --}}
    <section class="py-8 bg-muted/50 border-y sticky top-0 z-10">
        <div class="container px-4 md:px-6">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                {{-- Filter Tabs --}}
                <div class="flex gap-2">
                    <button
                        wire:click="setFilter('all')"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $selectedFilter === 'all' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted' }}"
                    >
                        All Startups
                    </button>
                    <button
                        wire:click="setFilter('current')"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $selectedFilter === 'current' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted' }}"
                    >
                        Current Cohort
                    </button>
                    <button
                        wire:click="setFilter('graduated')"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $selectedFilter === 'graduated' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted' }}"
                    >
                        Graduated
                    </button>
                </div>

                <div class="flex gap-4 items-center">
                    {{-- Industry Filter --}}
                    @if($this->industries->isNotEmpty())
                    <select wire:model.live="selectedIndustry" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option value="">All Industries</option>
                        @foreach($this->industries as $industry)
                        <option value="{{ $industry }}">{{ $industry }}</option>
                        @endforeach
                    </select>
                    @endif

                    {{-- Search --}}
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search startups..."
                            class="pl-10 pr-4 py-2 rounded-md border border-input bg-background text-sm w-64"
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Startups Grid --}}
    <section class="py-20">
        <div class="container px-4 md:px-6">
            @if($this->startups->isEmpty())
                {{-- Empty State with Sample Data --}}
                <div class="text-center space-y-4 mb-16">
                    <h2 class="text-3xl md:text-5xl font-bold">Featured Startups</h2>
                    <p class="text-xl text-muted-foreground max-w-2xl mx-auto">Discover the innovative companies building the future of technology in Egypt</p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    {{-- Sample Startup Cards --}}
                    @foreach([
                        ['initials' => 'TF', 'name' => 'TechFlow Solutions', 'desc' => 'AI-powered workflow automation platform for SMEs', 'stage' => 'Series A', 'funding' => '$500K', 'industry' => 'SaaS'],
                        ['initials' => 'EE', 'name' => 'EduTech Egypt', 'desc' => 'Digital learning platform for Arabic-speaking students', 'stage' => 'Seed', 'funding' => '$250K', 'industry' => 'EdTech'],
                        ['initials' => 'AS', 'name' => 'AgriSmart', 'desc' => 'IoT solutions for precision agriculture in Egypt', 'stage' => 'Pre-Series A', 'funding' => '$150K', 'industry' => 'AgriTech'],
                        ['initials' => 'FP', 'name' => 'FinPay', 'desc' => 'Mobile payment solutions for the unbanked', 'stage' => 'Seed', 'funding' => '$300K', 'industry' => 'FinTech'],
                        ['initials' => 'HC', 'name' => 'HealthConnect', 'desc' => 'Telemedicine platform connecting patients with doctors', 'stage' => 'Pre-Seed', 'funding' => '$100K', 'industry' => 'HealthTech'],
                        ['initials' => 'LG', 'name' => 'LogiTrack', 'desc' => 'Last-mile delivery optimization for e-commerce', 'stage' => 'Seed', 'funding' => '$200K', 'industry' => 'Logistics'],
                    ] as $startup)
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div class="flex flex-col space-y-1.5 p-6">
                            <div class="flex items-start justify-between">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                                    {{ $startup['initials'] }}
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
                                    Graduated
                                </span>
                            </div>
                            <h3 class="text-2xl font-semibold leading-none tracking-tight pt-4 group-hover:text-primary transition-colors">{{ $startup['name'] }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $startup['desc'] }}</p>
                        </div>
                        <div class="p-6 pt-0 space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-muted">{{ $startup['industry'] }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 pt-2 border-t">
                                <div>
                                    <div class="text-xs text-muted-foreground">Stage</div>
                                    <div class="font-semibold text-sm">{{ $startup['stage'] }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Funding</div>
                                    <div class="font-semibold text-sm">{{ $startup['funding'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                {{-- Real Data --}}
                <div class="text-center space-y-4 mb-16">
                    <h2 class="text-3xl md:text-5xl font-bold">{{ $selectedFilter === 'graduated' ? 'Graduated Startups' : ($selectedFilter === 'current' ? 'Current Cohort' : 'All Startups') }}</h2>
                    <p class="text-xl text-muted-foreground max-w-2xl mx-auto">{{ $this->startups->count() }} startups building the future</p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->startups as $startup)
                    <div wire:key="startup-{{ $startup->id }}" class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div class="flex flex-col space-y-1.5 p-6">
                            <div class="flex items-start justify-between">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                                    {{ strtoupper(substr($startup->startup_name, 0, 2)) }}
                                </div>
                                @if($startup->cohort?->status === \Modules\Incubation\Enums\CohortStatus::COMPLETED)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
                                    Graduated
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100">
                                    Active
                                </span>
                                @endif
                            </div>
                            <h3 class="text-2xl font-semibold leading-none tracking-tight pt-4 group-hover:text-primary transition-colors">{{ $startup->startup_name }}</h3>
                            <p class="text-sm text-muted-foreground line-clamp-2">{{ $startup->solution }}</p>
                        </div>
                        <div class="p-6 pt-0 space-y-3">
                            <div class="flex flex-wrap gap-2">
                                @if($startup->industry)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-muted">{{ $startup->industry }}</span>
                                @endif
                                @if($startup->stage)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary/10 text-primary">{{ $startup->stage }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-2 pt-2 border-t">
                                <div>
                                    <div class="text-xs text-muted-foreground">Cohort</div>
                                    <div class="font-semibold text-sm">{{ $startup->cohort?->name ?? 'N/A' }}</div>
                                </div>
                                @if($startup->funding_raised)
                                <div>
                                    <div class="text-xs text-muted-foreground">Funding</div>
                                    <div class="font-semibold text-sm">{{ number_format($startup->funding_raised) }} {{ $startup->funding_currency }}</div>
                                </div>
                                @endif
                            </div>
                            @if($startup->website_url)
                            <a href="{{ $startup->website_url }}" target="_blank" rel="noopener" class="inline-flex items-center text-sm text-primary hover:underline">
                                Visit Website
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Success Stories Section --}}
    <section class="py-20 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="text-center space-y-4 mb-16">
                <h2 class="text-3xl md:text-4xl font-bold">Success Stories</h2>
                <p class="text-xl text-muted-foreground max-w-2xl mx-auto">Hear from founders who transformed their ideas into successful businesses</p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border bg-card p-6 space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                            AM
                        </div>
                        <div>
                            <div class="font-semibold">Ahmed Mohamed</div>
                            <div class="text-sm text-muted-foreground">Founder, TechFlow Solutions</div>
                        </div>
                    </div>
                    <p class="text-muted-foreground italic">"The mentorship and network access at Siliconile were invaluable. We went from a prototype to raising our Series A in just 18 months."</p>
                    <div class="flex items-center space-x-2 text-sm text-primary">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <span>Cohort 2022</span>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                            SH
                        </div>
                        <div>
                            <div class="font-semibold">Sara Hassan</div>
                            <div class="text-sm text-muted-foreground">Co-founder, EduTech Egypt</div>
                        </div>
                    </div>
                    <p class="text-muted-foreground italic">"Being part of the Siliconile community opened doors we didn't know existed. The connections and support system are second to none."</p>
                    <div class="flex items-center space-x-2 text-sm text-primary">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <span>Cohort 2023</span>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                            MK
                        </div>
                        <div>
                            <div class="font-semibold">Mohamed Khaled</div>
                            <div class="text-sm text-muted-foreground">Founder, AgriSmart</div>
                        </div>
                    </div>
                    <p class="text-muted-foreground italic">"From farm to funding - Siliconile helped us understand our market and build a product farmers actually need. Won the Egypt Startup Awards 2024!"</p>
                    <div class="flex items-center space-x-2 text-sm text-primary">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <span>Cohort 2024</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-primary text-primary-foreground">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto text-center space-y-6">
                <h2 class="text-3xl md:text-4xl font-bold">Join Our Portfolio</h2>
                <p class="text-xl opacity-90">Ready to transform your startup idea into reality? Apply to our next cohort and become part of the Siliconile family.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('programs') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-white text-primary hover:bg-white/90 h-11 px-8">
                        View Programs
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-white/30 hover:bg-white/10 h-11 px-8">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
