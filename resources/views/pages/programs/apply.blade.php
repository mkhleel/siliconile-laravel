<?php

declare(strict_types=1);

use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Events\ApplicationSubmitted;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Models\Cohort;

new
#[Layout('layouts.app')]
#[Title('Apply to Startup {{ __('Incubation Program') }} | Siliconile')]
class extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    public int $totalSteps = 4;

    // Cohort Selection
    public ?int $cohortId = null;

    // Step 1: {{ __('Founder Information') }}
    #[Validate('required|string|max:255')]
    public string $founder{{ __('Name') }} = '';

    #[Validate('required|email|max:255')]
    public string $founder{{ __('Email') }} = '';

    #[Validate('nullable|string|max:50')]
    public string $founderPhone = '';

    #[Validate('nullable|url|max:500')]
    public string $founderLinkedin = '';

    #[Validate('nullable|string|max:500')]
    public string $founderBio = '';

    // Co-founders
    public array $coFounders = [];

    // Step 2: {{ __('Startup Information') }}
    #[Validate('required|string|max:255')]
    public string $startupName = '';

    #[Validate('nullable|url|max:500')]
    public string $websiteUrl = '';

    #[Validate('required|string|max:100')]
    public string $industry = '';

    #[Validate('required|string')]
    public string $stage = 'idea';

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:2000')]
    public string $problem = '';

    #[Validate('required|string|max:2000')]
    public string $solution = '';

    #[Validate('nullable|string|max:2000')]
    public string $uniqueValue = '';

    // Step 3: Business & Traction
    #[Validate('nullable|string|max:100')]
    public string $businessModel = '';

    #[Validate('nullable|string|max:2000')]
    public string $traction = '';

    #[Validate('nullable|string|max:500')]
    public string $revenue = '';

    #[Validate('nullable|string|max:500')]
    public string $funding = '';

    #[Validate('nullable|string|max:2000')]
    public string $competition = '';

    #[Validate('nullable|string|max:2000')]
    public string $marketSize = '';

    // Step 4: Additional Info
    #[Validate('nullable|string|max:2000')]
    public string $whyApply = '';

    #[Validate('nullable|string|max:2000')]
    public string $expectations = '';

    #[Validate('nullable|string|max:500')]
    public string $hearAboutUs = '';

    public $pitchDeck = null;

    public bool $agreedToTerms = false;

    public bool $submitted = false;

    public ?string $applicationCode = null;

    public function mount(): void
    {
        // Pre-select cohort if passed via query string
        $this->cohortId = request()->query('cohort') ? (int) request()->query('cohort') : null;
    }

    public function get{{ __('Open') }}CohortsProperty()
    {
        return Cohort::where('status', 'active')
            ->where('application_end_date', '>', now())
            ->orderBy('application_end_date')
            ->get();
    }

    public function getSelectedCohortProperty(): ?Cohort
    {
        return $this->cohortId ? Cohort::find($this->cohortId) : null;
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
    }

    public function addCoFounder(): void
    {
        if (count($this->coFounders) < 4) {
            $this->coFounders[] = ['name' => '', 'email' => '', 'role' => '', 'linkedin' => ''];
        }
    }

    public function removeCoFounder(int $index): void
    {
        unset($this->coFounders[$index]);
        $this->coFounders = array_values($this->coFounders);
    }

    public function submit(): void
    {
        $this->validate([
            'agreedToTerms' => 'accepted',
            'cohortId' => 'required|exists:cohorts,id',
        ]);

        // Check if already applied with this email for this cohort
        if (Application::where('email', $this->founderEmail)->where('cohort_id', $this->cohortId)->exists()) {
            Notification::make()
                ->warning()
                ->title('Already Applied')
                ->body('You have already submitted an application for this cohort.')
                ->send();

            return;
        }

        try {
            $applicationCode = 'APP-'.strtoupper(Str::random(8));

            $data = [
                'cohort_id' => $this->cohortId,
                'application_code' => $applicationCode,
                'status' => ApplicationStatus::Submitted,

                // Founder Info
                'name' => $this->founderName,
                'email' => $this->founderEmail,
                'phone' => $this->founderPhone ?: null,
                'linkedin_url' => $this->founderLinkedin ?: null,

                // Startup Info
                'startup_name' => $this->startupName,
                'website' => $this->websiteUrl ?: null,
                'industry' => $this->industry,
                'stage' => $this->stage,
                'description' => $this->description,

                // Application Data (stored as JSON)
                'application_data' => [
                    'founder' => [
                        'bio' => $this->founderBio,
                    ],
                    'co_founders' => array_filter($this->coFounders, fn ($cf) => ! empty($cf['name'])),
                    'problem_statement' => $this->problem,
                    'solution' => $this->solution,
                    'unique_value' => $this->uniqueValue,
                    'business_model' => $this->businessModel,
                    'traction' => $this->traction,
                    'revenue' => $this->revenue,
                    'funding_raised' => $this->funding,
                    'competition' => $this->competition,
                    'market_size' => $this->marketSize,
                    'why_apply' => $this->whyApply,
                    'expectations' => $this->expectations,
                    'referral_source' => $this->hearAboutUs,
                ],

                'submitted_at' => now(),
            ];

            $application = Application::create($data);

            // Handle pitch deck upload
            if ($this->pitchDeck) {
                $path = $this->pitchDeck->store('applications/pitch-decks', 'public');
                $application->update(['pitch_deck' => $path]);
            }

            // Fire event
            event(new ApplicationSubmitted($application));

            $this->submitted = true;
            $this->applicationCode = $applicationCode;

            Notification::make()
                ->success()
                ->title('Application Submitted!')
                ->body("Your application code is: {$applicationCode}")
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Submission Failed')
                ->body('There was an error submitting your application. Please try again.')
                ->send();

            report($e);
        }
    }

    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'cohortId' => 'required|exists:cohorts,id',
                'founderName' => 'required|string|max:255',
                'founderEmail' => 'required|email|max:255',
            ]),
            2 => $this->validate([
                'startupName' => 'required|string|max:255',
                'industry' => 'required|string|max:100',
                'stage' => 'required|string',
                'description' => 'required|string|max:2000',
                'problem' => 'required|string|max:2000',
                'solution' => 'required|string|max:2000',
            ]),
            default => null,
        };
    }

    public function getIndustryOptions(): array
    {
        return [
            'FinTech',
            'HealthTech',
            'EdTech',
            'AgriTech',
            'E-commerce',
            'SaaS',
            'CleanTech',
            'PropTech',
            'FoodTech',
            'LogisTech',
            'AI / Machine Learning',
            'IoT',
            'Blockchain',
            'Gaming',
            'Media & Entertainment',
            'Travel & Tourism',
            'HR Tech',
            'Legal Tech',
            'Other',
        ];
    }

    public function getStageOptions(): array
    {
        return [
            'idea' => 'Idea Stage',
            'prototype' => 'Prototype / MVP',
            'pre_revenue' => 'Pre-Revenue',
            'early_revenue' => 'Early Revenue',
            'growth' => 'Growth Stage',
        ];
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-12 md:py-16 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Apply to the <span class="text-primary">Incubation Program</span></h1>
                <p class="text-lg text-muted-foreground">{{ __('Take the first step towards accelerating your startup\'s growth. Join our program and get access to mentorship, funding, and a supportive community.') }}</p>
            </div>
        </div>
    </section>

    @if($submitted)
    {{-- Success State --}}
    <section class="py-20">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto text-center space-y-8">
                <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto">
                    <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="space-y-4">
                    <h2 class="text-2xl md:text-3xl font-bold">{{ __('Application Submitted Successfully!') }}</h2>
                    <p class="text-lg text-muted-foreground">
                        {{ __('Thank you for applying to the Siliconile Incubation Program. We\'ve received your application and will review it carefully.') }}
                    </p>
                </div>

                <div class="rounded-lg border bg-primary/5 p-6 space-y-4">
                    <div class="text-center">
                        <p class="text-sm text-muted-foreground mb-2">{{ __('Your Application Code') }}</p>
                        <p class="text-2xl font-mono font-bold text-primary">{{ $applicationCode }}</p>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        <strong>{{ __('Important:') }}</strong> Save this code! You'll need it to check your application status.
                    </p>
                </div>

                <div class="rounded-lg border bg-card p-6 text-left space-y-4">
                    <h3 class="font-semibold">{{ __('What happens next?') }}</h3>
                    <ul class="space-y-3 text-sm text-muted-foreground">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">1</span>
                            <span><strong>{{ __('Application Review') }}</strong> - Our team will review your application within 7-10 business days</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">2</span>
                            <span><strong>{{ __('Interview Invitation') }}</strong> - If shortlisted, you'll be invited for a virtual interview</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">3</span>
                            <span><strong>{{ __('Final Selection') }}</strong> - Selected startups will be notified and onboarded to the program</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('incubation.status') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8">
                        {{ __('Track Application Status') }}
                    </a>
                    <a href="{{ route('programs') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent h-11 px-8">
                        {{ __('Back to Programs') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
    @else
    {{-- Application Form --}}
    <section class="py-12">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto">
                {{-- Progress Steps --}}
                <div class="mb-10">
                    <div class="flex items-center justify-between">
                        @foreach([1 => 'Program & Founder', 2 => 'Startup Info', 3 => 'Business Details', 4 => 'Submit'] as $step => $label)
                        <div class="flex items-center {{ $step < $totalSteps ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold {{ $currentStep >= $step ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                                    @if($currentStep > $step)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    @else
                                    {{ $step }}
                                    @endif
                                </div>
                                <span class="text-xs mt-2 text-center hidden sm:block {{ $currentStep >= $step ? 'text-primary font-medium' : 'text-muted-foreground' }}">{{ $label }}</span>
                            </div>
                            @if($step < $totalSteps)
                            <div class="flex-1 h-1 mx-2 md:mx-4 {{ $currentStep > $step ? 'bg-primary' : 'bg-muted' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Card --}}
                <div class="rounded-xl border bg-card shadow-sm p-6 md:p-8">
                    <form wire:submit="submit">
                        {{-- Step 1: Program & Founder Information --}}
                        @if($currentStep === 1)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">{{ __('Select Program & Founder Information') }}</h2>
                                <p class="text-sm text-muted-foreground">{{ __('Choose your program and tell us about yourself') }}</p>
                            </div>

                            {{-- Cohort Selection --}}
                            <div class="space-y-3">
                                <label class="text-sm font-medium">{{ __('Select Program / Cohort *') }}</label>
                                @if($this->openCohorts->isEmpty())
                                <div class="rounded-lg border border-dashed border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200">No programs are currently accepting applications. Please check back later or <a href="{{ route('programs') }}" class="underline">{{ __('view our programs') }}</a> for upcoming opportunities.</p>
                                </div>
                                @else
                                <div class="grid gap-3">
                                    @foreach($this->openCohorts as $cohort)
                                    <label class="flex items-start gap-4 p-4 rounded-lg border cursor-pointer hover:bg-muted/50 transition-colors {{ $cohortId === $cohort->id ? 'border-primary bg-primary/5' : '' }}">
                                        <input type="radio" wire:model="cohortId" value="{{ $cohort->id }}" class="mt-1">
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="font-semibold">{{ $cohort->name }}</h3>
                                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Open</span>
                                            </div>
                                            <p class="text-sm text-muted-foreground mt-1">{{ Str::limit($cohort->description, 100) }}</p>
                                            <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                                                <span>{{ __('Starts:') }} {{ $cohort->start_date->format('M d, Y') }}</span>
                                                <span>{{ __('Deadline:') }} {{ $cohort->application_end_date->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                @endif
                                @error('cohortId') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                            </div>

                            <div class="border-t pt-6">
                                <h3 class="text-sm font-medium mb-4">Founder Information</h3>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">{{ __('Full Name *') }}</label>
                                        <input type="text" wire:model="founderName" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Your full name') }}">
                                        @error('founderName') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">{{ __('Email Address *') }}</label>
                                        <input type="email" wire:model="founderEmail" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="your@email.com">
                                        @error('founderEmail') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 mt-4">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">{{ __('Phone Number') }}</label>
                                        <input type="tel" wire:model="founderPhone" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="+20 1XX XXX XXXX">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">{{ __('LinkedIn Profile') }}</label>
                                        <input type="url" wire:model="founderLinkedin" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://linkedin.com/in/yourprofile">
                                    </div>
                                </div>

                                <div class="space-y-2 mt-4">
                                    <label class="text-sm font-medium">{{ __('Brief Bio') }}</label>
                                    <textarea wire:model="founderBio" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Tell us about your background and relevant experience...') }}"></textarea>
                                </div>
                            </div>

                            {{-- Co-founders Section --}}
                            <div class="border-t pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-medium">{{ __('Co-founders (Optional)') }}</h3>
                                    @if(count($coFounders) < 4)
                                    <button type="button" wire:click="addCoFounder" class="text-sm text-primary hover:underline">{{ __('+ Add Co-founder') }}</button>
                                    @endif
                                </div>

                                @foreach($coFounders as $index => $coFounder)
                                <div class="rounded-lg border p-4 mb-4 relative" wire:key="cofounder-{{ $index }}">
                                    <button type="button" wire:click="removeCoFounder({{ $index }})" class="absolute top-2 right-2 text-muted-foreground hover:text-destructive">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <div class="grid gap-4 md:grid-cols-2 pr-8">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">Name</label>
                                            <input type="text" wire:model="coFounders.{{ $index }}.name" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Co-founder name') }}">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">Role</label>
                                            <input type="text" wire:model="coFounders.{{ $index }}.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('CTO, CMO, etc.') }}">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">Email</label>
                                            <input type="email" wire:model="coFounders.{{ $index }}.email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="email@example.com">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">LinkedIn</label>
                                            <input type="url" wire:model="coFounders.{{ $index }}.linkedin" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://linkedin.com/in/...">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Step 2: Startup Information --}}
                        @if($currentStep === 2)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">Startup Information</h2>
                                <p class="text-sm text-muted-foreground">{{ __('Tell us about your startup') }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ __('Startup Name *') }}</label>
                                    <input type="text" wire:model="startupName" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Your startup name') }}">
                                    @error('startupName') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Website</label>
                                    <input type="url" wire:model="websiteUrl" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://yourstartup.com">
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ __('Industry *') }}</label>
                                    <select wire:model="industry" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                        <option value="">Select industry...</option>
                                        @foreach($this->getIndustryOptions() as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('industry') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ __('Stage *') }}</label>
                                    <select wire:model="stage" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                        @foreach($this->getStageOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('stage') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Startup Description *') }}</label>
                                <textarea wire:model="description" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Describe your startup in a few sentences...') }}"></textarea>
                                <p class="text-xs text-muted-foreground">{{ strlen($description) }}/2000</p>
                                @error('description') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Problem Statement *') }}</label>
                                <textarea wire:model="problem" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('What problem are you solving? Who experiences this problem?') }}"></textarea>
                                @error('problem') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Your Solution *') }}</label>
                                <textarea wire:model="solution" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('How does your startup solve this problem?') }}"></textarea>
                                @error('solution') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Unique Value Proposition') }}</label>
                                <textarea wire:model="uniqueValue" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('What makes your solution different from existing alternatives?') }}"></textarea>
                            </div>
                        </div>
                        @endif

                        {{-- Step 3: Business & Traction --}}
                        @if($currentStep === 3)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">{{ __('Business Details & Traction') }}</h2>
                                <p class="text-sm text-muted-foreground">{{ __('Help us understand your business model and progress') }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Business Model') }}</label>
                                <select wire:model="businessModel" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select business model...</option>
                                    <option value="b2b">B2B (Business to Business)</option>
                                    <option value="b2c">B2C (Business to Consumer)</option>
                                    <option value="b2b2c">B2B2C</option>
                                    <option value="marketplace">Marketplace</option>
                                    <option value="saas">SaaS (Subscription)</option>
                                    <option value="freemium">Freemium</option>
                                    <option value="commission">Commission-based</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Current Traction') }}</label>
                                <textarea wire:model="traction" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Users, customers, partnerships, pilots, etc. Share any metrics you have.') }}"></textarea>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ __('Current Revenue (if any)') }}</label>
                                    <input type="text" wire:model="revenue" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="e.g., $5,000/month or Pre-revenue">
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ __('Funding Raised (if any)') }}</label>
                                    <input type="text" wire:model="funding" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="e.g., $50,000 pre-seed or Bootstrapped">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Market Size') }}</label>
                                <textarea wire:model="marketSize" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Describe your target market size (TAM, SAM, SOM if available)') }}"></textarea>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Competition</label>
                                <textarea wire:model="competition" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Who are your main competitors? How do you differentiate?') }}"></textarea>
                            </div>
                        </div>
                        @endif

                        {{-- Step 4: Additional Info & Submit --}}
                        @if($currentStep === 4)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">{{ __('Additional Information') }}</h2>
                                <p class="text-sm text-muted-foreground">{{ __('Final details before submitting your application') }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Why are you applying to this program?') }}</label>
                                <textarea wire:model="whyApply" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('What do you hope to achieve through this program?') }}"></textarea>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('What are your expectations from the program?') }}</label>
                                <textarea wire:model="expectations" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="{{ __('Mentorship, funding, network, specific skills...') }}"></textarea>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('Pitch Deck (Optional)') }}</label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-muted/50 hover:bg-muted">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            @if($pitchDeck)
                                            <svg class="h-8 w-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <p class="text-sm text-muted-foreground">{{ $pitchDeck->getClientOriginalName() }}</p>
                                            @else
                                            <svg class="h-8 w-8 text-muted-foreground mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            <p class="text-sm text-muted-foreground">{{ __('Upload pitch deck (PDF, max 10MB)') }}</p>
                                            @endif
                                        </div>
                                        <input type="file" wire:model="pitchDeck" class="hidden" accept=".pdf,.ppt,.pptx">
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ __('How did you hear about us?') }}</label>
                                <select wire:model="hearAboutUs" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select option...</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="friend_referral">Friend/Colleague Referral</option>
                                    <option value="event">Event/Conference</option>
                                    <option value="search_engine">Search Engine (Google)</option>
                                    <option value="news_article">News Article</option>
                                    <option value="university">University/Incubator</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            {{-- {{ __('Application Summary') }} --}}
                            @if($this->selectedCohort)
                            <div class="rounded-lg border bg-muted/50 p-4 space-y-3">
                                <h3 class="font-semibold">Application Summary</h3>
                                <div class="grid gap-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">{{ __('Program:') }}</span>
                                        <span class="font-medium">{{ $this->selectedCohort->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">{{ __('Founder:') }}</span>
                                        <span class="font-medium">{{ $founderName }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">{{ __('Startup:') }}</span>
                                        <span class="font-medium">{{ $startupName }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">{{ __('Industry:') }}</span>
                                        <span class="font-medium">{{ $industry }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="rounded-lg border bg-muted/50 p-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model="agreedToTerms" class="mt-1 rounded">
                                    <span class="text-sm">
                                        I confirm that all information provided is accurate and I agree to the <a href="#" class="text-primary hover:underline">{{ __('Terms & Conditions') }}</a> and <a href="#" class="text-primary hover:underline">{{ __('Privacy Policy') }}</a> of the Siliconile Incubation Program.
                                    </span>
                                </label>
                                @error('agreedToTerms') <p class="text-sm text-destructive mt-2">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        @endif

                        {{-- Navigation Buttons --}}
                        <div class="flex justify-between pt-6 border-t mt-6">
                            @if($currentStep > 1)
                            <button type="button" wire:click="previousStep" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent h-10 px-6">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('Previous') }}
                            </button>
                            @else
                            <div></div>
                            @endif

                            @if($currentStep < $totalSteps)
                            <button type="button" wire:click="nextStep" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6" @if($currentStep === 1 && $this->openCohorts->isEmpty()) disabled @endif>{{ __('Next') }}
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            @else
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-8">
                                <span wire:loading.remove>{{ __('Submit Application') }}</span>
                                <span wire:loading>Submitting...</span>
                            </button>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Help Section --}}
                <div class="mt-8 text-center text-sm text-muted-foreground">
                    <p>Need help with your application? <a href="{{ route('contact') }}" class="text-primary hover:underline">{{ __('Contact us') }}</a></p>
                    <p class="mt-2">Already applied? <a href="{{ route('incubation.status') }}" class="text-primary hover:underline">{{ __('Check your application status') }}</a></p>
                </div>
            </div>
        </div>
    </section>
    @endif
</main>
