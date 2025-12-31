<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

/**
 * Sync action type for tracking operations.
 */
enum SyncAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case KICKED = 'kicked';
    case PASSWORD_RESET = 'password_reset';
    case DELETED = 'deleted';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'User Created',
            self::UPDATED => 'User Updated',
            self::ENABLED => 'User Enabled',
            self::DISABLED => 'User Disabled',
            self::KICKED => 'Session Terminated',
            self::PASSWORD_RESET => 'Password Reset',
            self::DELETED => 'User Deleted',
        };
    }

    /**
     * Get icon for Filament.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'heroicon-o-plus-circle',
            self::UPDATED => 'heroicon-o-pencil-square',
            self::ENABLED => 'heroicon-o-check-circle',
            self::DISABLED => 'heroicon-o-no-symbol',
            self::KICKED => 'heroicon-o-bolt',
            self::PASSWORD_RESET => 'heroicon-o-key',
            self::DELETED => 'heroicon-o-trash',
        };
    }
}
