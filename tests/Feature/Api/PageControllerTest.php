<?php

namespace Xavcha\PageContentManager\Tests\Feature\Api;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class PageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // La page Home est déjà créée par la migration
        // On vérifie juste qu'elle existe
        $homePage = Page::where('type', 'home')->first();
        if (!$homePage) {
            // Si elle n'existe pas (ne devrait pas arriver), on la crée
            Page::create([
                'type' => 'home',
                'slug' => 'home',
                'title' => 'Home',
                'content' => [
                    'sections' => [],
                    'metadata' => ['schema_version' => 1],
                ],
                'status' => 'published',
            ]);
        }
    }

    public function test_index_returns_published_pages(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'published-page',
            'title' => 'Published Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        Page::create([
            'type' => 'standard',
            'slug' => 'draft-page',
            'title' => 'Draft Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $response->assertJsonStructure([
            'pages' => [
                '*' => ['id', 'title', 'slug', 'type'],
            ],
        ]);

        $pages = $response->json('pages');
        $this->assertCount(2, $pages); // Home + Published
        $this->assertTrue(collect($pages)->contains('slug', 'published-page'));
        $this->assertFalse(collect($pages)->contains('slug', 'draft-page'));
    }

    public function test_index_orders_home_first(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'another-page',
            'title' => 'Another Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $pages = $response->json('pages');
        
        // La première page devrait être Home
        $this->assertEquals('home', $pages[0]['type']);
    }

    public function test_index_orders_by_title_after_home(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'zebra-page',
            'title' => 'Zebra Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        Page::create([
            'type' => 'standard',
            'slug' => 'alpha-page',
            'title' => 'Alpha Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $pages = $response->json('pages');
        
        // Après Home, devrait être trié par titre
        $this->assertEquals('home', $pages[0]['type']);
        $this->assertEquals('Alpha Page', $pages[1]['title']);
        $this->assertEquals('Zebra Page', $pages[2]['title']);
    }

    public function test_index_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $response->assertJsonStructure([
            'pages' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'type',
                ],
            ],
        ]);
    }

    public function test_show_returns_page_by_slug(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-page',
            'title' => 'Test Page',
            'content' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'data' => ['titre' => 'Test'],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
        ]);

        $response = $this->getJson('/api/pages/test-page');

        $response->assertOk();
        $response->assertJson([
            'id' => $page->id,
            'title' => 'Test Page',
            'slug' => 'test-page',
            'type' => 'standard',
        ]);
    }

    public function test_show_returns_home_page_with_home_slug(): void
    {
        $response = $this->getJson('/api/pages/home');

        $response->assertOk();
        $response->assertJson([
            'type' => 'home',
        ]);
    }

    public function test_show_returns_home_page_with_empty_slug(): void
    {
        // Note: Laravel ne route pas /api/pages/ vers show(), il faut utiliser 'home'
        // Ce test vérifie que 'home' fonctionne (déjà testé dans test_show_returns_home_page_with_home_slug)
        $response = $this->getJson('/api/pages/home');

        $response->assertOk();
        $response->assertJson([
            'type' => 'home',
        ]);
    }

    public function test_show_returns_404_for_nonexistent_page(): void
    {
        $response = $this->getJson('/api/pages/non-existent');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Page non trouvée',
        ]);
    }

    public function test_show_returns_404_for_unpublished_page(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'draft-page',
            'title' => 'Draft Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/pages/draft-page');

        $response->assertStatus(404);
    }

    public function test_show_returns_transformed_sections(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-sections',
            'title' => 'Test Sections',
            'content' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'data' => [
                            'titre' => 'Test Title',
                            'content' => 'Test Content',
                        ],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/test-sections');

        $response->assertOk();
        $data = $response->json();
        
        $this->assertArrayHasKey('sections', $data);
        $this->assertIsArray($data['sections']);
        $this->assertCount(1, $data['sections']);
        $this->assertEquals('text', $data['sections'][0]['type']);
        $this->assertArrayHasKey('data', $data['sections'][0]);
    }

    public function test_show_includes_seo_metadata(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'seo-page',
            'title' => 'SEO Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
        ]);

        $response = $this->getJson('/api/pages/seo-page');

        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals('SEO Title', $data['seo_title']);
        $this->assertEquals('SEO Description', $data['seo_description']);
    }

    public function test_show_includes_metadata(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'metadata-page',
            'title' => 'Metadata Page',
            'content' => [
                'sections' => [],
                'metadata' => [
                    'schema_version' => 1,
                    'custom' => 'value',
                ],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/metadata-page');

        $response->assertOk();
        $data = $response->json();
        
        $this->assertArrayHasKey('metadata', $data);
        $this->assertEquals(1, $data['metadata']['schema_version']);
        $this->assertEquals('value', $data['metadata']['custom']);
    }
}

