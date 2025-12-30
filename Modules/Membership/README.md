# Membership Module

## Overview
The Membership module provides comprehensive member and subscription lifecycle management for the Siliconile Coworking Space Management System. It follows event-driven architecture principles to remain decoupled from other modules while providing robust subscription tracking.

## Features

### 1. Member Management
- **Individual & Corporate Accounts**: Support for both individual members and corporate teams
- **Member Profiles**: Rich CRM data including bio, interests, social links
- **Team Hierarchy**: Corporate members can have sub-members
- **Referral Tracking**: Built-in referral system with automatic counting
- **Member Code Generation**: Unique member codes (e.g., `MEM-2025-001`)

### 2. Subscription Lifecycle
- **Multiple Status States**: 
  - `pending` - Awaiting payment
  - `active` - Currently active
  - `expiring` - Will expire within 7 days
  - `grace_period` - Expired but within grace period
  - `expired` - Fully expired
  - `cancelled` - User/admin cancelled
  - `suspended` - Admin suspended
  
- **Auto-Renewal**: Optional automatic subscription renewal
- **Grace Period**: Configurable grace period after expiration
- **Subscription History**: Complete audit trail of status changes

### 3. Plan Features
- **Extensible Features**: Store custom plan features via `plan_features` table
- **Core Features**: Built-in features on Plan model (WiFi, Meeting Rooms, Desks, etc.)
- **Feature Types**: Support for boolean, integer, string, and float feature types

### 4. Event-Driven Architecture
The module emits the following events for other modules to listen to:

- `SubscriptionCreated`
- `SubscriptionActivated`
- `SubscriptionExpiring`
- `SubscriptionExpired`
- `SubscriptionRenewed`
- `SubscriptionCancelled`
- `SubscriptionSuspended`

**Example: Network Module Integration**
```php
// In Network module's EventServiceProvider
protected $listen = [
    'Modules\Membership\app\Events\SubscriptionExpired' => [
        'Modules\Network\app\Listeners\DisableWifiOnExpiry',
    ],
    'Modules\Membership\app\Events\SubscriptionActivated' => [
        'Modules\Network\app\Listeners\EnableWifiOnActivation',
    ],
];
```

## Database Schema

### Tables
1. **members** - Member profiles and identity
2. **subscriptions** - Subscription records with lifecycle tracking
3. **subscription_history** - Audit trail for status changes
4. **plan_features** - Extensible plan features

### Key Relationships
```
User (Core) ←→ Member ←→ Subscription → Plan (Core)
                ↓
            Team Members (self-referencing)
                ↓
            Referrals (self-referencing)
```

## Services

### MembershipService
Core business logic for member management:

```php
use Modules\Membership\app\Services\MembershipService;

$membershipService = app(MembershipService::class);

// Create individual member
$member = $membershipService->createMember(
    user: $user,
    memberType: MemberType::INDIVIDUAL
);

// Create corporate member with team
$corporateMember = $membershipService->createCorporateMember(
    primaryUser: $primaryUser,
    companyName: 'Acme Corp',
    corporateData: ['company_vat_number' => '123456'],
    teamUsers: [$teamUser1, $teamUser2]
);

// Deactivate member
$membershipService->deactivateMember($member, 'No longer needed');
```

### SubscriptionService
Handles subscription lifecycle:

```php
use Modules\Membership\app\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

// Create subscription
$subscription = $subscriptionService->createSubscription(
    member: $member,
    plan: $plan,
    autoRenew: true,
    gracePeriodDays: 3
);

// Activate after payment
$subscriptionService->activateSubscription($subscription, $paymentId);

// Renew subscription
$subscriptionService->renewSubscription($subscription, $paymentId);

// Cancel subscription
$subscriptionService->cancelSubscription(
    subscription: $subscription,
    reason: 'User requested cancellation',
    cancelledBy: auth()->id()
);

// Get subscription summary
$summary = $subscriptionService->getSubscriptionSummary($member);
```

## Filament Admin Resources

### MemberResource
Full CRUD for members with:
- Create/Edit forms with conditional sections (Corporate info only for corporate accounts)
- List view with filters (type, status, referrals)
- 360-degree member view with subscription summary
- Actions: View, Edit, Delete

**Location:** `Modules/Membership/app/Filament/Resources/MemberResource.php`

### SubscriptionResource
Subscription management with:
- Create/Edit forms with reactive plan selection
- Automatic date calculations based on plan duration
- Custom actions: Activate, Renew, Cancel
- Filters: Status, auto-renew, expiring soon, date range

**Location:** `Modules/Membership/app/Filament/Resources/SubscriptionResource.php`

## Livewire Volt Components

### Pricing Table Component
Public-facing pricing table that displays all active plans:

**Usage:**
```blade
<livewire:membership.pricing-table />
```

