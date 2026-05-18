<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Xavcha\PageContentManager\Mcp\Messages;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;

#[IsDestructive]
class ForceDeletePageTool extends Tool
{
    protected string $name = 'force_delete_page';

    protected string $title = 'Force Delete Page';

    protected string $description = 'Permanently deletes a page from the database. Use only for trashed pages. Home page cannot be deleted. Requires confirm=true.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID. One of page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug. One of page_id or page_slug required.')->nullable(),
            'confirm' => $schema->boolean()->description('Must be true to proceed.')->nullable(),
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
            'confirm' => 'sometimes',
        ]);

        if (isset($validated['confirm'])) {
            $validated['confirm'] = is_bool($validated['confirm'])
                ? $validated['confirm']
                : filter_var($validated['confirm'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($validated['confirm']) && $validated['confirm'] !== true) {
            return Response::error('Deletion not confirmed. Set "confirm" to true.');
        }

        if (! isset($validated['page_id']) && ! isset($validated['page_slug'])) {
            return Response::error(Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        $page = $this->findPage($validated);

        if (! $page) {
            return Response::error('Page not found.');
        }

        if ($page->isHome()) {
            return Response::error('Home page cannot be deleted.');
        }

        try {
            $pageData = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
            ];

            app(PageDeletionService::class)->forceDelete($page);

            return Response::json([
                'success' => true,
                'message' => 'Page permanently deleted',
                'deleted_page' => $pageData,
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to force delete page: ' . $e->getMessage());
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
