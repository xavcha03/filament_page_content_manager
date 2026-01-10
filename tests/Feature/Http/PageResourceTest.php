<?php

namespace Xavcha\PageContentManager\Tests\Feature\Http;

use Xavcha\PageContentManager\Http\Resources\PageResource;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;
use Illuminate\Http\Request;

class PageResourceTest extends TestCase
{
    public function test_to_array_includes_all_fields(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-page',
            'title' => 'Test Page',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
        ]);

        $resource = new PageResource($page);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('seo_title', $array);
        $this->assertArrayHasKey('seo_description', $array);
        $this->assertArrayHasKey('sections', $array);
        $this->assertArrayHasKey('metadata', $array);
    }

    public function test_to_array_transforms_sections(): void
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

        $resource = new PageResource($page);
        $array = $resource->toArray(new Request());

        $this->assertIsArray($array['sections']);
        $this->assertCount(1, $array['sections']);
        $this->assertEquals('text', $array['sections'][0]['type']);
        $this->assertArrayHasKey('data', $array['sections'][0]);
    }

    public function test_to_array_includes_metadata(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-metadata',
            'title' => 'Test Metadata',
            'content' => [
                'sections' => [],
                'metadata' => [
                    'schema_version' => 1,
                    'custom' => 'value',
                ],
            ],
            'status' => 'published',
        ]);

        $resource = new PageResource($page);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals(1, $array['metadata']['schema_version']);
        $this->assertEquals('value', $array['metadata']['custom']);
    }

    public function test_to_array_handles_empty_sections(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'empty-sections',
            'title' => 'Empty Sections',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $resource = new PageResource($page);
        $array = $resource->toArray(new Request());

        $this->assertIsArray($array['sections']);
        $this->assertEmpty($array['sections']);
    }

    public function test_to_array_handles_null_content(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'null-content',
            'title' => 'Null Content',
            'content' => null,
            'status' => 'published',
        ]);
        $page->save();

        $resource = new PageResource($page);
        $array = $resource->toArray(new Request());

        // Le contenu null devrait être normalisé en structure vide
        $this->assertIsArray($array['sections']);
        $this->assertIsArray($array['metadata']);
    }
}

