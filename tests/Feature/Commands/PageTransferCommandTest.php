<?php

namespace Xavcha\PageContentManager\Tests\Feature\Commands;

use Xavcha\PageContentManager\Tests\Helpers\TestHelpers;
use Xavcha\PageContentManager\Tests\TestCase;

class PageTransferCommandTest extends TestCase
{
    public function test_export_command_creates_archive(): void
    {
        $page = TestHelpers::createPage([
            'slug' => 'export-cli',
            'title' => 'Export CLI',
        ]);

        $outputPath = sys_get_temp_dir() . '/export-cli-test.xavcha-page.zip';

        $this->artisan('page-content-manager:page:export', [
            'slug' => 'export-cli',
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        @unlink($outputPath);
    }

    public function test_import_command_dry_run_returns_preview(): void
    {
        $page = TestHelpers::createPage([
            'slug' => 'import-cli',
            'title' => 'Import CLI',
        ]);

        $outputPath = sys_get_temp_dir() . '/import-cli-test.xavcha-page.zip';

        $this->artisan('page-content-manager:page:export', [
            'slug' => 'import-cli',
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->artisan('page-content-manager:page:import', [
            'file' => $outputPath,
            '--dry-run' => true,
        ])->assertSuccessful();

        @unlink($outputPath);
    }
}
