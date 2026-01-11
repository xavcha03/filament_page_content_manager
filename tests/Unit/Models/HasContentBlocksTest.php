<?php

namespace Xavcha\PageContentManager\Tests\Unit\Models;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class HasContentBlocksTest extends TestCase
{
    public function test_normalize_content_handles_null(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'null-content',
            'title' => 'Null Content',
            'content' => null,
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertIsArray($page->content);
        $this->assertArrayHasKey('sections', $page->content);
        $this->assertArrayHasKey('metadata', $page->content);
    }

    public function test_normalize_content_handles_empty_array(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'empty-content',
            'title' => 'Empty Content',
            'content' => [],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertIsArray($page->content);
        $this->assertArrayHasKey('sections', $page->content);
        $this->assertArrayHasKey('metadata', $page->content);
    }

    public function test_normalize_content_handles_missing_sections(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'missing-sections',
            'title' => 'Missing Sections',
            'content' => [
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertArrayHasKey('sections', $page->content);
        $this->assertIsArray($page->content['sections']);
    }

    public function test_normalize_content_handles_missing_metadata(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'missing-metadata',
            'title' => 'Missing Metadata',
            'content' => [
                'sections' => [],
            ],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertArrayHasKey('metadata', $page->content);
        $this->assertIsArray($page->content['metadata']);
    }

    public function test_normalize_content_sets_schema_version(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'schema-version',
            'title' => 'Schema Version',
            'content' => [
                'sections' => [],
                'metadata' => [],
            ],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertEquals(1, $page->content['metadata']['schema_version']);
    }

    public function test_normalize_content_preserves_existing_sections(): void
    {
        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test'],
            ],
        ];

        $page = new Page([
            'type' => 'standard',
            'slug' => 'preserve-sections',
            'title' => 'Preserve Sections',
            'content' => [
                'sections' => $sections,
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertEquals($sections, $page->content['sections']);
    }

    public function test_normalize_content_preserves_existing_metadata(): void
    {
        $metadata = [
            'schema_version' => 2,
            'custom' => 'value',
        ];

        $page = new Page([
            'type' => 'standard',
            'slug' => 'preserve-metadata',
            'title' => 'Preserve Metadata',
            'content' => [
                'sections' => [],
                'metadata' => $metadata,
            ],
            'status' => 'draft',
        ]);
        $page->save();

        $this->assertEquals(2, $page->content['metadata']['schema_version']);
        $this->assertEquals('value', $page->content['metadata']['custom']);
    }

    public function test_get_sections_returns_sections(): void
    {
        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test'],
            ],
        ];

        $page = Page::create([
            'type' => 'standard',
            'slug' => 'get-sections',
            'title' => 'Get Sections',
            'content' => [
                'sections' => $sections,
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);

        $this->assertEquals($sections, $page->getSections());
    }

    public function test_get_sections_returns_empty_for_null_content(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'null-sections',
            'title' => 'Null Sections',
            'content' => null,
            'status' => 'draft',
        ]);

        $this->assertEquals([], $page->getSections());
    }

    public function test_get_metadata_returns_metadata(): void
    {
        $metadata = [
            'schema_version' => 1,
            'custom' => 'value',
        ];

        $page = Page::create([
            'type' => 'standard',
            'slug' => 'get-metadata',
            'title' => 'Get Metadata',
            'content' => [
                'sections' => [],
                'metadata' => $metadata,
            ],
            'status' => 'draft',
        ]);

        $this->assertEquals($metadata, $page->getMetadata());
    }

    public function test_get_metadata_returns_empty_for_null_content(): void
    {
        $page = new Page([
            'type' => 'standard',
            'slug' => 'null-metadata',
            'title' => 'Null Metadata',
            'content' => null,
            'status' => 'draft',
        ]);

        $this->assertEquals([], $page->getMetadata());
    }
}



