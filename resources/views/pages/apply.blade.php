<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Validate};
use Livewire\WithFileUploads;
use App\Models\User;
use App\Enums\UserType;
use App\Enums\ApplicationStatus;
use App\Enums\Gender;
use Illuminate\Support\Facades\Hash;

new
#[Layout('layouts.app')]
#[Title('Apply to Siliconile | Membership Application')]
class extends Component
{
    use WithFileUploads;

    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Personal Information (Step 1)
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:10|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|same:password')]
    public string $password_confirmation = '';

    #[Validate('nullable|in:male,female')]
    public ?string $gender = null;

    #[Validate('nullable|date|before:today')]
    public ?string $dob = null;

    // Professional Information (Step 2)
    #[Validate('required|in:entrepreneur,student,professional,other')]
    public string $applicant_type = 'entrepreneur';

    #[Validate('nullable|string|max:255')]
    public ?string $job_title = null;

    #[Validate('nullable|string|max:255')]
    public ?string $company_name = null;

    #[Validate('nullable|string|max:255')]
    public ?string $company_field = null;

    #[Validate('nullable|string')]
    public ?string $skills = null;

    // Student fields
    #[Validate('nullable|string|max:255')]
    public ?string $university = null;

    #[Validate('nullable|string|max:255')]
    public ?string $faculty = null;

    #[Validate('nullable|string|max:50')]
    public ?string $grade = null;

    // Contact & Location (Step 3)
    #[Validate('nullable|string|max:500')]
    public ?string $address = null;

    #[Validate('nullable|string|max:100')]
    public ?string $city = null;

    #[Validate('nullable|string|max:100')]
    public ?string $country = 'Egypt';

    #[Validate('nullable|string|max:20')]
    public ?string $whatsapp = null;

    #[Validate('nullable|string|max:255')]
    public ?string $emergency_contact_name = null;

    #[Validate('nullable|string|max:20')]
    public ?string $emergency_contact_phone = null;

    // Application Details (Step 4)
    #[Validate('nullable|string|max:2000')]
    public ?string $motivation = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $startup_idea = null;

    // add asking for if the members has visit any coworking space before, and how he found us
    #[Validate('nullable|string|max:2000')]
    public ?string $visited_coworking_space_before = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $how_found_us = null;

    #[Validate('required|boolean')]
    public bool $terms_accepted = false;

    #[Validate('nullable|boolean')]
    public bool $marketing_messages_accepted = false;


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
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    protected function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|min:10|max:20',
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|same:password',
            ]),
            2 => $this->validate([
                'applicant_type' => 'required|in:entrepreneur,student,professional,other',
            ]),
            3 => $this->validate([
                'city' => 'nullable|string|max:100',
            ]),
            4 => $this->validate([
                'terms_accepted' => 'accepted',
            ]),
            default => null,
        };
    }

    public function submit(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:10|max:20',
            'password' => 'required|string|min:8',
            'terms_accepted' => 'accepted',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'type' => match ($this->applicant_type) {
                'student' => UserType::STUDENT,
                default => UserType::MEMBER,
            },
            'application_status' => ApplicationStatus::PENDING,
            'job_title' => $this->job_title,
            'skills' => $this->skills,
            'company_name' => $this->company_name,
            'company_field' => $this->company_field,
            'university' => $this->university,
            'faculty' => $this->faculty,
            'grade' => $this->grade,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'whatsapp' => $this->whatsapp,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'gender' => $this->gender ? Gender::from($this->gender) : null,
            'dob' => $this->dob,
            'motivation' => $this->motivation,
            'startup_idea' => $this->startup_idea,
            'visited_coworking_space_before' => $this->visited_coworking_space_before,
            'how_found_us' => $this->how_found_us,
            'marketing_messages_accepted' => $this->marketing_messages_accepted,
        ]);

        session()->flash('success', 'Your application has been submitted successfully! We will review it and get back to you soon.');

        $this->redirect(route('application.success'));
    }
};

?>

