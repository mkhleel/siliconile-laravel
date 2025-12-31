<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\Network\Services\MikrotikService;
use Modules\Network\Settings\RouterSettings;

/**
 * Widget displaying real-time online hotspot users count.
 */
final class OnlineUsersWidget extends BaseWidget
{
    /**
     * Polling interval for auto-refresh.
     */
    protected ?string $pollingInterval = '15s';

    /**
     * Load lazily to not block dashboard.
     */
    protected static bool $isLazy = true;

    /**
     * Widget column span.
     */
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $settings = app(RouterSettings::class);

        // If module is not enabled, show disabled state
        if (! $settings->enabled || ! $settings->isConfigured()) {
            return [
                Stat::make(__('Network Module'), __('Disabled'))
                    ->description(__('Configure router settings to enable'))
                    ->descriptionIcon(Heroicon::OutlinedWifi, IconPosition::Before)
                    ->color('gray'),
            ];
        }

        // Cache the online count for 10 seconds to reduce API calls
        $cacheKey = 'network:online_count';
        $onlineCount = Cache::remember($cacheKey, 10, function () {
            try {
                /** @var MikrotikService $service */
                $service = app(MikrotikService::class);

                return $service->getOnlineCount();
            } catch (\Exception $e) {
                return null;
            }
        });

        // Connection failed
        if ($onlineCount === null) {
            return [
                Stat::make(__('Router Status'), __('Disconnected'))
                    ->description(__('Cannot connect to Mikrotik router'))
                    ->descriptionIcon(Heroicon::OutlinedXCircle, IconPosition::Before)
                    ->color('danger'),
            ];
        }

        return [
            Stat::make(__('Online Users'), (string) $onlineCount)
                ->description(__('Currently connected to WiFi'))
                ->descriptionIcon(Heroicon::OutlinedWifi, IconPosition::Before)
                ->color($onlineCount > 0 ? 'success' : 'gray')
                ->chart($this->getOnlineHistory())
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make(__('Router'), $settings->ip_address)
                ->description(__('Connected'))
                ->descriptionIcon(Heroicon::OutlinedCheckCircle, IconPosition::Before)
                ->color('success'),
        ];
    }

    /**
     * Get online user count history for chart (last 10 readings).
     *
     * @return array<int>
     */
    protected function getOnlineHistory(): array
    {
        $cacheKey = 'network:online_history';
        $history = Cache::get($cacheKey, []);

        // Get current count
        try {
            /** @var MikrotikService $service */
            $service = app(MikrotikService::class);
            $currentCount = $service->getOnlineCount();

            // Add to history
            $history[] = $currentCount;

            // Keep only last 10 readings
            if (count($history) > 10) {
                $history = array_slice($history, -10);
            }

            // Cache for 1 minute
            Cache::put($cacheKey, $history, 60);

        } catch (\Exception $e) {
            // Keep existing history on error
        }

        return array_values($history);
    }
}
