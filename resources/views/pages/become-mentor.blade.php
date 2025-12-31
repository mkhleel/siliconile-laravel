<?php

declare(strict_types=1);

use Filament\Notifications\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Modules\Incubation\Models\Mentor;

new
#[Layout('layouts.app')]
#[Title('Become a Mentor | Siliconile')]
class extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    public int $totalSteps = 3;

    // Step 1: Personal Information
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    #[Validate('nullable|url|max:500')]
    public string $linkedinUrl = '';

    #[Validate('nullable|url|max:500')]
    public string $twitterUrl = '';

    #[Validate('nullable|url|max:500')]
    public string $websiteUrl = '';

    // Step 2: Expertise & Experience
    #[Validate('required|string|max:2000')]
    public string $bio = '';

    public array $expertise = [];

    public string $customExpertise = '';

    #[Validate('nullable|string|max:1000')]
    public string $experience = '';

    #[Validate('nullable|string|max:1000')]
    public string $motivation = '';

    // Step 3: Availability
    #[Validate('required|integer|min:1|max:10')]
    public int $maxSessionsPerWeek = 2;

    public array $availability = [
        'monday' => false,
        'tuesday' => false,
        'wednesday' => false,
        'thursday' => false,
        'friday' => false,
        'saturday' => false,
        'sunday' => false,
    ];

    public string $preferredTime = 'flexible';

    public $profilePhoto = null;

    public bool $agreedToTerms = false;

    public bool $submitted = false;

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
    }

    public function addExpertise(): void
    {
        if (! empty($this->customExpertise) && ! in_array($this->customExpertise, $this->expertise)) {
            $this->expertise[] = trim($this->customExpertise);
            $this->customExpertise = '';
        }
    }

    public function removeExpertise(int $index): void
    {
        unset($this->expertise[$index]);
        $this->expertise = array_values($this->expertise);
    }

    public function toggleExpertise(string $value): void
    {
        if (in_array($value, $this->expertise)) {
            $this->expertise = array_values(array_filter($this->expertise, fn ($e) => $e !== $value));
        } else {
            $this->expertise[] = $value;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'agreedToTerms' => 'accepted',
        ]);

        if (empty($this->expertise)) {
            Notification::make()
                ->danger()
                ->title('Expertise Required')
                ->body('Please select at least one area of expertise.')
                ->send();

            return;
        }

        // Check if already applied
        if (Mentor::where('email', $this->email)->exists()) {
            Notification::make()
                ->warning()
                ->title('Already Applied')
                ->body('A mentor application with this email already exists.')
                ->send();

            return;
        }

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'title' => $this->title,
                'company' => $this->company ?: null,
                'bio' => $this->bio,
                'expertise' => $this->expertise,
                'linkedin_url' => $this->linkedinUrl ?: null,
                'twitter_url' => $this->twitterUrl ?: null,
                'website_url' => $this->websiteUrl ?: null,
                'max_sessions_per_week' => $this->maxSessionsPerWeek,
                'availability' => [
                    'days' => array_keys(array_filter($this->availability)),
                    'preferred_time' => $this->preferredTime,
                ],
                'is_active' => false, // Pending approval
                'internal_notes' => "Motivation: {$this->motivation}\n\nExperience: {$this->experience}",
            ];

            $mentor = Mentor::create($data);

            // Handle profile photo upload
            if ($this->profilePhoto) {
                $path = $this->profilePhoto->store('mentors/photos', 'public');
                $mentor->update(['profile_photo' => $path]);
            }

            $this->submitted = true;

            Notification::make()
                ->success()
                ->title('Application Submitted!')
                ->body('Thank you for applying. We will review your application and get back to you soon.')
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
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'title' => 'required|string|max:255',
                'linkedinUrl' => 'nullable|url|max:500',
                'twitterUrl' => 'nullable|url|max:500',
                'websiteUrl' => 'nullable|url|max:500',
            ]),
            2 => $this->validate([
                'bio' => 'required|string|max:2000',
            ]),
            default => null,
        };
    }

    public function getExpertiseOptions(): array
    {
        return [
            'Product Development',
            'Marketing & Growth',
            'Fundraising',
            'Sales & Business Development',
            'Technology & Engineering',
            'Finance & Accounting',
            'Legal & Compliance',
            'Operations & Scaling',
            'UX/UI Design',
            'Data Science & AI',
            'E-commerce',
            'SaaS',
            'FinTech',
            'HealthTech',
            'EdTech',
            'AgriTech',
        ];
    }
};
?>

