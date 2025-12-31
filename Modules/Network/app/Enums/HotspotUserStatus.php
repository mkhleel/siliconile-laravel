<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

/**
 * Status of a Hotspot user in Mikrotik.
 */
enum HotspotUserStatus: string
{
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case PENDING_SYNC = 'pending_sync';
    case SYNC_FAILED = 'sync_failed';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ENABLED => 'Enabled',
            self::DISABLED => 'Disabled',
            self::PENDING_SYNC => 'Pending Sync',
            self::SYNC_FAILED => 'Sync Failed',
        };
    }

    /**
     * Get color for Filament badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::ENABLED => 'success',
            self::DISABLED => 'danger',
            self::PENDING_SYNC => 'warning',
            self::SYNC_FAILED => 'danger',
        };
    }

    /**
     * Get icon for Filament badges.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ENABLED => 'heroicon-o-check-circle',
            self::DISABLED => 'heroicon-o-x-circle',
            self::PENDING_SYNC => 'heroicon-o-arrow-path',
            self::SYNC_FAILED => 'heroicon-o-exclamation-triangle',
        };
    }
}
