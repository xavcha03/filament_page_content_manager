<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class PageModelTest extends TestCase
{
    public function test_can_create_page(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-page',
            'title' => 'Test Page',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'test-page',
            'title' => 'Test Page',
        ]);

        $this->assertEquals('test-page', $page->slug);
        $this->assertEquals('Test Page', $page->title);
    }

    public function test_cannot_create_multiple_home_pages(): void
    {
        // Créer la première page Home (déjà créée par la migration)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Une page Home existe déjà');

        Page::create([
            'type' => 'home',
            'slug' => 'home-2',
            'title' => 'Home 2',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);
    }

    public function test_content_is_normalized(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'normalized',
            'title' => 'Normalized',
            'content' => [], // Contenu vide
            'status' => 'draft',
        ]);

        $this->assertIsArray($page->content);
        $this->assertArrayHasKey('sections', $page->content);
        $this->assertArrayHasKey('metadata', $page->content);
        $this->assertEquals(1, $page->content['metadata']['schema_version']);
    }

    public function test_published_scope(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'published',
            'title' => 'Published',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Page::create([
            'type' => 'standard',
            'slug' => 'draft',
            'title' => 'Draft',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $published = Page::published()->get();

        $this->assertCount(2, $published); // Home + Published
        $this->assertTrue($published->contains('slug', 'published'));
        $this->assertFalse($published->contains('slug', 'draft'));
    }

    public function test_cannot_delete_home_page(): void
    {
        $homePage = Page::where('type', 'home')->first();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La page Home ne peut pas être supprimée.');

        $homePage->delete();
    }

    public function test_cannot_change_page_type(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-type',
            'title' => 'Test Type',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le type de la page ne peut pas être modifié après création.');

        $page->type = 'home';
        $page->save();
    }

    public function test_is_home_returns_true_for_home_type(): void
    {
        $homePage = Page::where('type', 'home')->first();

        $this->assertTrue($homePage->isHome());
    }

    public function test_is_standard_returns_true_for_standard_type(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'standard-test',
            'title' => 'Standard Test',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->assertTrue($page->isStandard());
        $this->assertFalse($page->isHome());
    }

    public function test_is_published_checks_status_and_date(): void
    {
        $publishedPage = Page::create([
            'type' => 'standard',
            'slug' => 'published-test',
            'title' => 'Published Test',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $futurePage = Page::create([
            'type' => 'standard',
            'slug' => 'future-test',
            'title' => 'Future Test',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $draftPage = Page::create([
            'type' => 'standard',
            'slug' => 'draft-test',
            'title' => 'Draft Test',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->assertTrue($publishedPage->isPublished());
        $this->assertFalse($futurePage->isPublished());
        $this->assertFalse($draftPage->isPublished());
    }

    public function test_is_scheduled_returns_true_for_scheduled_status(): void
    {
        $scheduledPage = Page::create([
            'type' => 'standard',
            'slug' => 'scheduled-test',
            'title' => 'Scheduled Test',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->assertTrue($scheduledPage->isScheduled());
    }

    public function test_published_scope_excludes_draft(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'draft-exclude',
            'title' => 'Draft Exclude',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $published = Page::published()->get();

        $this->assertFalse($published->contains('slug', 'draft-exclude'));
    }

    public function test_published_scope_excludes_future_dates(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'future-exclude',
            'title' => 'Future Exclude',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $published = Page::published()->get();

        $this->assertFalse($published->contains('slug', 'future-exclude'));
    }

    public function test_published_scope_includes_null_published_at(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'null-date',
            'title' => 'Null Date',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'published_at' => null,
        ]);

        $published = Page::published()->get();

        $this->assertTrue($published->contains('slug', 'null-date'));
    }
}



