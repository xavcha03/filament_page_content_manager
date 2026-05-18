<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Mcp\Messages;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;

#[IsDestructive]
class DeletePageTool extends Tool
{
    protected string $name = 'delete_page';

    protected string $title = 'Delete Page';

    protected string $description = 'Soft-deletes a page and stores the URL policy (404, 410, 301 to page or URL). The page moves to trash and can be restored. Home page cannot be deleted. Optional confirm=true.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Alias for page_id.')->nullable(),
            'slug' => $schema->string()->description('Alias for page_slug.')->nullable(),
            'page_id' => $schema->string()->description('The ID of the page to delete.')->nullable(),
            'page_slug' => $schema->string()->description('The slug of the page to delete.')->nullable(),
            'deleted_response_type' => $schema->string()
                ->enum(['404', '410', '301_page', '301_url'])
                ->description('URL policy after deletion: 404, 410, 301_page, or 301_url.')
                ->nullable(),
            'redirect_target_page_id' => $schema->string()->description('Target page ID when deleted_response_type is 301_page.')->nullable(),
            'redirect_target_url' => $schema->string()->description('Target URL when deleted_response_type is 301_url.')->nullable(),
            'confirm' => $schema->boolean()->description('Optional confirmation flag. If provided, must be true.')->nullable(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'sometimes|string',
            'slug' => 'sometimes|string',
            'page_id' => 'sometimes|string',
            'page_slug' => 'sometimes|string',
            'deleted_response_type' => 'sometimes|string|in:404,410,301_page,301_url',
            'redirect_target_page_id' => 'sometimes|string',
            'redirect_target_url' => 'sometimes|string|max:2048',
            'confirm' => 'sometimes',
        ]);

        if (isset($validated['page_id']) && ! isset($validated['id'])) {
            $validated['id'] = $validated['page_id'];
        }

        if (empty($validated['slug']) && ! empty($validated['page_slug'])) {
            $validated['slug'] = $validated['page_slug'];
        }

        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;

            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
            }
        }

        if (isset($validated['confirm'])) {
            if (is_string($validated['confirm'])) {
                $validated['confirm'] = filter_var($validated['confirm'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($validated['confirm'] === null) {
                    return Response::error('Invalid confirm value. Must be true or false (boolean).');
                }
            } elseif (! is_bool($validated['confirm'])) {
                return Response::error('Invalid confirm value. Must be true or false (boolean).');
            }
        }

        if (isset($validated['confirm']) && $validated['confirm'] !== true) {
            return Response::error('Deletion not confirmed. Set "confirm" to true to delete the page.');
        }

        if (isset($validated['id'])) {
            $page = Page::find($validated['id']);
        } elseif (! empty($validated['slug'])) {
            $page = Page::where('slug', trim((string) $validated['slug']))->first();
        } else {
            return Response::error(Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        if (! $page) {
            return Response::error('Page not found.');
        }

        if ($page->isHome()) {
            return Response::error('Home page cannot be deleted via MCP.');
        }

        if ($page->trashed()) {
            return Response::error('Page is already in the trash.');
        }

        $responseType = DeletedPageResponseType::tryFrom(
            (string) ($validated['deleted_response_type'] ?? app(PageDeletionService::class)->defaultResponseType()->value)
        ) ?? app(PageDeletionService::class)->defaultResponseType();

        $redirectPageId = isset($validated['redirect_target_page_id']) && is_numeric($validated['redirect_target_page_id'])
            ? (int) $validated['redirect_target_page_id']
            : null;

        try {
            $pageData = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
            ];

            app(PageDeletionService::class)->softDelete(
                $page,
                $responseType,
                $redirectPageId,
                $validated['redirect_target_url'] ?? null,
            );

            return Response::json([
                'success' => true,
                'message' => 'Page moved to trash successfully',
                'deleted_page' => $pageData,
                'deleted_response_type' => $responseType->value,
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to delete page: ' . $e->getMessage());
        }
    }
}