**Features:**
- Groups plans by type (Daily, Weekly, Monthly, etc.)
- Highlights featured plans
- Shows capacity (X/Y spots taken)
- Displays plan features with icons
- "Get Started" CTA buttons

**Location:** `Modules/Membership/resources/views/livewire/pricing-table.blade.php`

### Subscription Status Component
Dashboard widget showing member's current subscription:

**Usage:**
```blade
<livewire:membership.subscription-status />
```

**Features:**
- Shows current plan and status
- Days remaining countdown
- Warning badges for expiring/grace period
- Auto-renewal indicator
- "Manage Subscription" CTA

**Location:** `Modules/Membership/resources/views/livewire/subscription-status.blade.php`

## Console Commands

### Process Subscriptions Command
Runs subscription lifecycle automation:

```bash
php artisan membership:process-subscriptions
```

**Actions:**
1. Mark subscriptions expiring within 7 days as `expiring` → fires `SubscriptionExpiring` event
2. Process subscriptions past end date → move to `grace_period` or `expired`
3. Check grace periods → expire if grace period ended

**Scheduling:**
The command is automatically scheduled to run daily at 1:00 AM (configured in `MembershipServiceProvider`).

## Model Scopes

### Member Scopes
```php
Member::active()->get();           // Only active members
Member::individual()->get();       // Individual members only
Member::corporate()->get();        // Corporate members only
Member::topLevel()->get();         // No parent (exclude team members)
```

### Subscription Scopes
```php
Subscription::active()->get();                  // Active subscriptions
Subscription::expiring()->get();                // Expiring soon status
Subscription::expired()->get();                 // Expired status
Subscription::gracePeriod()->get();             // In grace period
Subscription::expiringWithinDays(7)->get();     // Expiring in X days
Subscription::dueForRenewal()->get();           // Auto-renew due soon
```

## Integration Examples

### Example 1: Listen to Subscription Events (Network Module)
```php
// In Modules/Network/app/Listeners/DisableWifiOnExpiry.php
namespace Modules\Network\app\Listeners;

use Modules\Membership\app\Events\SubscriptionExpired;
use Modules\Network\app\Services\MikrotikService;

class DisableWifiOnExpiry
{
    public function handle(SubscriptionExpired $event): void
    {
        $member = $event->subscription->member;
        
        try {
            app(MikrotikService::class)->disableUser($member->user);
        } catch (\Exception $e) {
            Log::error('Failed to disable WiFi', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Example 2: Create Subscription After Payment (Billing Module)
```php
// In Modules/Billing/app/Listeners/CreateSubscriptionOnPayment.php
use Modules\Billing\app\Events\OrderPaid;
use Modules\Membership\app\Services\SubscriptionService;
use Modules\Membership\app\Services\MembershipService;

class CreateSubscriptionOnPayment
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        
        // Get or create member
        $membershipService = app(MembershipService::class);
        $member = Member::firstOrCreate(
            ['user_id' => $order->user_id],
            ['member_code' => $membershipService->generateMemberCode()]
        );
        
        // Create and activate subscription
        $subscriptionService = app(SubscriptionService::class);
        $subscription = $subscriptionService->createSubscription(
            member: $member,
            plan: $order->plan,
            autoRenew: $order->auto_renew ?? false
        );
        
        $subscriptionService->activateSubscription($subscription, $order->id);
    }
}
```

## Enums

### MemberType
```php
MemberType::INDIVIDUAL  // Individual member
MemberType::CORPORATE   // Corporate account
```

### SubscriptionStatus
```php
SubscriptionStatus::PENDING
SubscriptionStatus::ACTIVE
SubscriptionStatus::EXPIRING
SubscriptionStatus::GRACE_PERIOD
SubscriptionStatus::EXPIRED
SubscriptionStatus::CANCELLED
SubscriptionStatus::SUSPENDED
```

## Testing

Run migrations:
```bash
php artisan migrate
```

Process subscriptions manually:
```bash
php artisan membership:process-subscriptions
```

## Best Practices

1. **Always use Services**: Don't put business logic in controllers or Volt components
2. **Listen to Events**: Other modules should listen to membership events, not call directly
3. **Use Scopes**: Leverage Eloquent scopes for common queries
4. **Log Critical Actions**: Subscription status changes are automatically logged in `subscription_history`
5. **Handle Exceptions**: Network/external integrations should use try-catch with logging

## Future Enhancements
- Auto-renewal payment processing integration
- Subscription upgrades/downgrades
- Member wallet/credits system
- Subscription pause/resume functionality
- Advanced reporting and analytics

---

**Module Author:** Senior Laravel Architect  
**Last Updated:** December 30, 2025  
**Laravel Version:** 12  
**Filament Version:** 4
