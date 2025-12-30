<?php

declare(strict_types=1);

namespace Modules\Membership\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Membership\Enums\MemberType;
use Modules\Membership\Database\Factories\MemberFactory;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'member_code',
        'member_type',
        'parent_member_id',
        'company_name',
        'company_vat_number',
        'company_address',
        'bio',
        'interests',
        'linkedin_url',
        'twitter_url',
        'website_url',
        'workspace_preferences',
        'notification_preferences',
        'referred_by_member_id',
        'referral_count',
        'is_active',
        'deactivation_reason',
        'deactivated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'member_type' => MemberType::class,
            'interests' => 'array',
            'workspace_preferences' => 'array',
            'notification_preferences' => 'array',
            'referral_count' => 'integer',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent member (for corporate team members).
     */
    public function parentMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'parent_member_id');
    }

    /**
     * Get all team members (sub-members under corporate account).
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(Member::class, 'parent_member_id');
    }

    /**
     * Get the member who referred this member.
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referred_by_member_id');
    }

    /**
     * Get all members referred by this member.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Member::class, 'referred_by_member_id');
    }

    /**
     * Get all subscriptions for this member.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the current active subscription.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->active()
            ->first();
    }

    /**
     * Check if member has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Scope a query to only include active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include individual members.
     */
    public function scopeIndividual($query)
    {
        return $query->where('member_type', MemberType::INDIVIDUAL);
    }

    /**
     * Scope a query to only include corporate members.
     */
    public function scopeCorporate($query)
    {
        return $query->where('member_type', MemberType::CORPORATE);
    }

    /**
     * Scope a query to only include top-level members (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_member_id');
    }

    /**
     * Check if this is a corporate account.
     */
    public function isCorporate(): bool
    {
        return $this->member_type === MemberType::CORPORATE;
    }

    /**
     * Check if this member is part of a corporate team.
     */
    public function isTeamMember(): bool
    {
        return $this->parent_member_id !== null;
    }

    /**
     * Get full display name (includes company name for corporate).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isCorporate() && $this->company_name) {
            return $this->company_name . ' (' . $this->user->name . ')';
        }

        return $this->user->name;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return MemberFactory::new();
    }
}
