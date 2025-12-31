<?php

declare(strict_types=1);

namespace Modules\Network\Settings;

use Illuminate\Support\Facades\Crypt;
use Spatie\LaravelSettings\Settings;

/**
 * Router settings using spatie/laravel-settings.
 *
 * Stores Mikrotik router connection credentials securely.
 */
class RouterSettings extends Settings
{
    /**
     * Router IP address or hostname.
     */
    public string $ip_address = '';

    /**
     * API port (default 8728, or 8729 for SSL).
     */
    public int $port = 8728;

    /**
     * Admin username for API access.
     */
    public string $admin_username = 'admin';

    /**
     * Encrypted admin password.
     *
     * Note: Use getDecryptedPassword() to retrieve plaintext.
     */
    public string $admin_password = '';

    /**
     * Default hotspot profile to assign to new users.
     */
    public string $hotspot_profile = 'default';

    /**
     * Hotspot server name (if multiple servers exist).
     */
    public string $hotspot_server = 'hotspot1';

    /**
     * Connection timeout in seconds.
     */
    public int $connection_timeout = 10;

    /**
     * Enable SSL for API connection (port 8729).
     */
    public bool $use_ssl = false;

    /**
     * Whether the Network module is enabled.
     */
    public bool $enabled = false;

    /**
     * Username format pattern.
     * Supported: {member_code}, {phone}, {email}
     */
    public string $username_format = '{phone}';

    /**
     * Auto-generate password on sync if not provided.
     */
    public bool $auto_generate_password = true;

    /**
     * Default password length for auto-generated passwords.
     */
    public int $password_length = 8;

    /**
     * Get the settings group name.
     */
    public static function group(): string
    {
        return 'network_router';
    }

    /**
     * Get decrypted password.
     */
    public function getDecryptedPassword(): string
    {
        if (empty($this->admin_password)) {
            return '';
        }

        try {
            return Crypt::decryptString($this->admin_password);
        } catch (\Exception $e) {
            // If decryption fails, it might be stored in plaintext (legacy)
            return $this->admin_password;
        }
    }

    /**
     * Set and encrypt the password.
     */
    public function setEncryptedPassword(string $plainPassword): void
    {
        $this->admin_password = Crypt::encryptString($plainPassword);
    }

    /**
     * Check if router settings are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->ip_address)
            && ! empty($this->admin_username)
            && ! empty($this->admin_password);
    }

    /**
     * Get the API port based on SSL setting.
     */
    public function getEffectivePort(): int
    {
        return $this->use_ssl ? 8729 : $this->port;
    }
}
