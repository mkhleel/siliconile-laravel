<?php

namespace Modules\Core\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--compress : Compress the backup file}
                            {--cloud : Upload to cloud storage}
                            {--keep=7 : Number of backups to keep (default: 7)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup with optional compression and cloud upload';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗄️  Starting database backup...');

        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$connection}_{$timestamp}";

            // Create backups directory if it doesn't exist
            $backupPath = storage_path('app/backups');
            if (! file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $backupFile = null;

            switch ($config['driver']) {
                case 'sqlite':
                    $backupFile = $this->backupSqlite($config, $filename);
                    break;
                case 'mysql':
                case 'mariadb':
                    $backupFile = $this->backupMysql($config, $filename);
                    break;
                case 'pgsql':
                    $backupFile = $this->backupPostgres($config, $filename);
                    break;
                default:
                    $this->error("Unsupported database driver: {$config['driver']}");

                    return 1;
            }

            if ($backupFile) {
                $this->info('✅ Backup created: '.basename($backupFile));

                // Compress if requested
                if ($this->option('compress')) {
                    $backupFile = $this->compressBackup($backupFile);
                    $this->info('🗜️  Backup compressed: '.basename($backupFile));
                }

                // Upload to cloud if requested
                if ($this->option('cloud')) {
                    $this->uploadToCloud($backupFile);
                }

                // Clean old backups
                $this->cleanOldBackups($this->option('keep'));

                // Log successful backup
                Log::info('Database backup completed successfully', [
                    'file' => basename($backupFile),
                    'size' => $this->formatBytes(filesize($backupFile)),
                ]);

                $this->info('📊 Backup size: '.$this->formatBytes(filesize($backupFile)));
                $this->info('🎉 Database backup completed successfully!');

            } else {
                $this->error('❌ Failed to create backup');

                return 1;
            }

        } catch (Exception $e) {
            $this->error('❌ Backup failed: '.$e->getMessage());
            Log::error('Database backup failed', ['error' => $e->getMessage()]);

            return 1;
        }

        return 0;
    }

    /**
     * Backup SQLite database.
     */
    private function backupSqlite($config, $filename)
    {
        $sourceFile = $config['database'];
        $backupFile = storage_path("app/backups/{$filename}.sqlite");

        if (file_exists($sourceFile)) {
            copy($sourceFile, $backupFile);

            return $backupFile;
        }

        throw new Exception("SQLite database file not found: {$sourceFile}");
    }

    /**
     * Backup MySQL/MariaDB database.
     */
    private function backupMysql($config, $filename)
    {
        $backupFile = storage_path("app/backups/{$filename}.sql");

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupFile)) {
            return $backupFile;
        }

        throw new Exception("MySQL backup failed with return code: {$returnCode}");
    }

    /**
     * Backup PostgreSQL database.
     */
    private function backupPostgres($config, $filename)
    {
        $backupFile = storage_path("app/backups/{$filename}.sql");

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --dbname=%s > %s',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupFile)) {
            return $backupFile;
        }

        throw new Exception("PostgreSQL backup failed with return code: {$returnCode}");
    }

    /**
     * Compress backup file.
     */
    private function compressBackup($backupFile)
    {
        $compressedFile = $backupFile.'.gz';

        $command = sprintf('gzip -c %s > %s', escapeshellarg($backupFile), escapeshellarg($compressedFile));
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($compressedFile)) {
            unlink($backupFile); // Remove original file

            return $compressedFile;
        }

        return $backupFile; // Return original if compression failed
    }

    /**
     * Upload backup to cloud storage.
     */
    private function uploadToCloud($backupFile)
    {
        try {
            $filename = basename($backupFile);
            $content = file_get_contents($backupFile);

            Storage::disk('s3')->put("backups/{$filename}", $content);
            $this->info('☁️  Backup uploaded to cloud storage');

        } catch (Exception $e) {
            $this->warn('⚠️  Cloud upload failed: '.$e->getMessage());
        }
    }

    /**
     * Clean old backup files.
     */
    private function cleanOldBackups($keep)
    {
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath.'/backup_*');

        // Sort files by modification time (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Remove old files
        $removed = 0;
        for ($i = $keep; $i < count($files); $i++) {
            if (unlink($files[$i])) {
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("🧹 Cleaned {$removed} old backup(s)");
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
