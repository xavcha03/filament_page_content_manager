<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Mcp\Messages;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;

class RestorePageTool extends Tool
{
    protected string $name = 'restore_page';

    protected string $title = 'Restore Page';

    protected string $description = 'Restores a soft-deleted page from the trash and clears its deletion policy (redirect / 404 / 410).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID. One of page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug. One of page_id or page_slug required.')->nullable(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'page_id' => 'sometimes|string',
            'page_slug' => 'sometimes|string',
        ]);

        if (! isset($validated['page_id']) && ! isset($validated['page_slug'])) {
            return Response::error(Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        $page = $this->findPage($validated);

        if (! $page) {
            return Response::error('Page not found.');
        }

        if (! $page->trashed()) {
            return Response::error('Page is not in the trash.');
        }

        try {
            app(PageDeletionService::class)->restore($page);
            $page->refresh();

            return Response::json([
                'success' => true,
                'message' => 'Page restored successfully',
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'status' => $page->status,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to restore page: ' . $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function findPage(array $validated): ?Page
    {
        if (isset($validated['page_id'])) {
            $id = is_numeric($validated['page_id']) ? (int) $validated['page_id'] : null;

            return $id ? Page::withTrashed()->find($id) : null;
        }

        if (isset($validated['page_slug'])) {
            return Page::withTrashed()->where('slug', $validated['page_slug'])->first();
        }

        return null;
    }
}
