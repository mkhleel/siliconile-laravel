<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    public function getCpuUsage(): float
    {
        return sys_getloadavg()[0];
    }

    public function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');

        // Convert memory limit to bytes
        if (preg_match('/(\d+)([KMG])/', $memoryLimit, $matches)) {
            switch ($matches[2]) {
                case 'K':
                    $memoryLimit = $matches[1] * 1024;
                    break;
                case 'M':
                    $memoryLimit = $matches[1] * 1024 * 1024;
                    break;
                case 'G':
                    $memoryLimit = $matches[1] * 1024 * 1024 * 1024;
                    break;
            }
        } else {
            $memoryLimit = (int) $memoryLimit; // Assume it's already in bytes
        }

        return [
            'used' => $memoryUsage,
            'limit' => $memoryLimit,
            'percentage' => ($memoryUsage / $memoryLimit) * 100,
        ];
    }

    public function getDiskUsage(): array
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;

        return [
            'used' => $diskUsed,
            'total' => $diskTotal,
            'free' => $diskFree,
            'percentage' => ($diskUsed / $diskTotal) * 100,
        ];
    }

    public function getDatabaseSize(): int
    {
        $databaseName = config('database.connections.mysql.database');
        $result = DB::select('SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?', [$databaseName]);

        return (int) $result[0]->size;
    }

    public function getLaravelVersion(): string
    {
        return app()->version();
    }

    public function getPhpVersion(): string
    {
        return phpversion();
    }

    public function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            Log::error('Database connection error: '.$e->getMessage());

            return false;
        }
    }

    public function checkStorageLink(): bool
    {
        return file_exists(public_path('storage')) && is_link(public_path('storage'));
    }

    public function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 60);

            return Cache::has('health_check');
        } catch (Exception $e) {
            Log::error('Cache error: '.$e->getMessage());

            return false;
        }
    }
}
