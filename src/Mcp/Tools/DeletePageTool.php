<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class DeletePageTool extends Tool
{
    protected string $name = 'delete_page';

    protected string $title = 'Delete Page';

    protected string $description = 'Deletes a page completely. Useful for cleanup or removing obsolete pages. Home page cannot be deleted.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the page to delete (as string or integer)')->nullable(),
            'slug' => $schema->string()->description('The slug of the page to delete (alternative to ID)')->nullable(),
            'confirm' => $schema->boolean()->description('Confirmation flag to prevent accidental deletion')->nullable(),
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
            'confirm' => 'sometimes|boolean',
        ]);

        // Convertir id en integer si c'est une string
        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;
            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
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
            return Response::error('Either "id" or "slug" must be provided to identify the page.');
        }

        // Prevent deleting home page
        if ($page->isHome()) {
            return Response::error('Home page cannot be deleted via MCP.');
        }

        // Confirmation optionnelle
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
