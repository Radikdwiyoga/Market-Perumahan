<?php

namespace App\Console\Commands;

use App\Support\BackupManager;
use Illuminate\Console\Command;

class BackupSite extends Command
{
    protected $signature = 'site:backup';

    protected $description = 'Backup harian database dan file upload dengan retensi terkonfigurasi';

    public function handle(): int
    {
        $report = BackupManager::run(
            (string) config('marketplace.backup.directory'),
            (int) config('marketplace.backup.retention_days'),
        );

        $this->info("Backup selesai: {$report['stamp']}");
        $this->line('Database: '.($report['database'] ?? 'dilewati'));
        $this->line('Uploads : '.($report['uploads'] ?? 'dilewati'));

        if ($report['pruned'] !== []) {
            $this->info('Backup lama dibersihkan: '.implode(', ', $report['pruned']));
        }

        foreach ($report['errors'] as $error) {
            $this->error($error);
        }

        return $report['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