<main class="flex-1">
    <x-sections.hero
        title="Apply to <span class='text-primary'>Siliconile</span>"
        subtitle="Join Luxor's premier startup community. Complete the application form below to start your entrepreneurial journey with us."
    />

    <x-sections.content>
        <div class="max-w-3xl mx-auto">
            <!-- Progress Steps -->
            <div class="mb-8 md:mb-12">
                <div class="flex items-center justify-between">
                    @foreach(range(1, $totalSteps) as $step)
                        <div class="flex items-center flex-1 {{ $step === $totalSteps ? 'flex-none' : '' }}">
                            <div class="relative flex flex-col items-center">
                                <button
                                    wire:click="goToStep({{ $step }})"
                                    @class([
                                        'w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-sm sm:text-base font-semibold transition-colors flex-shrink-0',
                                        'bg-primary text-primary-foreground' => $currentStep === $step,
                                        'bg-primary/20 text-primary' => $currentStep > $step,
                                        'bg-muted text-muted-foreground' => $currentStep < $step,
                                        'cursor-pointer hover:bg-primary/30' => $currentStep > $step,
                                        'cursor-default' => $currentStep <= $step,
                                    ])
                                    @if($currentStep < $step) disabled @endif
                                >
                                    @if($currentStep > $step)
                                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @else
                                        {{ $step }}
                                    @endif
                                </button>
                                <span @class([
                                    'absolute -bottom-6 sm:-bottom-7 text-xs sm:text-sm whitespace-nowrap text-center',
                                    'font-semibold text-primary' => $currentStep === $step,
                                    'text-muted-foreground' => $currentStep !== $step,
                                ])>
                                    @switch($step)
                                        @case(1) <span class="hidden sm:inline">Personal</span><span class="sm:hidden">Info</span> @break
                                        @case(2) <span class="hidden sm:inline">Professional</span><span class="sm:hidden">Work</span> @break
                                        @case(3) Contact @break
                                        @case(4) Submit @break
                                    @endswitch
                                </span>
                            </div>
                            @if($step < $totalSteps)
                                <div @class([
                                    'flex-1 h-0.5 sm:h-1 mx-1 sm:mx-2',
                                    'bg-primary' => $currentStep > $step,
                                    'bg-muted' => $currentStep <= $step,
                                ])></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <x-ui.card>
                <form wire:submit="submit">
                    <!-- Step 1: Personal Information -->
                    <div x-show="$wire.currentStep === 1" x-transition>
                        <h2 class="text-2xl font-bold mb-6">Personal Information</h2>
                        <div class="space-y-6">
                            <x-ui.input
                                wire:model="name"
                                label="Full Name"
                                placeholder="Enter your full name"
                                :required="true"
                                :error="$errors->first('name')"
                            />

                            <x-ui.input
                                wire:model="email"
                                type="email"
                                label="Email Address"
                                placeholder="your@email.com"
                                :required="true"
                                :error="$errors->first('email')"
                            />

                            <x-ui.input
                                wire:model="phone"
                                type="tel"
                                label="Phone Number"
                                placeholder="+20 1XX XXX XXXX"
                                :required="true"
                                :error="$errors->first('phone')"
                            />

                            <div class="grid gap-4 md:grid-cols-2">
                                <x-ui.input
                                    wire:model="password"
                                    type="password"
                                    label="Password"
                                    placeholder="Min 8 characters"
                                    :required="true"
                                    :error="$errors->first('password')"
                                />

                                <x-ui.input
                                    wire:model="password_confirmation"
                                    type="password"
                                    label="Confirm Password"
                                    placeholder="Confirm your password"
                                    :required="true"
                                    :error="$errors->first('password_confirmation')"
                                />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <x-ui.select
                                    wire:model="gender"
                                    label="Gender"
                                    :options="['male' => 'Male', 'female' => 'Female']"
                                    placeholder="Select gender"
                                />

                                <x-ui.input
                                    wire:model="dob"
                                    type="date"
                                    label="Date of Birth"
                                    :error="$errors->first('dob')"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Professional Information -->
                    <div x-show="$wire.currentStep === 2" x-transition>
                        <h2 class="text-2xl font-bold mb-6">Professional Information</h2>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">I am a...</label>
                                <div class="grid gap-4 md:grid-cols-2">
                                    @foreach(['entrepreneur' => 'Entrepreneur / Founder', 'student' => 'Student', 'professional' => 'Working Professional', 'other' => 'Other'] as $value => $label)
                                        <label @class([
                                            'flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors',
                                            'border-primary bg-primary/5' => $applicant_type === $value,
                                            'hover:border-primary/50' => $applicant_type !== $value,
                                        ])>
                                            <input
                                                type="radio"
                                                wire:model.live="applicant_type"
                                                value="{{ $value }}"
                                                class="w-4 h-4 text-primary"
                                            />
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            @if($applicant_type === 'student')
                                <div class="space-y-4 p-4 bg-muted/50 rounded-lg">
                                    <h3 class="font-semibold">Student Information</h3>
                                    <x-ui.input
                                        wire:model="university"
                                        label="University"
                                        placeholder="e.g., Cairo University"
                                    />
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <x-ui.input
                                            wire:model="faculty"
                                            label="Faculty"
                                            placeholder="e.g., Computer Science"
                                        />
                                        <x-ui.input
                                            wire:model="grade"
                                            label="Year / Grade"
                                            placeholder="e.g., 3rd Year"
                                        />
                                    </div>
                                </div>
                            @else
                                <x-ui.input
                                    wire:model="job_title"
                                    label="Job Title / Role"
                                    placeholder="e.g., Founder & CEO"
                                />

                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-ui.input
                                        wire:model="company_name"
                                        label="Company / Startup Name"
                                        placeholder="Your company name"
                                    />
                                    <x-ui.input
                                        wire:model="company_field"
                                        label="Industry / Field"
                                        placeholder="e.g., EdTech, FinTech"
                                    />
                                </div>
                            @endif

                            <x-ui.textarea
                                wire:model="skills"
                                label="Skills & Expertise"
                                placeholder="List your key skills (e.g., Web Development, Marketing, Finance...)"
                                :rows="3"
                            />
                        </div>
                    </div>

                    <!-- Step 3: Contact Information -->
                    <div x-show="$wire.currentStep === 3" x-transition>
                        <h2 class="text-2xl font-bold mb-6">Contact & Location</h2>
                        <div class="space-y-6">
                            <x-ui.textarea
                                wire:model="address"
                                label="Address"
                                placeholder="Your full address"
                                :rows="2"
                            />

                            <div class="grid gap-4 md:grid-cols-2">
                                <x-ui.input
                                    wire:model="city"
                                    label="City"
                                    placeholder="e.g., Luxor"
                                />
                                <x-ui.input
                                    wire:model="country"
                                    label="Country"
                                    placeholder="e.g., Egypt"
                                />
                            </div>

                            <x-ui.input
                                wire:model="whatsapp"
                                type="tel"
                                label="WhatsApp Number"
                                placeholder="+20 1XX XXX XXXX"
                            />

                            <div class="space-y-4 p-4 bg-muted/50 rounded-lg">
                                <h3 class="font-semibold">Emergency Contact</h3>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-ui.input
                                        wire:model="emergency_contact_name"
                                        label="Contact Name"
                                        placeholder="Full name"
                                    />
                                    <x-ui.input
                                        wire:model="emergency_contact_phone"
                                        type="tel"
                                        label="Contact Phone"
                                        placeholder="Phone number"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Application Details -->
                    <div x-show="$wire.currentStep === 4" x-transition>
                        <h2 class="text-2xl font-bold mb-6">Almost Done!</h2>
                        <div class="space-y-6">
                            <x-ui.textarea
                                wire:model="motivation"
                                label="Why do you want to join Siliconile?"
                                placeholder="Tell us about your goals and what you hope to achieve..."
                                :rows="4"
                            />

                            <x-ui.textarea
                                wire:model="startup_idea"
                                label="Tell us about your startup or project idea"
                                placeholder="Describe your idea, the problem you're solving, and your target market..."
                                :rows="4"
                            />

                            <x-ui.textarea
                                wire:model="visited_coworking_space_before"
                                label="Have you visited any coworking space before?"
                                placeholder="Share your experience if any..."
                                :rows="3"
                            />

                            <x-ui.textarea
                                wire:model="how_found_us"
                                label="How did you find out about Siliconile?"
                                placeholder="e.g., social media, friend, event, etc."
                                :rows="3"
                            />

                            <div class="p-4 bg-muted/50 rounded-lg">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="terms_accepted"
                                        class="mt-1 w-4 h-4 text-primary rounded"
                                    />
                                    <span class="text-sm">
                                        I agree to the <a href="#" class="text-primary hover:underline">Terms of Service</a> and <a href="#" class="text-primary hover:underline">Privacy Policy</a>. I understand that my application will be reviewed and I will be contacted regarding the next steps.
                                    </span>
                                </label>
                                @error('terms_accepted')
                                    <p class="text-sm text-destructive mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="p-4 bg-muted/50 rounded-lg">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="marketing_messages_accepted"
                                        class="mt-1 w-4 h-4 text-primary rounded"
                                    />
                                    <span class="text-sm">
                                        I consent to receive marketing communications from Siliconile about events, programs, and opportunities. I understand I can unsubscribe at any time.
                                    </span>
                                </label>
                                @error('marketing_messages_accepted')
                                    <p class="text-sm text-destructive mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t">
                        @if($currentStep > 1)
                            <x-ui.button type="button" wire:click="previousStep" variant="outline">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Previous
                            </x-ui.button>
                        @else
                            <div></div>
                        @endif

                        @if($currentStep < $totalSteps)
                            <x-ui.button type="button" wire:click="nextStep">
                                Next
                                <svg class="h-4 w-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </x-ui.button>
                        @else
                            <x-ui.button type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove>Submit Application</span>
                                <span wire:loading>Submitting...</span>
                            </x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>
        </div>
    </x-sections.content>
</main>
