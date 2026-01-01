<?php

declare(strict_types=1);

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Modules\Incubation\Enums\StartupStage;
use Modules\Incubation\Models\Cohort;
use Modules\Incubation\Services\ApplicationService;

new class extends Component
{
    use WithFileUploads;

    public Cohort $cohort;

    public int $currentStep = 1;

    public int $totalSteps = 5;

    // Step 1: {{ __('Basic Information') }}
    #[Validate('required|string|max:255')]
    public string $startupName = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|url|max:255')]
    public string $website = '';

    // Step 2: Founders
    public array $founders = [];

    // Step 3: {{ __('Business Details') }}
    #[Validate('required|string|max:2000')]
    public string $problemStatement = '';

    #[Validate('required|string|max:2000')]
    public string $solution = '';

    #[Validate('nullable|string|max:100')]
    public string $industry = '';

    #[Validate('nullable|string')]
    public ?string $businessModel = null;

    #[Validate('nullable|string')]
    public ?string $stage = null;

    #[Validate('nullable|string|max:1000')]
    public string $traction = '';

    #[Validate('required|string|max:2000')]
    public string $whyApply = '';

    // Step 4: Materials
    public $pitchDeck = null;

    #[Validate('nullable|url|max:500')]
    public string $pitchDeckUrl = '';

    #[Validate('nullable|url|max:500')]
    public string $videoPitchUrl = '';

    public array $socialLinks = [
        'linkedin' => '',
        'twitter' => '',
        'facebook' => '',
    ];

    // Step 5: Review & Submit
    public bool $agreedToTerms = false;

    public function mount(Cohort $cohort): void
    {
        $this->cohort = $cohort;

        // Check if cohort is accepting applications
        if (! $cohort->isAcceptingApplications()) {
            abort(403, 'This cohort is not currently accepting applications.');
        }

        // Initialize founders with one empty entry
        $this->founders = [
            ['name' => '', 'email' => '', 'role' => '', 'linkedin' => '', 'bio' => ''],
        ];
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

    public function goToStep(int $step): void
    {
        if ($step <= $this->currentStep || $step === $this->currentStep + 1) {
            $this->currentStep = $step;
        }
    }

    public function addFounder(): void
    {
        $this->founders[] = ['name' => '', 'email' => '', 'role' => '', 'linkedin' => '', 'bio' => ''];
    }

    public function removeFounder(int $index): void
    {
        if (count($this->founders) > 1) {
            unset($this->founders[$index]);
            $this->founders = array_values($this->founders);
        }
    }

    public function submit(): void
    {
        $this->validate([
            'agreedToTerms' => 'accepted',
        ]);

        if (! $this->cohort->isAcceptingApplications()) {
            Notification::make()
                ->danger()
                ->title('Applications Closed')
                ->body('This cohort is no longer accepting applications.')
                ->send();

            return;
        }

        // Prepare application data
        $data = [
            'cohort_id' => $this->cohort->id,
            'startup_name' => $this->startupName,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'website_url' => $this->website ?: null,
            'founders_data' => array_filter($this->founders, fn ($f) => ! empty($f['name'])),
            'problem_statement' => $this->problemStatement,
            'solution' => $this->solution,
            'industry' => $this->industry ?: null,
            'business_model' => $this->businessModel,
            'stage' => $this->stage,
            'traction' => $this->traction ?: null,
            'why_apply' => $this->whyApply,
            'pitch_deck_url' => $this->pitchDeckUrl ?: null,
            'video_pitch_url' => $this->videoPitchUrl ?: null,
            'social_links' => array_filter($this->socialLinks),
            'source' => 'website',
        ];

        try {
            $service = app(ApplicationService::class);
            $application = $service->submitApplication($data);

            // Handle pitch deck upload
            if ($this->pitchDeck) {
                $path = $this->pitchDeck->store(
                    'pitch-decks/' . now()->format('Y/m'),
                    'local'
                );
                $service->uploadPitchDeck($application, $path);
            }

            // Redirect to success page
            $this->redirect(
                route('incubation.application.status', ['applicationCode' => $application->application_code]),
                navigate: true
            );
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
                'startupName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:50',
                'website' => 'nullable|url|max:255',
            ]),
            2 => $this->validate([
                'founders' => 'required|array|min:1',
                'founders.*.name' => 'required|string|max:255',
                'founders.*.email' => 'nullable|email|max:255',
            ]),
            3 => $this->validate([
                'problemStatement' => 'required|string|max:2000',
                'solution' => 'required|string|max:2000',
                'whyApply' => 'required|string|max:2000',
            ]),
            4 => $this->validate([
                'pitchDeck' => 'nullable|file|max:20480|mimes:pdf,ppt,pptx',
                'pitchDeckUrl' => 'nullable|url|max:500',
            ]),
            default => null,
        };
    }

    public function getStageOptionsProperty(): array
    {
        return StartupStage::options();
    }

    public function getProgressProperty(): int
    {
        return (int) (($this->currentStep / $this->totalSteps) * 100);
    }
}; ?>

