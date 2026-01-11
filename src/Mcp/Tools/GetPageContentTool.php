<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class GetPageContentTool extends Tool
{
    protected string $name = 'get_page_content';

    protected string $title = 'Get Page Content';

    protected string $description = 'Retrieves the complete content of a page including all blocks. Useful to understand the structure before modifying.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the page (as string or integer)'),
            'slug' => $schema->string()->description('The slug of the page (alternative to ID)'),
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

        try {
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];

            return Response::json([
                'success' => true,
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'type' => $page->type,
                    'status' => $page->status,
                    'seo_title' => $page->seo_title,
                    'seo_description' => $page->seo_description,
                ],
                'content' => [
                    'sections' => $sections,
                    'total_sections' => count($sections),
                    'metadata' => $content['metadata'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to get page content: ' . $e->getMessage());
        }
    }
}
