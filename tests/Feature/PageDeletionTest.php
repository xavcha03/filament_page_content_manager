<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;
use Xavcha\PageContentManager\Services\PageUrlResolver;
use Xavcha\PageContentManager\Tests\TestCase;

class PageDeletionTest extends TestCase
{
    public function test_soft_deleted_page_with_gone_returns_410_via_api(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'removed-page',
            'title' => 'Removed',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        app(PageDeletionService::class)->softDelete($page, DeletedPageResponseType::Gone);

        $response = $this->getJson('/api/pages/removed-page');

        $response->assertStatus(410);
        $response->assertJson([
            'resolution' => PageUrlResolver::RESOLUTION_GONE,
            'message' => 'Page supprimée',
        ]);
    }

    public function test_soft_deleted_page_with_404_policy_returns_404_via_api(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'hidden-page',
            'title' => 'Hidden',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        app(PageDeletionService::class)->softDelete($page, DeletedPageResponseType::NotFound);

        $this->getJson('/api/pages/hidden-page')
            ->assertStatus(404)
            ->assertJson(['resolution' => PageUrlResolver::RESOLUTION_NOT_FOUND]);
    }

    public function test_soft_deleted_page_with_redirect_to_page_returns_301(): void
    {
        $target = Page::create([
            'type' => 'standard',
            'slug' => 'target-page',
            'title' => 'Target',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        $page = Page::create([
            'type' => 'standard',
            'slug' => 'old-page',
            'title' => 'Old',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        app(PageDeletionService::class)->softDelete(
            $page,
            DeletedPageResponseType::RedirectToPage,
            $target->id,
        );

        $response = $this->getJson('/api/pages/old-page');

        $response->assertStatus(301);
        $response->assertHeader('Location', '/target-page');
        $response->assertJson([
            'resolution' => PageUrlResolver::RESOLUTION_REDIRECT,
            'redirect' => [
                'type' => 'page',
                'slug' => 'target-page',
                'location' => '/target-page',
            ],
        ]);
    }

    public function test_restore_clears_deletion_policy_and_page_is_available_again(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'restored-page',
            'title' => 'Restored',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        $service = app(PageDeletionService::class);
        $service->softDelete($page, DeletedPageResponseType::Gone);

        $trashed = Page::withTrashed()->where('slug', 'restored-page')->first();
        $this->assertNotNull($trashed);
        $this->assertTrue($trashed->trashed());

        $service->restore($trashed);

        $this->getJson('/api/pages/restored-page')
            ->assertOk()
            ->assertJsonPath('slug', 'restored-page');

        $this->assertNull($trashed->fresh()->deleted_response_type);
    }

    public function test_force_delete_removes_page_permanently(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'force-deleted',
            'title' => 'Force Deleted',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        $service = app(PageDeletionService::class);
        $service->softDelete($page, DeletedPageResponseType::Gone);
        $service->forceDelete(Page::withTrashed()->find($page->id));

        $this->assertDatabaseMissing('pages', ['slug' => 'force-deleted', 'deleted_at' => null]);
        $this->getJson('/api/pages/force-deleted')->assertStatus(404);
    }

    public function test_index_excludes_soft_deleted_pages(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'trashed-list',
            'title' => 'Trashed List',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'published',
        ]);

        app(PageDeletionService::class)->softDelete($page, DeletedPageResponseType::Gone);

        $response = $this->getJson('/api/pages');
        $slugs = collect($response->json('pages'))->pluck('slug');

        $this->assertFalse($slugs->contains('trashed-list'));
    }
}
