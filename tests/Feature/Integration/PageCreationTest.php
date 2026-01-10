<?php

namespace Xavcha\PageContentManager\Tests\Feature\Integration;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class PageCreationTest extends TestCase
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

    public function test_can_create_page_with_hero_block(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'hero-page',
            'title' => 'Hero Page',
            'content' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'titre' => 'Hero Title',
                            'description' => 'Hero Description',
                            'variant' => 'hero',
                        ],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'hero-page',
            'title' => 'Hero Page',
        ]);

        $this->assertCount(1, $page->getSections());
        $this->assertEquals('hero', $page->getSections()[0]['type']);
    }

    public function test_can_create_page_with_multiple_blocks(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'multiple-blocks',
            'title' => 'Multiple Blocks',
            'content' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'data' => ['titre' => 'Hero'],
                    ],
                    [
                        'type' => 'text',
                        'data' => ['titre' => 'Text'],
                    ],
                    [
                        'type' => 'image',
                        'data' => ['alt' => 'Image'],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $this->assertCount(3, $page->getSections());
    }

    public function test_can_retrieve_page_via_api(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'api-test',
            'title' => 'API Test',
            'content' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'data' => [
                            'titre' => 'API Title',
                            'content' => 'API Content',
                        ],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/api-test');

        $response->assertOk();
        $response->assertJson([
            'id' => $page->id,
            'title' => 'API Test',
            'slug' => 'api-test',
        ]);
    }

    public function test_page_sections_are_transformed_correctly(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'transform-test',
            'title' => 'Transform Test',
            'content' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'data' => [
                            'titre' => 'Transformed Title',
                            'content' => 'Transformed Content',
                        ],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/transform-test');

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('sections', $data);
        $this->assertCount(1, $data['sections']);
        $this->assertEquals('text', $data['sections'][0]['type']);
        $this->assertArrayHasKey('data', $data['sections'][0]);
        $this->assertEquals('Transformed Title', $data['sections'][0]['data']['titre']);
    }

    public function test_can_update_page_content(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'update-test',
            'title' => 'Update Test',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);

        $page->update([
            'content' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'data' => ['titre' => 'Updated'],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);

        $this->assertCount(1, $page->fresh()->getSections());
    }

    public function test_page_normalization_on_save(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'normalize-test',
            'title' => 'Normalize Test',
            'content' => null,
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertIsArray($page->content);
        $this->assertArrayHasKey('sections', $page->content);
        $this->assertArrayHasKey('metadata', $page->content);
        $this->assertEquals(1, $page->content['metadata']['schema_version']);
    }
}

