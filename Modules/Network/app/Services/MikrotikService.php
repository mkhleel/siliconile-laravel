<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Membership\Models\Member;
use Modules\Network\DTOs\HotspotUserDTO;
use Modules\Network\Enums\HotspotUserStatus;
use Modules\Network\Enums\SyncAction;
use Modules\Network\Exceptions\MikrotikConnectionException;
use Modules\Network\Exceptions\MikrotikOperationException;
use Modules\Network\Models\NetworkSyncLog;
use Modules\Network\Settings\RouterSettings;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Exceptions\ClientException;
use RouterOS\Exceptions\ConfigException;
use RouterOS\Query;

/**
 * Mikrotik RouterOS API Service.
 *
 * Handles all interactions with Mikrotik router for hotspot user management.
 * All operations are designed to be fail-safe and should not crash the application.
 */
class MikrotikService
{
    private ?Client $client = null;

    private RouterSettings $settings;

    public function __construct(RouterSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Establish connection to the Mikrotik router.
     *
     * @throws MikrotikConnectionException
     */
    public function connect(): self
    {
        if (! $this->settings->isConfigured()) {
            throw new MikrotikConnectionException('Router settings are not configured.');
        }

        if (! $this->settings->enabled) {
            throw new MikrotikConnectionException('Network module is disabled.');
        }

        try {
            $config = new Config([
                'host' => $this->settings->ip_address,
                'port' => $this->settings->getEffectivePort(),
                'user' => $this->settings->admin_username,
                'pass' => $this->settings->getDecryptedPassword(),
                'timeout' => $this->settings->connection_timeout,
                'ssl' => $this->settings->use_ssl,
            ]);

            $this->client = new Client($config);

            Log::info('MikrotikService: Connected to router', [
                'host' => $this->settings->ip_address,
                'port' => $this->settings->getEffectivePort(),
            ]);

        } catch (ClientException|ConfigException $e) {
            Log::error('MikrotikService: Connection failed', [
                'host' => $this->settings->ip_address,
                'error' => $e->getMessage(),
            ]);

            throw new MikrotikConnectionException(
                "Failed to connect to router: {$e->getMessage()}",
                previous: $e
            );
        }

        return $this;
    }

    /**
     * Test connection to the router.
     *
     * @return array{success: bool, message: string, identity?: string}
     */
    public function testConnection(): array
    {
        try {
            $this->connect();

            // Get router identity as proof of connection
            $query = new Query('/system/identity/print');
            $response = $this->client->query($query)->read();

            $identity = $response[0]['name'] ?? 'Unknown';

            return [
                'success' => true,
                'message' => "Successfully connected to router: {$identity}",
                'identity' => $identity,
            ];

        } catch (MikrotikConnectionException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Unexpected error: {$e->getMessage()}",
            ];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Sync a member to the Mikrotik hotspot.
     *
     * Creates or updates the user based on existence.
     *
     * @param  string|null  $password  If null, auto-generates based on settings
     */
    public function syncUser(Member $member, ?string $password = null): HotspotUserDTO
    {
        $username = $this->formatUsername($member);
        $password = $password ?? $this->generatePassword();

        try {
            $this->ensureConnected();

            $existingUser = $this->findHotspotUser($username);

            if ($existingUser !== null) {
                // Update existing user
                return $this->updateHotspotUser($member, $username, $password, $existingUser['.id']);
            }

            // Create new user
            return $this->createHotspotUser($member, $username, $password);

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::UPDATED, $e);

            throw new MikrotikOperationException(
                "Failed to sync user {$username}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Create a new hotspot user.
     */
    protected function createHotspotUser(Member $member, string $username, string $password): HotspotUserDTO
    {
        $query = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $username)
            ->equal('password', $password)
            ->equal('profile', $this->settings->hotspot_profile)
            ->equal('server', $this->settings->hotspot_server)
            ->equal('comment', "Member ID: {$member->id} | Code: {$member->member_code}");

        $this->client->query($query)->read();

        $this->logSync($member, SyncAction::CREATED, [
            'username' => $username,
            'profile' => $this->settings->hotspot_profile,
        ]);

        Log::info('MikrotikService: User created', [
            'member_id' => $member->id,
            'username' => $username,
        ]);

        return new HotspotUserDTO(
            username: $username,
            password: $password,
            profile: $this->settings->hotspot_profile,
            server: $this->settings->hotspot_server,
            status: HotspotUserStatus::ENABLED,
            memberId: $member->id,
        );
    }

    /**
     * Update an existing hotspot user.
     */
    protected function updateHotspotUser(
        Member $member,
        string $username,
        string $password,
        string $mikrotikId
    ): HotspotUserDTO {
        $query = (new Query('/ip/hotspot/user/set'))
            ->equal('.id', $mikrotikId)
            ->equal('password', $password)
            ->equal('profile', $this->settings->hotspot_profile)
            ->equal('disabled', 'no')
            ->equal('comment', "Member ID: {$member->id} | Code: {$member->member_code}");

        $this->client->query($query)->read();

        $this->logSync($member, SyncAction::UPDATED, [
            'username' => $username,
            'profile' => $this->settings->hotspot_profile,
        ]);

        Log::info('MikrotikService: User updated', [
            'member_id' => $member->id,
            'username' => $username,
        ]);

        return new HotspotUserDTO(
            username: $username,
            password: $password,
            profile: $this->settings->hotspot_profile,
            server: $this->settings->hotspot_server,
            status: HotspotUserStatus::ENABLED,
            memberId: $member->id,
        );
    }

    /**
     * Disable a hotspot user (sets disabled=yes).
     */
    public function disableUser(Member $member): bool
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            $existingUser = $this->findHotspotUser($username);

            if ($existingUser === null) {
                Log::warning('MikrotikService: Cannot disable non-existent user', [
                    'member_id' => $member->id,
                    'username' => $username,
                ]);

                return false;
            }

            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $existingUser['.id'])
                ->equal('disabled', 'yes');

            $this->client->query($query)->read();

            $this->logSync($member, SyncAction::DISABLED, ['username' => $username]);

            Log::info('MikrotikService: User disabled', [
                'member_id' => $member->id,
                'username' => $username,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::DISABLED, $e);

            Log::error('MikrotikService: Failed to disable user', [
                'member_id' => $member->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enable a previously disabled hotspot user.
     */
    public function enableUser(Member $member): bool
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            $existingUser = $this->findHotspotUser($username);

            if ($existingUser === null) {
                Log::warning('MikrotikService: Cannot enable non-existent user', [
                    'member_id' => $member->id,
                    'username' => $username,
                ]);

                return false;
            }

            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $existingUser['.id'])
                ->equal('disabled', 'no');

            $this->client->query($query)->read();

            $this->logSync($member, SyncAction::ENABLED, ['username' => $username]);

            Log::info('MikrotikService: User enabled', [
                'member_id' => $member->id,
                'username' => $username,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::ENABLED, $e);

            Log::error('MikrotikService: Failed to enable user', [
                'member_id' => $member->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Kick (terminate active session) for a member.
     *
     * Finds and removes active sessions from /ip/hotspot/active.
     */
    public function kickUser(Member $member): bool
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            // Find active sessions for this user
            $query = (new Query('/ip/hotspot/active/print'))
                ->where('user', $username);

            $activeSessions = $this->client->query($query)->read();

            if (empty($activeSessions)) {
                Log::info('MikrotikService: No active sessions to kick', [
                    'member_id' => $member->id,
                    'username' => $username,
                ]);

                return true;
            }

            // Remove all active sessions
            foreach ($activeSessions as $session) {
                $removeQuery = (new Query('/ip/hotspot/active/remove'))
                    ->equal('.id', $session['.id']);

                $this->client->query($removeQuery)->read();
            }

            $this->logSync($member, SyncAction::KICKED, [
                'username' => $username,
                'sessions_terminated' => count($activeSessions),
            ]);

            Log::info('MikrotikService: User sessions terminated', [
                'member_id' => $member->id,
                'username' => $username,
                'sessions_count' => count($activeSessions),
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::KICKED, $e);

            Log::error('MikrotikService: Failed to kick user', [
                'member_id' => $member->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disable user and terminate all active sessions.
     */
    public function disableAndKickUser(Member $member): bool
    {
        // First kick active sessions
        $this->kickUser($member);

        // Then disable the user account
        return $this->disableUser($member);
    }

    /**
     * Reset password for a member and sync to router.
     */
    public function resetPassword(Member $member, ?string $newPassword = null): HotspotUserDTO
    {
        $username = $this->formatUsername($member);
        $password = $newPassword ?? $this->generatePassword();

        try {
            $this->ensureConnected();

            $existingUser = $this->findHotspotUser($username);

            if ($existingUser === null) {
                // Create the user if doesn't exist
                return $this->createHotspotUser($member, $username, $password);
            }

            // Update password only
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $existingUser['.id'])
                ->equal('password', $password);

            $this->client->query($query)->read();

            $this->logSync($member, SyncAction::PASSWORD_RESET, ['username' => $username]);

            Log::info('MikrotikService: Password reset', [
                'member_id' => $member->id,
                'username' => $username,
            ]);

            return new HotspotUserDTO(
                username: $username,
                password: $password,
                profile: $existingUser['profile'] ?? $this->settings->hotspot_profile,
                server: $existingUser['server'] ?? $this->settings->hotspot_server,
                status: ($existingUser['disabled'] ?? 'false') === 'true'
                    ? HotspotUserStatus::DISABLED
                    : HotspotUserStatus::ENABLED,
                memberId: $member->id,
            );

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::PASSWORD_RESET, $e);

            throw new MikrotikOperationException(
                "Failed to reset password for {$username}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Delete a hotspot user from the router.
     */
    public function deleteUser(Member $member): bool
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            $existingUser = $this->findHotspotUser($username);

            if ($existingUser === null) {
                return true; // User doesn't exist, consider it deleted
            }

            // First kick any active sessions
            $this->kickUser($member);

            // Then delete the user
            $query = (new Query('/ip/hotspot/user/remove'))
                ->equal('.id', $existingUser['.id']);

            $this->client->query($query)->read();

            $this->logSync($member, SyncAction::DELETED, ['username' => $username]);

            Log::info('MikrotikService: User deleted', [
                'member_id' => $member->id,
                'username' => $username,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logSyncError($member, SyncAction::DELETED, $e);

            Log::error('MikrotikService: Failed to delete user', [
                'member_id' => $member->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get count of currently online hotspot users.
     */
    public function getOnlineCount(): int
    {
        try {
            $this->ensureConnected();

            $query = new Query('/ip/hotspot/active/print');
            $activeUsers = $this->client->query($query)->read();

            return count($activeUsers);

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get online count', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get list of currently active hotspot users.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveUsers(): array
    {
        try {
            $this->ensureConnected();

            $query = new Query('/ip/hotspot/active/print');
            $activeUsers = $this->client->query($query)->read();

            return $activeUsers;

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get active users', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all hotspot users from router.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllHotspotUsers(): array
    {
        try {
            $this->ensureConnected();

            $query = new Query('/ip/hotspot/user/print');
            $users = $this->client->query($query)->read();

            return $users;

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get hotspot users', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get available hotspot profiles.
     *
     * @return array<int, string>
     */
    public function getHotspotProfiles(): array
    {
        try {
            $this->ensureConnected();

            $query = new Query('/ip/hotspot/user/profile/print');
            $profiles = $this->client->query($query)->read();

            return array_map(fn ($p) => $p['name'], $profiles);

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get hotspot profiles', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get available hotspot servers.
     *
     * @return array<int, string>
     */
    public function getHotspotServers(): array
    {
        try {
            $this->ensureConnected();

            $query = new Query('/ip/hotspot/print');
            $servers = $this->client->query($query)->read();

            return array_map(fn ($s) => $s['name'], $servers);

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get hotspot servers', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check if a specific member is currently online.
     */
    public function isUserOnline(Member $member): bool
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            $query = (new Query('/ip/hotspot/active/print'))
                ->where('user', $username);

            $sessions = $this->client->query($query)->read();

            return ! empty($sessions);

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to check online status', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user info from router.
     */
    public function getUserInfo(Member $member): ?array
    {
        $username = $this->formatUsername($member);

        try {
            $this->ensureConnected();

            return $this->findHotspotUser($username);

        } catch (\Exception $e) {
            Log::error('MikrotikService: Failed to get user info', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a hotspot user by username.
     */
    protected function findHotspotUser(string $username): ?array
    {
        $query = (new Query('/ip/hotspot/user/print'))
            ->where('name', $username);

        $results = $this->client->query($query)->read();

        return $results[0] ?? null;
    }

    /**
     * Format username based on settings pattern.
     */
    public function formatUsername(Member $member): string
    {
        $format = $this->settings->username_format;

        // Load user relationship if needed
        $member->loadMissing('user');

        $replacements = [
            '{member_code}' => $member->member_code ?? '',
            '{phone}' => $member->user?->phone ?? '',
            '{email}' => $member->user?->email ?? '',
            '{member_id}' => (string) $member->id,
        ];

        $username = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $format
        );

        // Clean up the username (remove special chars except underscore and dash)
        return preg_replace('/[^a-zA-Z0-9_\-@.]/', '', $username) ?: "member_{$member->id}";
    }

    /**
     * Generate a random password.
     */
    public function generatePassword(): string
    {
        $length = max(6, $this->settings->password_length);

        // Generate alphanumeric password for easy typing
        return Str::random($length);
    }

    /**
     * Ensure we have an active connection.
     *
     * @throws MikrotikConnectionException
     */
    protected function ensureConnected(): void
    {
        if ($this->client === null) {
            $this->connect();
        }
    }

    /**
     * Disconnect from the router.
     */
    public function disconnect(): void
    {
        $this->client = null;
    }

    /**
     * Log a successful sync operation.
     */
    protected function logSync(Member $member, SyncAction $action, array $metadata = []): void
    {
        NetworkSyncLog::create([
            'member_id' => $member->id,
            'action' => $action,
            'status' => 'success',
            'metadata' => $metadata,
            'router_ip' => $this->settings->ip_address,
        ]);
    }

    /**
     * Log a failed sync operation.
     */
    protected function logSyncError(Member $member, SyncAction $action, \Throwable $e): void
    {
        NetworkSyncLog::create([
            'member_id' => $member->id,
            'action' => $action,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'metadata' => [
                'exception' => get_class($e),
                'trace' => Str::limit($e->getTraceAsString(), 500),
            ],
            'router_ip' => $this->settings->ip_address,
        ]);
    }

    /**
     * Check if the module is properly configured and enabled.
     */
    public function isAvailable(): bool
    {
        return $this->settings->enabled && $this->settings->isConfigured();
    }

    /**
     * Get the current settings.
     */
    public function getSettings(): RouterSettings
    {
        return $this->settings;
    }
}
