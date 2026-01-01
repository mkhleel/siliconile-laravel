# Pushover Push Notifications Integration

## Overview

This integration sends real-time push notifications to your mobile device when new membership applications are submitted through Siliconile platform using [Pushover](https://pushover.net/).

## Features

- ✅ Real-time push notifications for new member applications
- ✅ Queued notifications (non-blocking)
- ✅ High-priority notifications
- ✅ Deep links to admin panel
- ✅ Detailed member information in notifications
- ✅ Graceful fallback if not configured
- ✅ Test command for verification

## Setup Instructions

### 1. Create Pushover Account

1. Visit [pushover.net](https://pushover.net/) and create a free account
2. Install the Pushover app on your iOS or Android device
3. Log in to the Pushover app with your account

### 2. Get Your Credentials

#### User Key
1. Visit [pushover.net](https://pushover.net/) and log in
2. Your **User Key** is displayed at the top of the page
3. Copy this key (format: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)

#### API Token (Application Token)
1. Visit [https://pushover.net/apps/build](https://pushover.net/apps/build)
2. Create a new application/API token:
   - **Name**: `Siliconile` (or any name you prefer)
   - **Type**: Application
   - **Description**: Member application notifications
   - **URL**: Your website URL (optional)
   - **Icon**: Upload your logo (optional)
3. Click "Create Application"
4. Copy the **API Token/Key** (format: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)

### 3. Configure Environment Variables

Add the following to your `.env` file:

```bash
# Pushover Push Notifications
PUSHOVER_API_TOKEN=your_api_token_here
PUSHOVER_USER_KEY=your_user_key_here
```

### 4. Test the Integration

Run the test command to verify your configuration:

```bash
php artisan pushover:test
```

You should receive a test notification on your mobile device if configured correctly.

## Usage

### Automatic Notifications

The system automatically sends notifications when:

- A new member is created through the admin panel
- A new member registers through the MembershipService
- Any membership application is submitted

### Notification Content

Each notification includes:

- 👤 Member name and email
- 🏢 Member type (Individual/Corporate)
- 🔢 Member code
- 🏢 Company name (for corporate members)
- 🔗 Direct link to member profile in admin panel

### Notification Priority

Member application notifications are sent as **high priority** (priority level 1), which means:

- Appears at the top of your notification list
- Bypasses quiet hours on your device
- Displays prominently

## Architecture

### Components

1. **PushoverService** (`Modules/Core/app/Services/PushoverService.php`)
   - Handles all Pushover API communication
   - Provides methods: `send()`, `sendHighPriority()`, `sendEmergency()`, `testConnection()`
   - Uses Laravel HTTP client
   - Automatic error handling and logging

2. **MemberCreated Event** (`Modules/Membership/app/Events/MemberCreated.php`)
   - Dispatched when new member is created
   - Contains Member model instance

3. **SendMemberCreatedNotification Listener** (`Modules/Membership/app/Listeners/SendMemberCreatedNotification.php`)
   - Listens to MemberCreated event
   - Implements `ShouldQueue` for background processing
   - Sends notification via PushoverService

4. **TestPushoverCommand** (`app/Console/Commands/TestPushoverCommand.php`)
   - Test command: `php artisan pushover:test`
   - Validates configuration
   - Sends test notification

### Event Flow

```
Member Created (Admin or Service)
       ↓
MemberCreated Event Dispatched
       ↓
SendMemberCreatedNotification Listener (Queued)
       ↓
PushoverService→send()
       ↓
Pushover API
       ↓
Your Mobile Device 📱
```

## API Documentation

### PushoverService Methods

```php
// Basic notification
$pushoverService->send([
    'message' => 'Your message here',
    'title' => 'Optional title',
]);

// High priority notification (bypasses quiet hours)
$pushoverService->sendHighPriority(
    message: 'Urgent message',
    title: 'Alert',
    url: 'https://example.com'
);

// Emergency notification (requires acknowledgment)
$pushoverService->sendEmergency(
    message: 'Critical alert',
    title: 'Emergency',
    emergencyOptions: [
        'retry' => 30,    // Retry every 30 seconds
        'expire' => 3600, // Expire after 1 hour
    ]
);

// Test connection
$pushoverService->testConnection();

// Check if configured
if ($pushoverService->isConfigured()) {
    // Send notification
}
```

### Supported Parameters

When using `send()` method, you can pass:

- `message` (required): The notification message text
- `title` (optional): Notification title (defaults to app name)
- `priority` (optional): -2 (lowest), -1, 0 (default), 1 (high), 2 (emergency)
- `url` (optional): Supplementary URL
- `url_title` (optional): Title for the URL
- `device` (optional): Send to specific device only
- `sound` (optional): Override default notification sound
- `html` (optional): Enable HTML formatting (boolean)

## Troubleshooting

### Notifications Not Received

1. **Check Configuration**
   ```bash
   php artisan pushover:test
   ```

2. **Verify Credentials**
   - Ensure `PUSHOVER_API_TOKEN` is your Application Token, not User Key
   - Ensure `PUSHOVER_USER_KEY` is correct
   - Check for extra spaces or quotes in `.env`

3. **Check Queue**
   - Notifications are queued, ensure queue worker is running:
   ```bash
   php artisan queue:work
   ```

4. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Common Errors

- **"user identifier is invalid"**: Check your `PUSHOVER_USER_KEY`
- **"token is invalid"**: Check your `PUSHOVER_API_TOKEN`
- **"Pushover not configured"**: Add credentials to `.env` file
- **Notifications delayed**: Start queue worker with `php artisan queue:work`

## Advanced Usage

### Sending Custom Notifications

You can use the PushoverService anywhere in your application:

```php
use Modules\Core\Services\PushoverService;

class YourController extends Controller
{
    public function notify(PushoverService $pushover)
    {
        $pushover->sendHighPriority(
            message: "New order received!",
            title: "Order Notification",
            url: route('admin.orders.show', $orderId)
        );
    }
}
```

### Creating New Event Listeners

To add notifications for other events:

1. Create a listener:
   ```bash
   php artisan make:listener SendSubscriptionNotification --event=SubscriptionCreated
   ```

2. Implement `ShouldQueue` and inject `PushoverService`

3. Register in `EventServiceProvider`

### Priority Levels

- **-2 (Silent)**: No notification, just badge update
- **-1 (Quiet)**: No sound or vibration
- **0 (Normal)**: Default notification
- **1 (High)**: Bypasses quiet hours, shows prominently
- **2 (Emergency)**: Requires user acknowledgment, repeats until acknowledged

## Security Considerations

- ✅ API credentials stored in `.env` (not in version control)
- ✅ Credentials configured via `config/services.php`
- ✅ HTTPS enforced for all API calls
- ✅ Graceful error handling (no sensitive data in errors)
- ✅ Logging for audit trail

## Resources

- [Pushover Official Documentation](https://pushover.net/api)
- [Pushover FAQ](https://pushover.net/faq)
- [Create Application Token](https://pushover.net/apps/build)
- [Pushover Sounds](https://pushover.net/api#sounds)
- [Emergency Priority](https://pushover.net/api#priority)

## Support

For issues specific to this integration:
- Check `storage/logs/laravel.log` for error messages
- Run `php artisan pushover:test` for diagnostics
- Ensure queue worker is running: `php artisan queue:work`

For Pushover-specific issues:
- Visit [Pushover Support](https://pushover.net/faq)
- Email: support@pushover.net
