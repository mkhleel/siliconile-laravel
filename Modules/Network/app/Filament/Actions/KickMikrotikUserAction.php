<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\Membership\Models\Member;
use Modules\Network\Services\MikrotikService;
use Modules\Network\Settings\RouterSettings;

/**
 * Filament Action to kick a member's active WiFi session.
 *
 * Terminates active sessions and optionally disables the user.
 */
class KickMikrotikUserAction
{
    public static function make(?string $name = 'kick_mikrotik'): Action
    {
        return Action::make($name)
            ->label(__('Kick WiFi Session'))
            ->icon(Heroicon::OutlinedBolt)
            ->color('danger')
            ->visible(fn () => self::isModuleEnabled())
            ->requiresConfirmation()
            ->modalHeading(__('Kick WiFi Session'))
            ->modalDescription(fn (Member $record) => __('This will terminate all active WiFi sessions for member :name.', [
                'name' => $record->user?->name ?? $record->member_code,
            ]))
            ->action(function (Member $record) {
                try {
                    /** @var MikrotikService $service */
                    $service = app(MikrotikService::class);

                    $success = $service->kickUser($record);

                    if ($success) {
                        Notification::make()
                            ->title(__('Session Terminated'))
                            ->body(__('All active WiFi sessions have been terminated.'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('No Active Sessions'))
                            ->body(__('No active sessions found for this member.'))
                            ->info()
                            ->send();
                    }

                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('Kick Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function isModuleEnabled(): bool
    {
        try {
            $settings = app(RouterSettings::class);

            return $settings->enabled && $settings->isConfigured();
        } catch (\Exception $e) {
            return false;
        }
    }
}
