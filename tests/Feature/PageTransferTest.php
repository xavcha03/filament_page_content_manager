<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;
use Xavcha\PageContentManager\Tests\Helpers\TestHelpers;
use Xavcha\PageContentManager\Tests\TestCase;

class PageTransferTest extends TestCase
{
    public function test_round_trip_export_and_import_recreates_deleted_page(): void
    {
        $source = TestHelpers::createPage([
            'slug' => 'tarifs-export',
            'title' => 'Tarifs export',
            'status' => 'published',
            'published_at' => now(),
            'seo_title' => 'SEO Tarifs',
            'seo_description' => 'Description SEO',
            'content' => [
                'sections' => [
                    TestHelpers::createSection('text', [
                        'titre' => 'Nos tarifs',
                        'content' => 'Contenu tarifs',
                    ]),
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);

        $transferService = app(PageTransferService::class);
        $archivePath = $transferService->exportToFile([$source]);

        $this->assertFileExists($archivePath);

        $source->delete();

        $result = $transferService->importFromPath($archivePath, [
            'on_conflict' => 'replace',
            'import_as_draft' => true,
        ]);

        $this->assertCount(1, $result['pages']);

        $imported = $result['pages'][0];
        $this->assertSame('tarifs-export', $imported->slug);
        $this->assertSame('Tarifs export', $imported->title);
        $this->assertSame('draft', $imported->status);
        $this->assertNull($imported->published_at);
        $this->assertSame('Nos tarifs', $imported->getSections()[0]['data']['titre']);
    }

    public function test_import_replaces_existing_page_by_slug(): void
    {
        $existing = TestHelpers::createPage([
            'slug' => 'a-propos',
            'title' => 'Ancien titre',
            'content' => [
                'sections' => [
                    TestHelpers::createSection('text', ['titre' => 'Ancien contenu']),
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);

        $existing->fill([
            'title' => 'Nouveau titre',
            'content' => [
                'sections' => [
                    TestHelpers::createSection('text', ['titre' => 'Nouveau contenu']),
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);
        $existing->save();

        $archivePath = app(PageTransferService::class)->exportToFile([$existing]);

        $existing->fill([
            'title' => 'Ancien titre',
            'content' => [
                'sections' => [
                    TestHelpers::createSection('text', ['titre' => 'Ancien contenu']),
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);
        $existing->save();

        $result = app(PageTransferService::class)->importFromPath($archivePath, [
            'on_conflict' => 'replace',
            'import_as_draft' => false,
        ]);

        $this->assertSame($existing->id, $result['pages'][0]->id);
        $this->assertSame('Nouveau titre', $result['pages'][0]->title);
        $this->assertSame('Nouveau contenu', $result['pages'][0]->getSections()[0]['data']['titre']);
        $this->assertSame(1, Page::query()->where('slug', 'a-propos')->count());
    }

    public function test_import_replaces_home_page(): void
    {
        $home = Page::query()->where('type', 'home')->firstOrFail();
        $originalTitle = $home->title;

        $home->fill([
            'title' => 'Home exportée',
            'content' => [
                'sections' => [
                    TestHelpers::createSection('text', ['titre' => 'Bienvenue exportée']),
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);
        $home->save();

        $archivePath = app(PageTransferService::class)->exportToFile([$home]);

        $home->fill([
            'title' => $originalTitle,
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
        ]);
        $home->save();

        $result = app(PageTransferService::class)->importFromPath($archivePath, [
            'on_conflict' => 'replace',
            'import_as_draft' => true,
        ]);

        $this->assertSame($home->id, $result['pages'][0]->id);
        $this->assertSame('home', $result['pages'][0]->slug);
        $this->assertSame('Home exportée', $result['pages'][0]->title);
        $this->assertSame('Bienvenue exportée', $result['pages'][0]->getSections()[0]['data']['titre']);
    }
}
