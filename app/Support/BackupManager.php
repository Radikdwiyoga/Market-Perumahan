<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Backup harian database dan file upload dengan retensi terkonfigurasi (PRD §73–74).
 */
final class BackupManager
{
    /**
     * Buat satu snapshot (database + file upload) di dalam folder root,
     * lalu bersihkan snapshot yang lebih tua dari retention.
     *
     * @return array{
     *     stamp: string,
     *     database: string|null,
     *     uploads: string|null,
     *     pruned: list<string>,
     *     errors: list<string>,
     * }
     */
    public static function run(string $root, int $retentionDays, ?string $sqliteSource = null): array
    {
        $stamp = now()->format('Y-m-d_His');
        $directory = rtrim($root, '/\\').DIRECTORY_SEPARATOR.$stamp;
        File::ensureDirectoryExists($directory);

        $errors = [];

        try {
            $database = self::backupDatabase($directory.DIRECTORY_SEPARATOR.'database', $sqliteSource);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
            $database = null;
        }

        try {
            $uploads = self::backupUploads($directory.DIRECTORY_SEPARATOR.'uploads');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
            $uploads = null;
        }

        return [
            'stamp' => $stamp,
            'database' => $database,
            'uploads' => $uploads,
            'pruned' => self::prune($root, $retentionDays),
            'errors' => $errors,
        ];
    }

    private static function backupDatabase(string $directory, ?string $sqliteSource): ?string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => self::backupSqlite($directory, $sqliteSource),
            'mysql' => self::mysqldump($directory),
            'pgsql' => self::pgdump($directory),
            default => null,
        };
    }

    private static function backupSqlite(string $directory, ?string $sqliteSource): ?string
    {
        $source = $sqliteSource ?? database_path('database.sqlite');

        // Koneksi in-memory (testing) atau file belum ada: dilewati.
        if (! is_file($source)) {
            return null;
        }

        File::ensureDirectoryExists($directory);
        File::copy($source, $directory.DIRECTORY_SEPARATOR.'database.sqlite');

        return 'sqlite-copy';
    }

    private static function mysqldump(string $directory): string
    {
        $config = config('database.connections.mysql');
        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--skip-comments',
            '--single-transaction',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.($config['username'] ?? ''),
            $config['database'] ?? '',
        ];

        return self::runDump($directory, 'mysqldump', $command, 'MYSQL_PWD', (string) ($config['password'] ?? ''));
    }

    private static function pgdump(string $directory): string
    {
        $config = config('database.connections.pgsql');
        $command = [
            'pg_dump',
            '--no-owner',
            '--no-privileges',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 5432),
            '--username='.($config['username'] ?? ''),
            $config['database'] ?? '',
        ];

        return self::runDump($directory, 'pg_dump', $command, 'PGPASSWORD', (string) ($config['password'] ?? ''));
    }

    /**
     * @param  list<string>  $arguments
     */
    private static function runDump(string $directory, string $binary, array $arguments, string $passwordEnv, string $password): string
    {
        $target = $directory.DIRECTORY_SEPARATOR.'database.sql.gz';

        try {
            $process = new Process($arguments, null, [$passwordEnv => $password]);
            $process->run();
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                $binary.' tidak dapat dijalankan: '.$throwable->getMessage(),
                previous: $throwable,
            );
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException($binary.' gagal: '.trim($process->getErrorOutput()));
        }

        File::ensureDirectoryExists($directory);
        File::put($target, gzencode($process->getOutput()));

        return $binary;
    }

    private static function backupUploads(string $target): ?string
    {
        $source = storage_path('app/public');

        if (! is_dir($source)) {
            return null;
        }

        File::copyDirectory($source, $target);

        return 'uploads-copied';
    }

    /**
     * Hapus snapshot yang lebih tua dari retention.
     *
     * @return list<string>
     */
    private static function prune(string $root, int $retentionDays): array
    {
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $removed = [];

        foreach (File::directories($root) as $backupDirectory) {
            if (File::lastModified($backupDirectory) < $cutoff) {
                File::deleteDirectory($backupDirectory);
                $removed[] = basename($backupDirectory);
            }
        }

        return $removed;
    }
}
