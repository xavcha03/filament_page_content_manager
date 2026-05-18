<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PagePreviewService;
use Xavcha\PageContentManager\Tests\TestCase;

class PagePreviewTest extends TestCase
{
    public function test_preview_token_allows_draft_page_via_api(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'draft-preview',
            'title' => 'Draft Preview',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $token = app(PagePreviewService::class)->createToken($page);

        $response = $this->getJson('/api/pages/draft-preview?preview_token=' . urlencode($token));

        $response->assertOk();
        $response->assertHeader('X-Page-Preview', '1');
        $response->assertJson([
            'slug' => 'draft-preview',
            'preview' => true,
            'page_status' => 'draft',
        ]);
    }

    public function test_draft_page_without_token_returns_404(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'draft-no-token',
            'title' => 'Draft',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->getJson('/api/pages/draft-no-token')->assertStatus(404);
    }

    public function test_invalid_preview_token_returns_403(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'draft-bad-token',
            'title' => 'Draft',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->getJson('/api/pages/draft-bad-token?preview_token=invalid.token')
            ->assertStatus(403);
    }

    public function test_preview_token_rejects_slug_mismatch(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'page-a',
            'title' => 'Page A',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $token = app(PagePreviewService::class)->createToken($page);

        $this->getJson('/api/pages/other-slug?preview_token=' . urlencode($token))
            ->assertStatus(403);
    }

    public function test_preview_builds_frontend_url(): void
    {
        config([
            'app.frontend_url' => 'https://example.com',
            'page-content-manager.preview.path' => '/preview',
        ]);

        $page = Page::create([
            'type' => 'standard',
            'slug' => 'my-page',
            'title' => 'My Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $url = app(PagePreviewService::class)->buildFrontendPreviewUrl($page);

        $this->assertStringStartsWith('https://example.com/preview/my-page?preview_token=', $url);
    }
}