<div class="max-w-4xl mx-auto py-8 px-4">
    {{-- Header --}}
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('Apply to') }} {{ $cohort->name }}
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ __('Application deadline:') }} {{ $cohort->application_end_date?->format('F j, Y') ?? 'Rolling basis' }}
        </p>
    </div>

    {{-- Progress Bar --}}
    <div class="mb-8">
        <div class="flex justify-between mb-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Step {{ $currentStep }} of {{ $totalSteps }}</span>
            <span>{{ $this->progress }}{{ __('% Complete') }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
            <div
                class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                style="width: {{ $this->progress }}%"
            ></div>
        </div>

        {{-- Step Indicators --}}
        <div class="flex justify-between mt-4">
            @foreach(['Basic Info', 'Founders', 'Business', 'Materials', 'Review'] as $index => $label)
                <button
                    type="button"
                    wire:click="goToStep({{ $index + 1 }})"
                    @class([
                        'flex flex-col items-center text-xs',
                        'text-primary-600 font-medium' => $currentStep === $index + 1,
                        'text-gray-500' => $currentStep !== $index + 1,
                        'cursor-pointer' => $index + 1 <= $currentStep,
                        'cursor-not-allowed opacity-50' => $index + 1 > $currentStep + 1,
                    ])
                    @disabled($index + 1 > $currentStep + 1)
                >
                    <span @class([
                        'w-8 h-8 rounded-full flex items-center justify-center mb-1 text-sm font-bold',
                        'bg-primary-600 text-white' => $index + 1 <= $currentStep,
                        'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $index + 1 > $currentStep,
                    ])>
                        @if($index + 1 < $currentStep)
                            <x-heroicon-s-check class="w-5 h-5" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span class="hidden sm:block">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <form wire:submit="submit">
        {{-- Step 1: Basic Information --}}
        @if($currentStep === 1)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                    Basic Information
                </h2>

                <div class="space-y-4">
                    <flux:input
                        wire:model="startupName"
                        label="{{ __('Startup Name') }}"
                        placeholder="{{ __('Enter your startup name') }}"
                        required
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="email"
                            type="email"
                            label="{{ __('Contact Email') }}"
                            placeholder="contact@startup.com"
                            required
                        />

                        <flux:input
                            wire:model="phone"
                            type="tel"
                            label="{{ __('Phone Number') }}"
                            placeholder="{{ __('+249 XXX XXX XXX') }}"
                        />
                    </div>

                    <flux:input
                        wire:model="website"
                        type="url"
                        label="Website"
                        placeholder="https://www.yourstartup.com"
                    />
                </div>
            </div>
        @endif

        {{-- Step 2: Founders --}}
        @if($currentStep === 2)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                    {{ __('Founders & {{ __('Team') }}') }}
                </h2>

                <div class="space-y-6">
                    @foreach($founders as $index => $founder)
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    Founder {{ $index + 1 }}
                                </h3>
                                @if(count($founders) > 1)
                                    <button
                                        type="button"
                                        wire:click="removeFounder({{ $index }})"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        {{ __('Remove') }}
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="founders.{{ $index }}.name"
                                    label="{{ __('Full Name') }}"
                                    placeholder="{{ __('John Doe') }}"
                                    required
                                />

                                <flux:input
                                    wire:model="founders.{{ $index }}.email"
                                    type="email"
                                    label="{{ __('Email') }}"
                                    placeholder="john@startup.com"
                                />

                                <flux:input
                                    wire:model="founders.{{ $index }}.role"
                                    label="Role"
                                    placeholder="{{ __('CEO, CTO, etc.') }}"
                                />

                                <flux:input
                                    wire:model="founders.{{ $index }}.linkedin"
                                    type="url"
                                    label="LinkedIn"
                                    placeholder="https://linkedin.com/in/..."
                                />
                            </div>

                            <div class="mt-4">
                                <flux:textarea
                                    wire:model="founders.{{ $index }}.bio"
                                    label="{{ __('Short Bio') }}"
                                    placeholder="{{ __('Brief background and relevant experience...') }}"
                                    rows="2"
                                />
                            </div>
                        </div>
                    @endforeach

                    <button
                        type="button"
                        wire:click="addFounder"
                        class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400 hover:border-primary-500 hover:text-primary-500 transition"
                    >
                        {{ __('+ Add Another Founder') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 3: Business Details --}}
        @if($currentStep === 3)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                    Business Details
                </h2>

                <div class="space-y-4">
                    <flux:textarea
                        wire:model="problemStatement"
                        label="{{ __('{{ __('Problem Statement') }}') }}"
                        placeholder="{{ __('What problem are you solving? Who experiences this problem?') }}"
                        rows="4"
                        required
                    />

                    <flux:textarea
                        wire:model="solution"
                        label="{{ __('Your Solution') }}"
                        placeholder="{{ __('How does your product/service solve this problem?') }}"
                        rows="4"
                        required
                    />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input
                            wire:model="industry"
                            label="Industry"
                            placeholder="{{ __('e.g., FinTech, AgriTech') }}"
                        />

                        <flux:select
                            wire:model="businessModel"
                            label="{{ __('Business Model') }}"
                        >
                            <option value="">Select...</option>
                            <option value="B2B">B2B</option>
                            <option value="B2C">B2C</option>
                            <option value="B2B2C">B2B2C</option>
                            <option value="Marketplace">Marketplace</option>
                            <option value="SaaS">SaaS</option>
                            <option value="Other">Other</option>
                        </flux:select>

                        <flux:select
                            wire:model="stage"
                            label="Stage"
                        >
                            <option value="">Select...</option>
                            @foreach($this->stageOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:textarea
                        wire:model="traction"
                        label="{{ __('Current Traction') }}"
                        placeholder="{{ __('Users, revenue, partnerships, metrics...') }}"
                        rows="3"
                    />

                    <flux:textarea
                        wire:model="whyApply"
                        label="{{ __('Why Are You Applying?') }}"
                        placeholder="{{ __('What do you hope to achieve from this program?') }}"
                        rows="4"
                        required
                    />
                </div>
            </div>
        @endif

        {{-- Step 4: Materials --}}
        @if($currentStep === 4)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                    {{ __('Pitch Materials') }}
                </h2>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Upload Pitch Deck') }}
                        </label>
                        <input
                            type="file"
                            wire:model="pitchDeck"
                            accept=".pdf,.ppt,.pptx"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900 dark:file:text-primary-300"
                        />
                        <p class="mt-1 text-sm text-gray-500">{{ __('PDF or PowerPoint, max 20MB') }}</p>
                        @error('pitchDeck') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-center text-gray-500">{{ __('- OR -') }}</div>

                    <flux:input
                        wire:model="pitchDeckUrl"
                        type="url"
                        label="{{ __('Pitch Deck URL') }}"
                        placeholder="{{ __('Google Slides, Canva, or other link') }}"
                    />

                    <flux:input
                        wire:model="videoPitchUrl"
                        type="url"
                        label="{{ __('Video Pitch URL (Optional)') }}"
                        placeholder="{{ __('YouTube, Vimeo, or Loom link') }}"
                    />

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">{{ __('Social Links') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input
                                wire:model="socialLinks.linkedin"
                                type="url"
                                label="LinkedIn"
                                placeholder="{{ __('Company LinkedIn') }}"
                            />
                            <flux:input
                                wire:model="socialLinks.twitter"
                                type="url"
                                label="{{ __('Twitter/X') }}"
                                placeholder="@handle"
                            />
                            <flux:input
                                wire:model="socialLinks.facebook"
                                type="url"
                                label="Facebook"
                                placeholder="{{ __('Facebook page') }}"
                            />
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 5: Review & Submit --}}
        @if($currentStep === 5)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                    {{ __('Review Your Application') }}
                </h2>

                <div class="space-y-6">
                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Startup</h4>
                            <p class="text-lg font-semibold text-primary-600">{{ $startupName }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $email }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Team</h4>
                            <p class="text-lg font-semibold text-primary-600">
                                {{ count(array_filter($founders, fn($f) => !empty($f['name']))) }} {{ __('Founder(s)') }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $founders[0]['name'] ?? 'N/A' }}
                                @if(count($founders) > 1)
                                    +{{ count($founders) - 1 }} more
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Problem Statement</h4>
                        <p class="text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($problemStatement, 200) }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Solution</h4>
                        <p class="text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($solution, 200) }}</p>
                    </div>

                    {{-- Terms Agreement --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="agreedToTerms"
                                class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                I confirm that all information provided is accurate and I agree to the
                                <a href="#" class="text-primary-600 hover:underline">{{ __('Terms & Conditions') }}</a>
                                of the incubation program.
                            </span>
                        </label>
                        @error('agreedToTerms') <span class="text-red-500 text-sm block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Buttons --}}
        <div class="mt-6 flex justify-between">
            @if($currentStep > 1)
                <flux:button
                    type="button"
                    wire:click="previousStep"
                    variant="ghost"
                >
                    ← Previous
                </flux:button>
            @else
                <div></div>
            @endif

            @if($currentStep < $totalSteps)
                <flux:button
                    type="button"
                    wire:click="nextStep"
                    variant="primary"
                >
                    Next →
                </flux:button>
            @else
                <flux:button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ __('Submit Application') }}</span>
                    <span wire:loading>Submitting...</span>
                </flux:button>
            @endif
        </div>
    </form>
</div>
