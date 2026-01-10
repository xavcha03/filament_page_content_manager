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
}



