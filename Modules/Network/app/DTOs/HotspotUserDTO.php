<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Modules\Network\Enums\HotspotUserStatus;

/**
 * Data Transfer Object for Hotspot User data.
 */
final readonly class HotspotUserDTO
{
    public function __construct(
        public string $username,
        public string $password,
        public string $profile,
        public string $server,
        public HotspotUserStatus $status,
        public ?int $memberId = null,
    ) {}

    /**
     * Create from array (e.g., Mikrotik API response).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['name'] ?? $data['username'],
            password: $data['password'] ?? '',
            profile: $data['profile'] ?? 'default',
            server: $data['server'] ?? 'hotspot1',
            status: isset($data['disabled']) && $data['disabled'] === 'true'
                ? HotspotUserStatus::DISABLED
                : HotspotUserStatus::ENABLED,
            memberId: isset($data['member_id']) ? (int) $data['member_id'] : null,
        );
    }

    /**
     * Convert to array for API response.
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
            'profile' => $this->profile,
            'server' => $this->server,
            'status' => $this->status->value,
            'member_id' => $this->memberId,
        ];
    }
}
