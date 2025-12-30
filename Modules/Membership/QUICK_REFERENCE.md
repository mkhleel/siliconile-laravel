# Membership Module - Quick Reference

## Quick Start

### Create a Member
```php
use Modules\Membership\Services\MembershipService;

$service = app(MembershipService::class);

// Individual member
$member = $service->createMember($user);

// Corporate member with team
$corporate = $service->createCorporateMember(
    primaryUser: $ceoUser,
    companyName: 'Acme Corp',
    corporateData: ['company_vat_number' => '123456'],
    teamUsers: [$employee1, $employee2]
);
```

### Create & Activate Subscription
```php
use Modules\Membership\Services\SubscriptionService;

$service = app(SubscriptionService::class);

// Create subscription
$subscription = $service->createSubscription(
    member: $member,
    plan: $plan,
    autoRenew: true,
    gracePeriodDays: 3
);

// Activate after payment
$service->activateSubscription($subscription, $paymentId);
```

### Query Members
```php
use Modules\Membership\Models\Member;

// Active members
$activeMembers = Member::active()->get();

// Corporate accounts only
$corporates = Member::corporate()->topLevel()->get();

// Members with active subscriptions
$withActiveSubs = Member::whereHas('subscriptions', function($q) {
    $q->active();
})->get();
```

### Query Subscriptions
```php
use Modules\Membership\Models\Subscription;

// All active subscriptions
$active = Subscription::active()->get();

// Expiring within 7 days
$expiring = Subscription::expiringWithinDays(7)->get();

// Due for auto-renewal
$dueForRenewal = Subscription::dueForRenewal()->get();
```

## Event Listeners (Other Modules)

### Listen to Membership Events
```php
// In YourModule/Providers/EventServiceProvider.php
protected $listen = [
    'Modules\Membership\Events\SubscriptionActivated' => [
        'YourModule\Listeners\OnSubscriptionActivated',
    ],
    'Modules\Membership\Events\SubscriptionExpired' => [
        'YourModule\Listeners\OnSubscriptionExpired',
    ],
];
```

### Example: Network Module Integration
```php
namespace Modules\Network\Listeners;

use Modules\Membership\Events\SubscriptionExpired;
use Modules\Network\Services\MikrotikService;

class DisableWifiOnExpiry
{
    public function handle(SubscriptionExpired $event): void
    {
        $member = $event->subscription->member;
        
        app(MikrotikService::class)->disableUser(
            $member->user
        );
    }
}
```

## Livewire Volt Components

### Pricing Table
```blade
<!-- In your public pages -->
<livewire:membership.pricing-table />
```

### Subscription Status Widget
```blade
<!-- In member dashboard -->
<livewire:membership.subscription-status />
```

## Console Commands

### Process Subscription Lifecycle
```bash
# Run manually
php artisan membership:process-subscriptions

# Automatically runs daily at 1:00 AM
```

## Filament Admin

### Navigate to Resources
- Members: `/admin/members`
- Subscriptions: `/admin/subscriptions`

### Custom Actions Available
- **View Subscription**: Activate, Renew, Cancel buttons
- **Create Member**: Auto-generates member code

## Common Patterns

### Check Subscription Status
```php
$member = Member::find($id);

if ($member->hasActiveSubscription()) {
    $subscription = $member->activeSubscription();
    
    if ($subscription->isExpiringSoon()) {
        // Show renewal prompt
    }
    
    $daysLeft = $subscription->daysRemaining();
}
```

### Handle Payment Completion (Billing Module)
```php
use Modules\Billing\Events\OrderPaid;
use Modules\Membership\Services\SubscriptionService;

class CreateSubscriptionOnPayment
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        
        $subscription = app(SubscriptionService::class)
            ->createSubscription(
                member: $order->member,
                plan: $order->plan
            );
            
        app(SubscriptionService::class)
            ->activateSubscription($subscription, $order->id);
    }
}
```

### Track Referrals
```php
$service = app(MembershipService::class);

$service->trackReferral(
    referredMember: $newMember,
    referrer: $existingMember
);

// Automatically increments $existingMember->referral_count
```

### Deactivate Member
```php
$service = app(MembershipService::class);

$service->deactivateMember(
    member: $member,
    reason: 'Requested account closure'
);

// Also cancels active subscriptions
```

## Database Structure Reference

### Key Tables
- `members` - Member profiles
- `subscriptions` - Subscription records
- `subscription_history` - Audit trail
- `plan_features` - Custom plan features

### Key Relationships
```
User → Member → Subscription → Plan
        ↓           ↓
    TeamMembers   History
```

## Status Flow

```
PENDING → ACTIVE → EXPIRING → GRACE_PERIOD → EXPIRED
   ↓         ↓         ↓            ↓
   CANCELLED ← ← ← ← ← ← ← ← ← ← ←
   
   SUSPENDED (admin action, any time)
```

## Scheduled Tasks

The module registers one scheduled task:
- **Process Subscriptions**: Daily at 1:00 AM
  - Marks expiring subscriptions (7 days)
  - Processes expired subscriptions
  - Handles grace period expirations

## Configuration

Located in `Modules/Membership/config/config.php` (customize as needed):
```php
return [
    'expiring_soon_days' => 7,
    'default_grace_period_days' => 3,
    'member_code_prefix' => 'MEM',
];
```

## Useful Scopes

### Member
- `->active()` - Active members
- `->individual()` - Individual members
- `->corporate()` - Corporate members  
- `->topLevel()` - No parent member

### Subscription
- `->active()` - Active status
- `->expiring()` - Expiring status
- `->expired()` - Expired status
- `->gracePeriod()` - Grace period status
- `->expiringWithinDays($days)` - Custom window
- `->dueForRenewal()` - Auto-renew pending

---

**Quick Help**: See `README.md` for detailed documentation
