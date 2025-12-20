<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--backup-id= : ID of the backup record}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupId = $this->option('backup-id');
        
        if ($backupId) {
            $backup = DatabaseBackup::find($backupId);
            if (!$backup) {
                $this->error('Backup record not found');
                return 1;
            }
        } else {
            // Create new backup record for automatic backup
            $backup = DatabaseBackup::create([
                'filename' => 'backup_' . DB::connection()->getDatabaseName() . '_' . date('Y-m-d_His') . '.sql',
                'file_path' => 'backups/backup_' . DB::connection()->getDatabaseName() . '_' . date('Y-m-d_His') . '.sql',
                'type' => 'automatic',
                'status' => 'pending',
                'backup_date' => now(),
            ]);
        }

        try {
            $this->info('Starting database backup...');

            $databaseName = DB::connection()->getDatabaseName();
            $username = DB::connection()->getConfig('username');
            $password = DB::connection()->getConfig('password');
            $host = DB::connection()->getConfig('host');
            $port = DB::connection()->getConfig('port') ?? 3306;

            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filePath = storage_path('app/' . $backup->file_path);

            // Check if mysqldump is available
            $mysqldumpPath = $this->findMysqldump();
            if (!$mysqldumpPath) {
                throw new \Exception('mysqldump command not found. Please ensure MySQL client tools are installed.');
            }

            // Build mysqldump command with password in config file for security
            // Create temporary config file
            $configFile = storage_path('app/backups/.my.cnf');
            $configContent = sprintf(
                "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
                $host,
                $port,
                $username,
                $password
            );
            file_put_contents($configFile, $configContent);
            chmod($configFile, 0600); // Secure permissions

            // Build command using config file
            $command = sprintf(
                '%s --defaults-file=%s %s > %s 2>&1',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($configFile),
                escapeshellarg($databaseName),
                escapeshellarg($filePath)
            );

            // Execute backup
            exec($command, $output, $returnVar);
            
            // Delete config file for security
            if (file_exists($configFile)) {
                unlink($configFile);
            }

            // Check if backup failed
            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output);
                // Check if file was created but contains error
                if (file_exists($filePath)) {
                    $fileContent = file_get_contents($filePath);
                    if (stripos($fileContent, 'error') !== false || stripos($fileContent, 'Access denied') !== false) {
                        unlink($filePath); // Delete error file
                        throw new \Exception('Backup failed: ' . $errorMessage);
                    }
                } else {
                    throw new \Exception('Backup failed: ' . $errorMessage);
                }
            }

            // Verify file exists and is not empty
            if (!file_exists($filePath)) {
                throw new \Exception('Backup file was not created');
            }

            // Check if file is empty or contains only error messages
            $fileContent = file_get_contents($filePath, false, null, 0, 100);
            if (empty($fileContent) || stripos($fileContent, 'error') !== false || stripos($fileContent, 'Access denied') !== false) {
                unlink($filePath);
                throw new \Exception('Backup file is empty or contains errors');
            }

            // Get file size
            $fileSize = filesize($filePath);
            
            if ($fileSize === 0) {
                unlink($filePath);
                throw new \Exception('Backup file is empty');
            }

            // Update backup record
            $backup->update([
                'status' => 'completed',
                'file_size' => $fileSize,
                'backup_date' => now(),
            ]);

            $this->info('Backup completed successfully!');
            $this->info('File: ' . $backup->filename);
            $this->info('Size: ' . $this->formatBytes($fileSize));

            return 0;
        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Find mysqldump executable path.
     */
    private function findMysqldump(): ?string
    {
        // Common paths for mysqldump
        $paths = [
            'mysqldump', // In PATH
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // XAMPP Windows
            'C:\\wamp\\bin\\mysql\\mysql8.0.xx\\bin\\mysqldump.exe', // WAMP Windows
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe', // MySQL Windows
            'C:\\Program Files\\MariaDB\\bin\\mysqldump.exe', // MariaDB Windows
            '/usr/bin/mysqldump', // Linux
            '/usr/local/bin/mysqldump', // macOS/Linux
            '/opt/mysql/bin/mysqldump', // Custom MySQL
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Try to find in PATH
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        exec("$which mysqldump 2>&1", $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }
}
