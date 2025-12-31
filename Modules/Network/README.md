# Network Module

Mikrotik RouterOS integration for WiFi/Hotspot user management in the Siliconile Coworking Space Management System.

## Overview

The Network module provides automatic WiFi access management by integrating with Mikrotik routers via the RouterOS API. It enables/disables hotspot users based on their membership subscription status.

## Features

- **Automatic User Sync**: Hotspot users are automatically created/enabled when subscriptions are activated
- **Automatic Access Revocation**: Users are disabled and kicked when subscriptions expire
- **Password Reset**: Admin can reset WiFi passwords from the Member resource
- **Real-time Dashboard**: Widget showing currently online hotspot users
- **Audit Logging**: All operations are logged for troubleshooting
- **Fail-safe Design**: Router connection issues don't crash the application

## Installation

1. **Install the RouterOS API package:**
   ```bash
   composer require evilfreelancer/routeros-api-php
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate
   ```

3. **Enable the module:**
   ```bash
   php artisan module:enable Network
   ```

4. **Configure via Filament Admin:**
   Navigate to Settings → Network / WiFi and configure:
   - Router IP Address
   - API Port (default: 8728)
   - Admin credentials
   - Hotspot profile and server name

5. **Test the connection:**
   Use the "Test Connection" button on the settings page

## Architecture

### Service Layer

The core of the module is the `MikrotikService` class which handles all router interactions:

```php
use Modules\Network\Services\MikrotikService;

$service = app(MikrotikService::class);

// Sync a member (create or update)
$result = $service->syncUser($member);

// Disable and kick a member
$service->disableAndKickUser($member);

// Reset password
$userDto = $service->resetPassword($member, 'newpassword');

// Get online count
$count = $service->getOnlineCount();
```

### Jobs (Async Operations)

All router operations should be dispatched as jobs to prevent HTTP timeouts:

```php
use Modules\Network\Jobs\SyncMikrotikUserJob;
use Modules\Network\Jobs\KickMikrotikUserJob;
use Modules\Network\Jobs\ResetMikrotikPasswordJob;

// Sync user
SyncMikrotikUserJob::dispatch($member);

// Kick and disable
KickMikrotikUserJob::dispatch($member, disableUser: true);

// Reset password
ResetMikrotikPasswordJob::dispatch($member);
```

### Event Listeners

The module listens to Membership events automatically:

| Event | Action |
|-------|--------|
| `SubscriptionCreated` | Enable WiFi access |
| `SubscriptionActivated` | Enable WiFi access |
| `SubscriptionRenewed` | Ensure access enabled |
| `SubscriptionExpired` | Disable + Kick |
| `SubscriptionCancelled` | Disable + Kick |
| `SubscriptionSuspended` | Disable + Kick |

### Filament Integration

**Settings Page:** `Settings → Network / WiFi`

**Sync Logs:** `Network → Sync Logs`

**Dashboard Widget:** Online Users count

**Member Actions:** Add these actions to your Member resource:

```php
use Modules\Network\Filament\Actions\ResetWifiPasswordAction;
use Modules\Network\Filament\Actions\SyncMikrotikUserAction;
use Modules\Network\Filament\Actions\KickMikrotikUserAction;

// In your resource's table actions:
->actions([
    ResetWifiPasswordAction::make(),
    SyncMikrotikUserAction::make(),
    KickMikrotikUserAction::make(),
])
```

## Console Commands

```bash
# Sync all members based on subscription status
php artisan network:sync-all

# Dry run (preview changes)
php artisan network:sync-all --dry-run

# Use queue for processing
php artisan network:sync-all --sync-queue
```

## Configuration

### Username Format

The module supports multiple username formats. Configure in settings:

- `{phone}` - Member's phone number (default)
- `{email}` - Member's email address
- `{member_code}` - Member code (e.g., MEM-001)
- `{member_id}` - Database ID

### Security

- Router password is stored encrypted using Laravel's `Crypt` facade
- API connections can use SSL (port 8729)
- All operations are logged for audit purposes

## Troubleshooting

### Connection Issues

1. Verify router IP is accessible from server
2. Check firewall allows port 8728/8729
3. Ensure API service is enabled on router: `/ip service enable api`
4. Verify admin credentials have API access

### Sync Failures

1. Check `Network → Sync Logs` for error messages
2. Review Laravel logs: `storage/logs/laravel.log`
3. Test connection from settings page
4. Verify hotspot profile exists on router

### Manual Recovery

If router database is wiped:
```bash
php artisan network:sync-all --force
```

## Database Schema

### network_sync_logs
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| member_id | bigint | FK to members |
| action | string | Enum: created, updated, enabled, disabled, kicked, etc. |
| status | string | success or failed |
| error_message | text | Error details if failed |
| metadata | json | Additional context |
| router_ip | string | Router IP at time of operation |
| created_at | timestamp | When operation occurred |

### Settings (spatie/laravel-settings)
Group: `network_router`

| Key | Type | Description |
|-----|------|-------------|
| ip_address | string | Router IP |
| port | int | API port |
| admin_username | string | API username |
| admin_password | string | Encrypted password |
| hotspot_profile | string | Default profile |
| hotspot_server | string | Server name |
| enabled | bool | Module enabled |
| username_format | string | Username pattern |

## Dependencies

- `evilfreelancer/routeros-api-php`: RouterOS API client
- `spatie/laravel-settings`: Settings storage
- Membership module: For subscription events
