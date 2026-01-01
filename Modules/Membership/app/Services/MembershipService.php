<?php

declare(strict_types=1);

namespace Modules\Membership\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Membership\Enums\MemberType;
use Modules\Membership\Events\MemberCreated;
use Modules\Membership\Models\Member;

class MembershipService
{
    /**
     * Create a new member profile.
     */
    public function createMember(
        User $user,
        MemberType $memberType = MemberType::INDIVIDUAL,
        array $additionalData = []
    ): Member {
        return DB::transaction(function () use ($user, $memberType, $additionalData) {
            $member = Member::create(array_merge([
                'user_id' => $user->id,
                'member_code' => $this->generateMemberCode(),
                'member_type' => $memberType,
                'is_active' => true,
            ], $additionalData));

            // Fire event for new member creation
            event(new MemberCreated($member));

            return $member;
        });
    }

    /**
     * Generate a unique member code.
     */
    public function generateMemberCode(): string
    {
        do {
            $code = 'MEM-'.now()->year.'-'.str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Member::where('member_code', $code)->exists());

        return $code;
    }

    /**
     * Create a corporate member with team members.
     */
    public function createCorporateMember(
        User $primaryUser,
        string $companyName,
        array $corporateData = [],
        array $teamUsers = []
    ): Member {
        return DB::transaction(function () use ($primaryUser, $companyName, $corporateData, $teamUsers) {
            // Create the corporate member
            $corporateMember = $this->createMember(
                user: $primaryUser,
                memberType: MemberType::CORPORATE,
                additionalData: array_merge($corporateData, [
                    'company_name' => $companyName,
                ])
            );

            // Create team members if provided
            foreach ($teamUsers as $teamUser) {
                if ($teamUser instanceof User) {
                    $this->createMember(
                        user: $teamUser,
                        memberType: MemberType::INDIVIDUAL,
                        additionalData: [
                            'parent_member_id' => $corporateMember->id,
                        ]
                    );
                }
            }

            return $corporateMember->fresh(['teamMembers']);
        });
    }

    /**
     * Add a team member to a corporate account.
     */
    public function addTeamMember(Member $corporateMember, User $user): Member
    {
        if (! $corporateMember->isCorporate()) {
            throw new \InvalidArgumentException('Parent member must be a corporate account');
        }

        return $this->createMember(
            user: $user,
            memberType: MemberType::INDIVIDUAL,
            additionalData: [
                'parent_member_id' => $corporateMember->id,
            ]
        );
    }

    /**
     * Update member profile.
     */
    public function updateMember(Member $member, array $data): Member
    {
        $member->update($data);

        return $member->fresh();
    }

    /**
     * Deactivate a member.
     */
    public function deactivateMember(Member $member, string $reason): Member
    {
        return DB::transaction(function () use ($member, $reason) {
            $member->update([
                'is_active' => false,
                'deactivation_reason' => $reason,
                'deactivated_at' => now(),
            ]);

            // Cancel active subscriptions
            $activeSubscription = $member->activeSubscription();
            if ($activeSubscription) {
                app(SubscriptionService::class)->cancelSubscription(
                    subscription: $activeSubscription,
                    reason: $reason
                );
            }

            return $member->fresh();
        });
    }

    /**
     * Reactivate a member.
     */
    public function reactivateMember(Member $member): Member
    {
        $member->update([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
        ]);

        return $member->fresh();
    }

    /**
     * Track a referral.
     */
    public function trackReferral(Member $referredMember, Member $referrer): void
    {
        $referredMember->update([
            'referred_by_member_id' => $referrer->id,
        ]);

        $referrer->increment('referral_count');
    }

    /**
     * Update member preferences.
     */
    public function updatePreferences(Member $member, array $preferences): Member
    {
        $currentPreferences = $member->notification_preferences ?? [];
        $member->update([
            'notification_preferences' => array_merge($currentPreferences, $preferences),
        ]);

        return $member->fresh();
    }

    /**
     * Get member statistics.
     */
    public function getMemberStats(Member $member): array
    {
        return [
            'total_subscriptions' => $member->subscriptions()->count(),
            'active_subscription' => $member->activeSubscription(),
            'total_referrals' => $member->referral_count,
            'team_size' => $member->isCorporate() ? $member->teamMembers()->count() : 0,
            'member_since' => $member->created_at,
            'is_active' => $member->is_active,
        ];
    }
}
