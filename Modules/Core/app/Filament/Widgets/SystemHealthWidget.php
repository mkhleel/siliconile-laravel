<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
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
                ->descriptionIcon(Heroicon::OutlinedCpuChip, IconPosition::Before)
                ->chart([/**/])
                ->color($cpuUsage > 80 ? 'danger' : ($cpuUsage > 60 ? 'warning' : 'success')),

            Stat::make('Memory Usage', number_format($memory['percentage'], 1).'%')
                ->description(number_format($memory['used'] / 1024 / 1024, 1).' MB / '.number_format($memory['limit'] / 1024 / 1024, 1).' MB')
                ->descriptionIcon(Heroicon::OutlinedCircleStack, IconPosition::Before)
                ->color($memory['percentage'] > 80 ? 'danger' : ($memory['percentage'] > 60 ? 'warning' : 'success')),

            Stat::make('Disk Usage', number_format($disk['percentage'], 1).'%')
                ->description(number_format($disk['used'] / 1024 / 1024 / 1024, 1).' GB / '.number_format($disk['total'] / 1024 / 1024 / 1024, 1).' GB')
                ->descriptionIcon(Heroicon::OutlinedServer, IconPosition::Before)
                ->color($disk['percentage'] > 85 ? 'danger' : ($disk['percentage'] > 70 ? 'warning' : 'success')),

            Stat::make('Database Size', number_format($dbSize / 1024 / 1024, 1).' MB')
                ->description(__('Total database size'))
                ->descriptionIcon(Heroicon::OutlinedCircleStack, IconPosition::Before)
                ->color('info'),

            Stat::make('Laravel', $laravelVersion)
                ->description(__('Framework version'))
                ->descriptionIcon(Heroicon::OutlinedCodeBracket, IconPosition::Before)
                ->color('info'),

            Stat::make('PHP', $phpVersion)
                ->description(__('Runtime version'))
                ->descriptionIcon(Heroicon::OutlinedCodeBracketSquare, IconPosition::Before)
                ->color('info'),

            Stat::make('Database', $dbConnected ? 'Connected' : 'Disconnected')
                ->description($dbConnected ? 'Database is reachable' : 'Connection failed')
                ->descriptionIcon($dbConnected ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle, IconPosition::Before)
                ->color($dbConnected ? 'success' : 'danger'),

            Stat::make('Storage', $storageLinked ? 'Linked' : 'Missing')
                ->description($storageLinked ? 'Storage link exists' : 'Storage link missing')
                ->descriptionIcon($storageLinked ? Heroicon::OutlinedFolder : Heroicon::OutlinedFolderMinus, IconPosition::Before)
                ->color($storageLinked ? 'success' : 'warning'),

            Stat::make('Cache', $cacheWorking ? 'Working' : 'Error')
                ->description($cacheWorking ? 'Cache is operational' : 'Cache system error')
                ->descriptionIcon($cacheWorking ? Heroicon::OutlinedBolt : Heroicon::OutlinedExclamationTriangle, IconPosition::Before)
                ->color($cacheWorking ? 'success' : 'danger'),
        ];
    }
}
