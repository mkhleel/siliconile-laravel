<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Modules\Incubation\Models\Application;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Check Application Status')]
class extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|size:8')]
    public string $applicationCode = '';

    public ?Application $application = null;

    public bool $searched = false;

    public string $error = '';

    public function checkStatus(): void
    {
        $this->validate();
        $this->searched = true;
        $this->error = '';
        $this->application = null;

        $application = Application::query()
            ->where('application_code', strtoupper($this->applicationCode))
            ->where('email', $this->email)
            ->with(['cohort:id,name,start_date,end_date', 'statusHistory'])
            ->first();

        if (! $application) {
            $this->error = 'No application found with the provided details. Please check your email and application code.';

            return;
        }

        $this->application = $application;
    }

    public function resetSearch(): void
    {
        $this->reset(['email', 'applicationCode', 'application', 'searched', 'error']);
    }

    #[Computed]
    public function statusTimeline(): array
    {
        if (! $this->application) {
            return [];
        }

        $timeline = [];
        $history = $this->application->statusHistory()->orderBy('created_at')->get();

        foreach ($history as $entry) {
            $timeline[] = [
                'status' => $entry->new_status,
                'date' => $entry->created_at,
                'notes' => $entry->notes,
            ];
        }

        // If no history, just show current status
        if (empty($timeline)) {
            $timeline[] = [
                'status' => $this->application->status->value,
                'date' => $this->application->created_at,
                'notes' => 'Application submitted',
            ];
        }

        return $timeline;
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-16 md:py-24 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto text-center space-y-6">
                <h1 class="text-3xl md:text-5xl font-bold tracking-tight">Check Your <span class="text-primary">Application Status</span></h1>
                <p class="text-lg text-muted-foreground">Enter your email and application code to track the progress of your incubation program application.</p>
            </div>
        </div>
    </section>

    {{-- Status Checker Section --}}
    <section class="py-16">
        <div class="container px-4 md:px-6">
            <div class="max-w-xl mx-auto">
                @if(!$searched || $error)
                {{-- Search Form --}}
                <div class="rounded-xl border bg-card shadow-sm p-6 md:p-8">
                    <form wire:submit="checkStatus" class="space-y-6">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium leading-none">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                placeholder="Enter the email used in your application"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                            @error('email')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="applicationCode" class="text-sm font-medium leading-none">Application Code</label>
                            <input
                                type="text"
                                id="applicationCode"
                                wire:model="applicationCode"
                                placeholder="e.g., APP12345"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 uppercase"
                                maxlength="8"
                            >
                            <p class="text-xs text-muted-foreground">You received this code in your application confirmation email</p>
                            @error('applicationCode')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($error)
                        <div class="rounded-lg bg-destructive/10 border border-destructive/20 p-4">
                            <div class="flex items-center space-x-2">
                                <svg class="h-5 w-5 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-destructive">{{ $error }}</p>
                            </div>
                        </div>
                        @endif

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center w-full whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Check Status</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Checking...
                            </span>
                        </button>
                    </form>
                </div>
                @endif

                @if($application)
                {{-- Application Status Card --}}
                <div class="space-y-6">
                    {{-- Status Overview --}}
                    <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                        <div class="p-6 md:p-8 space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Application Code</p>
                                    <p class="text-lg font-mono font-bold">{{ $application->application_code }}</p>
                                </div>
                                @php
                                    $statusConfig = match($application->status) {
                                        ApplicationStatus::SUBMITTED => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-100', 'icon' => '📝'],
                                        ApplicationStatus::UNDER_REVIEW => ['bg' => 'bg-yellow-100 dark:bg-yellow-900', 'text' => 'text-yellow-800 dark:text-yellow-100', 'icon' => '🔍'],
                                        ApplicationStatus::SCREENING => ['bg' => 'bg-orange-100 dark:bg-orange-900', 'text' => 'text-orange-800 dark:text-orange-100', 'icon' => '📋'],
                                        ApplicationStatus::INTERVIEW_SCHEDULED => ['bg' => 'bg-purple-100 dark:bg-purple-900', 'text' => 'text-purple-800 dark:text-purple-100', 'icon' => '📅'],
                                        ApplicationStatus::INTERVIEW_COMPLETED => ['bg' => 'bg-indigo-100 dark:bg-indigo-900', 'text' => 'text-indigo-800 dark:text-indigo-100', 'icon' => '✅'],
                                        ApplicationStatus::ACCEPTED => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-800 dark:text-green-100', 'icon' => '🎉'],
                                        ApplicationStatus::REJECTED => ['bg' => 'bg-red-100 dark:bg-red-900', 'text' => 'text-red-800 dark:text-red-100', 'icon' => '❌'],
                                        ApplicationStatus::WAITLISTED => ['bg' => 'bg-gray-100 dark:bg-gray-900', 'text' => 'text-gray-800 dark:text-gray-100', 'icon' => '⏳'],
                                        ApplicationStatus::WITHDRAWN => ['bg' => 'bg-gray-100 dark:bg-gray-900', 'text' => 'text-gray-600 dark:text-gray-400', 'icon' => '🚫'],
                                        default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => '📄'],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                    <span class="mr-1.5">{{ $statusConfig['icon'] }}</span>
                                    {{ $application->status->label() }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <p class="text-sm text-muted-foreground">Startup Name</p>
                                    <p class="font-semibold">{{ $application->startup_name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Cohort</p>
                                    <p class="font-semibold">{{ $application->cohort?->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Submitted</p>
                                    <p class="font-semibold">{{ $application->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Industry</p>
                                    <p class="font-semibold">{{ $application->industry ?? 'Not specified' }}</p>
                                </div>
                            </div>

                            @if($application->status === ApplicationStatus::INTERVIEW_SCHEDULED && $application->interview_scheduled_at)
                            <div class="rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-4">
                                <h4 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">🗓️ Interview Scheduled</h4>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Date:</strong> {{ $application->interview_scheduled_at->format('l, F j, Y') }}</p>
                                    <p><strong>Time:</strong> {{ $application->interview_scheduled_at->format('g:i A') }}</p>
                                    @if($application->interview_location)
                                    <p><strong>Location:</strong> {{ $application->interview_location }}</p>
                                    @endif
                                    @if($application->interview_meeting_link)
                                    <p><a href="{{ $application->interview_meeting_link }}" target="_blank" class="text-primary hover:underline">Join Meeting →</a></p>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($application->status === ApplicationStatus::ACCEPTED)
                            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2">🎉 Congratulations!</h4>
                                <p class="text-sm text-green-700 dark:text-green-300">Your application has been accepted! Check your email for onboarding instructions and next steps.</p>
                                @if($application->cohort?->start_date)
                                <p class="text-sm text-green-700 dark:text-green-300 mt-2"><strong>Program starts:</strong> {{ $application->cohort->start_date->format('F j, Y') }}</p>
                                @endif
                            </div>
                            @endif

                            @if($application->status === ApplicationStatus::REJECTED && $application->rejection_reason)
                            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                                <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">Application Update</h4>
                                <p class="text-sm text-red-700 dark:text-red-300">{{ $application->rejection_reason }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Timeline --}}
                    @if(count($this->statusTimeline) > 0)
                    <div class="rounded-xl border bg-card shadow-sm p-6 md:p-8">
                        <h3 class="text-lg font-semibold mb-6">Application Timeline</h3>
                        <div class="space-y-4">
                            @foreach($this->statusTimeline as $index => $entry)
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-3 h-3 rounded-full {{ $index === count($this->statusTimeline) - 1 ? 'bg-primary' : 'bg-muted-foreground/30' }}"></div>
                                    @if($index < count($this->statusTimeline) - 1)
                                    <div class="w-0.5 h-full bg-muted-foreground/20 my-1"></div>
                                    @endif
                                </div>
                                <div class="pb-4">
                                    <p class="font-medium">{{ ApplicationStatus::tryFrom($entry['status'])?->label() ?? ucfirst($entry['status']) }}</p>
                                    <p class="text-sm text-muted-foreground">{{ $entry['date']->format('M d, Y - g:i A') }}</p>
                                    @if($entry['notes'])
                                    <p class="text-sm text-muted-foreground mt-1">{{ $entry['notes'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button
                            wire:click="resetSearch"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-6"
                        >
                            Check Another Application
                        </button>
                        <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6">
                            Contact Support
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold mb-8 text-center">Frequently Asked Questions</h2>

                <div class="space-y-4">
                    <div class="rounded-lg border bg-card p-4" x-data="{ open: false }">
                        <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                            <span class="font-medium">Where can I find my application code?</span>
                            <svg :class="{ 'rotate-180': open }" class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-3 text-sm text-muted-foreground">
                            Your application code was sent to your email immediately after you submitted your application. Check your inbox (and spam folder) for an email from Siliconile with the subject "Application Received".
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4" x-data="{ open: false }">
                        <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                            <span class="font-medium">How long does the review process take?</span>
                            <svg :class="{ 'rotate-180': open }" class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-3 text-sm text-muted-foreground">
                            The typical review process takes 2-3 weeks from submission to final decision. You'll receive email notifications at each stage of the process.
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4" x-data="{ open: false }">
                        <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                            <span class="font-medium">What happens after my interview?</span>
                            <svg :class="{ 'rotate-180': open }" class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-3 text-sm text-muted-foreground">
                            After your interview, our team will review all candidates and make final selections within 5-7 business days. You'll receive an email notification with the decision.
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4" x-data="{ open: false }">
                        <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                            <span class="font-medium">Can I update my application after submission?</span>
                            <svg :class="{ 'rotate-180': open }" class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-3 text-sm text-muted-foreground">
                            While you cannot directly edit your submitted application, you can contact us with any updates or additional materials you'd like to add. Email us at programs@siliconile.com.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
