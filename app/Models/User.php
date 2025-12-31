<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ApplicationStatus;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'type',
        'application_status',
        'membership_status',
        'job_title',
        'skills',
        'company_name',
        'company_field',
        'company_registration_number',
        'university',
        'faculty',
        'grade',
        'student_id',
        'address',
        'city',
        'state',
        'country',
        'whatsapp',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'photo',
        'gender',
        'national_id_document',
        'membership_started_at',
        'membership_expires_at',
        'current_plan_id',
        'verified_at',
        'verified_by',
        'admin_notes',
        'rejection_reason',
        'dob',
        'motivation',
        'startup_idea',
        'visited_coworking_space_before',
        'how_found_us',
        'marketing_messages_accepted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
            'application_status' => ApplicationStatus::class,
            'membership_status' => MembershipStatus::class,
            'gender' => Gender::class,
            'dob' => 'date',
            'membership_started_at' => 'date',
            'membership_expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];


    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the current plan for this user.
     */
    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    /**
     * Get the admin who verified this user.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if membership is active.
     */
    public function isActiveMember(): bool
    {
        return $this->membership_status === MembershipStatus::ACTIVE
            && $this->membership_expires_at?->isFuture();
    }

    /**
     * Check if membership is expiring soon (within 7 days).
     */
    public function isExpiringSoon(): bool
    {
        return $this->membership_expires_at?->diffInDays(now()) <= 7
            && $this->membership_expires_at->isFuture();
    }

    /**
     * Check if application is approved.
     */
    public function isApproved(): bool
    {
        return $this->application_status === ApplicationStatus::APPROVED;
    }


}
