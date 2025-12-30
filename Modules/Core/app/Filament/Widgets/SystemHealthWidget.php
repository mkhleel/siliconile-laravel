<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Core\Services\SystemHealthService;

final class SystemHealthWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $service = app(SystemHealthService::class);

        $cpuUsage = $service->getCpuUsage();
        $memory = $service->getMemoryUsage();
        $disk = $service->getDiskUsage();
        $dbSize = $service->getDatabaseSize();
        $laravelVersion = $service->getLaravelVersion();
        $phpVersion = $service->getPhpVersion();
        $dbConnected = $service->checkDatabaseConnection();
        $storageLinked = $service->checkStorageLink();
        $cacheWorking = $service->checkCache();

        return [
            Stat::make('CPU Usage', number_format($cpuUsage, 1).'%')
                ->description(__('Current CPU load average'))
                ->descriptionIcon('heroicon-o-cpu-chip', IconPosition::Before)
                ->chart([/**/])
                ->color($cpuUsage > 80 ? 'danger' : ($cpuUsage > 60 ? 'warning' : 'success')),

            Stat::make('Memory Usage', number_format($memory['percentage'], 1).'%')
                ->description(number_format($memory['used'] / 1024 / 1024, 1).' MB / '.number_format($memory['limit'] / 1024 / 1024, 1).' MB')
                ->descriptionIcon('heroicon-o-circle-stack', IconPosition::Before)
                ->color($memory['percentage'] > 80 ? 'danger' : ($memory['percentage'] > 60 ? 'warning' : 'success')),

            Stat::make('Disk Usage', number_format($disk['percentage'], 1).'%')
                ->description(number_format($disk['used'] / 1024 / 1024 / 1024, 1).' GB / '.number_format($disk['total'] / 1024 / 1024 / 1024, 1).' GB')
                ->descriptionIcon('heroicon-o-server', IconPosition::Before)
                ->color($disk['percentage'] > 85 ? 'danger' : ($disk['percentage'] > 70 ? 'warning' : 'success')),

            Stat::make('Database Size', number_format($dbSize / 1024 / 1024, 1).' MB')
                ->description(__('Total database size'))
                ->descriptionIcon('heroicon-o-circle-stack', IconPosition::Before)
                ->color('info'),

            Stat::make('Laravel', $laravelVersion)
                ->description(__('Framework version'))
                ->descriptionIcon('heroicon-o-code-bracket', IconPosition::Before)
                ->color('info'),

            Stat::make('PHP', $phpVersion)
                ->description(__('Runtime version'))
                ->descriptionIcon('heroicon-o-code-bracket-square', IconPosition::Before)
                ->color('info'),

            Stat::make('Database', $dbConnected ? 'Connected' : 'Disconnected')
                ->description($dbConnected ? 'Database is reachable' : 'Connection failed')
                ->descriptionIcon($dbConnected ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle', IconPosition::Before)
                ->color($dbConnected ? 'success' : 'danger'),

            Stat::make('Storage', $storageLinked ? 'Linked' : 'Missing')
                ->description($storageLinked ? 'Storage link exists' : 'Storage link missing')
                ->descriptionIcon($storageLinked ? 'heroicon-o-folder' : 'heroicon-o-folder-minus', IconPosition::Before)
                ->color($storageLinked ? 'success' : 'warning'),

            Stat::make('Cache', $cacheWorking ? 'Working' : 'Error')
                ->description($cacheWorking ? 'Cache is operational' : 'Cache system error')
                ->descriptionIcon($cacheWorking ? 'heroicon-o-bolt' : 'heroicon-o-exclamation-triangle', IconPosition::Before)
                ->color($cacheWorking ? 'success' : 'danger'),
        ];
    }
}
