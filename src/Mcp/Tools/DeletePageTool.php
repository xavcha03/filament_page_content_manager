<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Xavcha\PageContentManager\Models\Page;

#[IsDestructive]
class DeletePageTool extends Tool
{
    protected string $name = 'delete_page';

    protected string $title = 'Delete Page';

    protected string $description = 'Permanently deletes a page from the CMS. This action cannot be undone. The page will be removed from the pages list in Filament. Home page cannot be deleted. Requires confirmation: set "confirm" to true (boolean) to proceed with deletion.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the page to delete (as string or integer)')->nullable(),
            'slug' => $schema->string()->description('The slug of the page to delete (alternative to ID)')->nullable(),
            'confirm' => $schema->boolean()->description('Confirmation flag to prevent accidental deletion. Must be set to true (boolean) to proceed with deletion.')->nullable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
            'confirm' => 'sometimes',
        ]);

        // Alias page_id/page_slug vers id/slug pour compatibilité clients (LLM, etc.)
        if (isset($validated['page_id']) && empty($validated['id'])) {
            $validated['id'] = $validated['page_id'];
        }
        if (isset($validated['page_slug']) && empty($validated['slug'])) {
            $validated['slug'] = $validated['page_slug'];
        }

        // Convertir id en integer si c'est une string
        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;
            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
            }
        }

        // Convertir confirm en booléen si présent (peut être string "true"/"false" ou booléen)
        if (isset($validated['confirm'])) {
            if (is_string($validated['confirm'])) {
                $validated['confirm'] = filter_var($validated['confirm'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($validated['confirm'] === null) {
                    return Response::error('Invalid confirm value. Must be true or false (boolean).');
                }
            } elseif (!is_bool($validated['confirm'])) {
                return Response::error('Invalid confirm value. Must be true or false (boolean).');
            }
        }

        // Find the page by ID or slug
        if (isset($validated['id'])) {
            $page = Page::find($validated['id']);
            if (!$page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (isset($validated['slug'])) {
            $page = Page::where('slug', $validated['slug'])->first();
            if (!$page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error(\Xavcha\PageContentManager\Mcp\Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        // Prevent deleting home page
        if ($page->isHome()) {
            return Response::error('Home page cannot be deleted via MCP.');
        }

        // Confirmation optionnelle - si confirm est fourni, il doit être true
        if (isset($validated['confirm']) && $validated['confirm'] !== true) {
            return Response::error('Deletion not confirmed. Set "confirm" to true to delete the page.');
        }

        try {
            $pageData = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
            ];

            $page->delete();

            return Response::json([
                'success' => true,
                'message' => 'Page deleted successfully',
                'deleted_page' => $pageData,
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to delete page: ' . $e->getMessage());
        }
    }
}
