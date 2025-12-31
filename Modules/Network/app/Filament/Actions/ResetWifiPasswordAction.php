<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Modules\Membership\Models\Member;
use Modules\Network\Exceptions\MikrotikOperationException;
use Modules\Network\Services\MikrotikService;
use Modules\Network\Settings\RouterSettings;

/**
 * Filament Action to reset a member's WiFi password.
 *
 * Can be added to Member Resource pages for immediate password reset.
 */
class ResetWifiPasswordAction
{
    public static function make(?string $name = 'reset_wifi_password'): Action
    {
        return Action::make($name)
            ->label(__('Reset WiFi Password'))
            ->icon('heroicon-o-key')
            ->color('warning')
            ->visible(fn () => self::isModuleEnabled())
            ->form([
                Toggle::make('auto_generate')
                    ->label(__('Auto-generate password'))
                    ->default(true)
                    ->live(),

                TextInput::make('new_password')
                    ->label(__('New Password'))
                    ->password()
                    ->revealable()
                    ->minLength(6)
                    ->maxLength(32)
                    ->visible(fn ($get) => ! $get('auto_generate'))
                    ->requiredIf('auto_generate', false),
            ])
            ->requiresConfirmation()
            ->modalHeading(__('Reset WiFi Password'))
            ->modalDescription(fn (Member $record) => __('This will generate a new WiFi password for member: :name', [
                'name' => $record->user?->name ?? $record->member_code,
            ]))
            ->action(function (array $data, Member $record) {
                $password = $data['auto_generate'] ? null : $data['new_password'];

                try {
                    /** @var MikrotikService $service */
                    $service = app(MikrotikService::class);

                    $result = $service->resetPassword($record, $password);

                    // Show the new password to admin
                    Notification::make()
                        ->title(__('Password Reset Successfully'))
                        ->body(__('New WiFi credentials:')."\n"
                            .__('Username: :username', ['username' => $result->username])."\n"
                            .__('Password: :password', ['password' => $result->password]))
                        ->success()
                        ->persistent()
                        ->send();

                } catch (MikrotikOperationException $e) {
                    Notification::make()
                        ->title(__('Password Reset Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('Error'))
                        ->body(__('An unexpected error occurred. Please check the logs.'))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Check if the Network module is enabled.
     */
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
