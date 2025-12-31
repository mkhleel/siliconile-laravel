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
 * Filament Action to sync a member to Mikrotik.
 *
 * Creates or updates the hotspot user on the router.
 */
class SyncMikrotikUserAction
{
    public static function make(?string $name = 'sync_mikrotik'): Action
    {
        return Action::make($name)
            ->label(__('Sync to WiFi'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('info')
            ->visible(fn () => self::isModuleEnabled())
            ->requiresConfirmation()
            ->modalHeading(__('Sync to Mikrotik'))
            ->modalDescription(fn (Member $record) => __('This will sync member :name to the Mikrotik router and enable their WiFi access.', [
                'name' => $record->user?->name ?? $record->member_code,
            ]))
            ->action(function (Member $record) {
                try {
                    /** @var MikrotikService $service */
                    $service = app(MikrotikService::class);

                    $result = $service->syncUser($record);

                    Notification::make()
                        ->title(__('Member Synced'))
                        ->body(__('WiFi access enabled. Username: :username', ['username' => $result->username]))
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('Sync Failed'))
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