<main class="flex-1">
    {{-- Hero Section --}}
    <section class="py-16 md:py-24 bg-gradient-to-br from-primary/5 to-secondary/10">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto text-center space-y-6">
                <h1 class="text-3xl md:text-5xl font-bold tracking-tight">Become a <span class="text-primary">Mentor</span></h1>
                <p class="text-lg md:text-xl text-muted-foreground">Share your expertise and help shape the next generation of successful startups in Egypt and the MENA region.</p>
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
                    <h2 class="text-2xl md:text-3xl font-bold">Application Submitted!</h2>
                    <p class="text-lg text-muted-foreground">
                        Thank you for your interest in becoming a mentor at Siliconile. We've received your application and will review it shortly.
                    </p>
                </div>

                <div class="rounded-lg border bg-card p-6 text-left space-y-4">
                    <h3 class="font-semibold">What happens next?</h3>
                    <ul class="space-y-3 text-sm text-muted-foreground">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">1</span>
                            <span>Our team will review your application within 5-7 business days</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">2</span>
                            <span>If selected, we'll schedule a brief introductory call</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-semibold text-primary">3</span>
                            <span>Once approved, you'll be matched with startups based on your expertise</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8">
                        Back to Home
                    </a>
                    <a href="{{ route('programs') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent h-11 px-8">
                        View Programs
                    </a>
                </div>
            </div>
        </div>
    </section>
    @else
    {{-- Application Form --}}
    <section class="py-16">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto">
                {{-- Progress Steps --}}
                <div class="mb-10">
                    <div class="flex items-center justify-between">
                        @foreach([1 => 'Personal Info', 2 => 'Expertise', 3 => 'Availability'] as $step => $label)
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
                                <span class="text-xs mt-2 {{ $currentStep >= $step ? 'text-primary font-medium' : 'text-muted-foreground' }}">{{ $label }}</span>
                            </div>
                            @if($step < $totalSteps)
                            <div class="flex-1 h-1 mx-4 {{ $currentStep > $step ? 'bg-primary' : 'bg-muted' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Card --}}
                <div class="rounded-xl border bg-card shadow-sm p-6 md:p-8">
                    <form wire:submit="submit">
                        {{-- Step 1: Personal Information --}}
                        @if($currentStep === 1)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">Personal Information</h2>
                                <p class="text-sm text-muted-foreground">Tell us about yourself</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Full Name *</label>
                                    <input type="text" wire:model="name" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="John Doe">
                                    @error('name') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Email Address *</label>
                                    <input type="email" wire:model="email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="john@example.com">
                                    @error('email') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Job Title *</label>
                                    <input type="text" wire:model="title" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="CEO, CTO, Founder...">
                                    @error('title') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Company</label>
                                    <input type="text" wire:model="company" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Your company name">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Phone Number</label>
                                <input type="tel" wire:model="phone" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="+20 1XX XXX XXXX">
                            </div>

                            <div class="space-y-4">
                                <label class="text-sm font-medium">Social Links</label>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <svg class="h-5 w-5 text-muted-foreground shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                        <input type="url" wire:model="linkedinUrl" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://linkedin.com/in/yourprofile">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <svg class="h-5 w-5 text-muted-foreground shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        <input type="url" wire:model="twitterUrl" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://twitter.com/yourhandle">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <svg class="h-5 w-5 text-muted-foreground shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        <input type="url" wire:model="websiteUrl" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://yourwebsite.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Step 2: Expertise & Experience --}}
                        @if($currentStep === 2)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">Expertise & Experience</h2>
                                <p class="text-sm text-muted-foreground">Share your areas of expertise and what you can offer</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Bio / Introduction *</label>
                                <textarea wire:model="bio" rows="4" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Tell startups about yourself, your background, and what makes you a great mentor..."></textarea>
                                <p class="text-xs text-muted-foreground">{{ strlen($bio) }}/2000 characters</p>
                                @error('bio') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-3">
                                <label class="text-sm font-medium">Areas of Expertise *</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($this->getExpertiseOptions() as $option)
                                    <button type="button" wire:click="toggleExpertise('{{ $option }}')" class="px-3 py-1.5 rounded-full text-sm border transition-colors {{ in_array($option, $expertise) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted border-input' }}">
                                        {{ $option }}
                                    </button>
                                    @endforeach
                                </div>

                                <div class="flex gap-2">
                                    <input type="text" wire:model="customExpertise" wire:keydown.enter.prevent="addExpertise" class="flex h-9 flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Add custom expertise...">
                                    <button type="button" wire:click="addExpertise" class="px-4 h-9 rounded-md bg-muted hover:bg-muted/80 text-sm font-medium">Add</button>
                                </div>

                                @if(count($expertise) > 0)
                                <div class="flex flex-wrap gap-2 pt-2">
                                    @foreach($expertise as $index => $exp)
                                    @if(!in_array($exp, $this->getExpertiseOptions()))
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-primary text-primary-foreground">
                                        {{ $exp }}
                                        <button type="button" wire:click="removeExpertise({{ $index }})" class="hover:text-primary-foreground/70">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Previous Mentoring Experience</label>
                                <textarea wire:model="experience" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Have you mentored startups before? Tell us about it..."></textarea>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Why do you want to be a mentor?</label>
                                <textarea wire:model="motivation" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="What motivates you to mentor early-stage startups?"></textarea>
                            </div>
                        </div>
                        @endif

                        {{-- Step 3: Availability --}}
                        @if($currentStep === 3)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold">Availability</h2>
                                <p class="text-sm text-muted-foreground">Let us know when you're available for mentoring sessions</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Profile Photo</label>
                                <div class="flex items-center gap-4">
                                    @if($profilePhoto)
                                    <img src="{{ $profilePhoto->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover">
                                    @else
                                    <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center">
                                        <svg class="h-8 w-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    @endif
                                    <input type="file" wire:model="profilePhoto" accept="image/*" class="text-sm">
                                </div>
                                <p class="text-xs text-muted-foreground">Upload a professional photo (optional)</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Maximum Sessions Per Week *</label>
                                <select wire:model="maxSessionsPerWeek" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'session' : 'sessions' }}</option>
                                    @endfor
                                </select>
                                <p class="text-xs text-muted-foreground">Each session is typically 30-60 minutes</p>
                            </div>

                            <div class="space-y-3">
                                <label class="text-sm font-medium">Available Days</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    @foreach(['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'] as $key => $day)
                                    <label class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer hover:bg-muted/50 {{ $availability[$key] ? 'border-primary bg-primary/5' : '' }}">
                                        <input type="checkbox" wire:model="availability.{{ $key }}" class="rounded">
                                        <span class="text-sm">{{ $day }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Preferred Time</label>
                                <select wire:model="preferredTime" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="flexible">Flexible</option>
                                    <option value="morning">Morning (9 AM - 12 PM)</option>
                                    <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                                    <option value="evening">Evening (5 PM - 9 PM)</option>
                                </select>
                            </div>

                            <div class="rounded-lg border bg-muted/50 p-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model="agreedToTerms" class="mt-1 rounded">
                                    <span class="text-sm">
                                        I agree to the <a href="#" class="text-primary hover:underline">Mentor Terms & Conditions</a> and commit to providing quality mentorship to startups in the Siliconile program.
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
                                Previous
                            </button>
                            @else
                            <div></div>
                            @endif

                            @if($currentStep < $totalSteps)
                            <button type="button" wire:click="nextStep" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6">
                                Next
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            @else
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6">
                                <span wire:loading.remove>Submit Application</span>
                                <span wire:loading>Submitting...</span>
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Why Become a Mentor Section --}}
    @if(!$submitted)
    <section class="py-16 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-2xl font-bold mb-8 text-center">Why Become a Mentor?</h2>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="rounded-lg border bg-card p-6 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3 class="font-semibold">Give Back</h3>
                        <p class="text-sm text-muted-foreground">Share your experience and help founders avoid common pitfalls</p>
                    </div>

                    <div class="rounded-lg border bg-card p-6 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </div>
                        <h3 class="font-semibold">Expand Network</h3>
                        <p class="text-sm text-muted-foreground">Connect with promising startups and fellow industry experts</p>
                    </div>

                    <div class="rounded-lg border bg-card p-6 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <h3 class="font-semibold">Stay Sharp</h3>
                        <p class="text-sm text-muted-foreground">Stay connected to the latest trends and innovative ideas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
</main>
