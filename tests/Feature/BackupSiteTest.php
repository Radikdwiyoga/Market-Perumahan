<?php

namespace Tests\Feature;

use App\Support\BackupManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupSiteTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/backups');
        config([
            'marketplace.backup.directory' => $this->root,
            'marketplace.backup.retention_days' => 7,
        ]);
        File::deleteDirectory($this->root);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        Storage::disk('public')->deleteDirectory('products');
        parent::tearDown();
    }

    public function test_command_runs_and_backs_up_file_uploads(): void
    {
        Storage::disk('public')->put('products/foto.jpg', 'data-foto');
        $this->assertFileExists(storage_path('app/public/products/foto.jpg'));

        $this->artisan('site:backup')->assertSuccessful();

        $snapshots = File::directories($this->root);
        $this->assertCount(1, $snapshots);
        $this->assertFileExists($snapshots[0].'/uploads/products/foto.jpg');
        $this->assertSame('data-foto', File::get($snapshots[0].'/uploads/products/foto.jpg'));
    }

    public function test_command_prunes_backups_older_than_retention(): void
    {
        $old = $this->root.'/2020-01-01_000000';
        $recent = $this->root.'/2026-09-27_000000';
        File::makeDirectory($old, 0755, true);
        File::makeDirectory($recent, 0755, true);
        file_put_contents($old.'/database.sqlite', 'lama');
        file_put_contents($recent.'/database.sqlite', 'baru');
        touch($old, now()->subDays(30)->getTimestamp());
        touch($recent, now()->getTimestamp());

        $this->artisan('site:backup')->assertSuccessful();

        $this->assertDirectoryDoesNotExist($old);
        $this->assertDirectoryExists($recent);
    }

    public function test_sqlite_database_dump_copies_the_database_file(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'db').'.sqlite';
        file_put_contents($source, 'isi-database');

        $report = BackupManager::run($this->root.'/manual', 7, $source);

        $this->assertSame('sqlite-copy', $report['database']);
        $this->assertFileExists($this->root.'/manual/'.$report['stamp'].'/database/database.sqlite');
        $this->assertSame('isi-database', File::get($this->root.'/manual/'.$report['stamp'].'/database/database.sqlite'));
    }

    public function test_site_backup_is_scheduled_daily(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);
        $event = collect($schedule->events())->first(
            fn ($event): bool => str_contains($event->command, 'site:backup')
        );

        $this->assertNotNull($event);
        $this->assertSame('30 2 * * *', $event->expression);
    }

    public function test_up_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
