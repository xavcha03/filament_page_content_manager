<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Mcp\Messages;
use Xavcha\PageContentManager\Models\Page;

class GetPageContentTool extends Tool
{
    protected string $name = 'get_page_content';

    protected string $title = 'Get Page Content';

    protected string $description = 'Use when you need to read a page\'s full content and blocks before editing or to verify after a change. Retrieves the complete content as in Filament: metadata (title, slug, type, status, SEO) and all content blocks from the "Contenu" tab with type and data. Identify the page with page_id or page_slug (e.g. "home" for home page).';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID (numeric). One of page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug (e.g. "home" for home page). One of page_id or page_slug required.')->nullable(),
            'id' => $schema->string()->description('Alias for page_id.')->nullable(),
            'slug' => $schema->string()->description('Alias for page_slug.')->nullable(),
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
        ]);

        // Normaliser page_id / page_slug vers id / slug
        if (isset($validated['page_id']) && ! isset($validated['id'])) {
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

        // Find the page by ID, slug, or home-page alias
        if (isset($validated['id'])) {
            $page = Page::find($validated['id']);
            if (! $page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (! empty($validated['slug']) || array_key_exists('slug', $validated)) {
            $slug = trim((string) ($validated['slug'] ?? ''));
            $page = $slug !== '' ? Page::where('slug', $slug)->first() : null;
            if (! $page && in_array(strtolower($slug), ['home', 'accueil', ''], true)) {
                $page = Page::where('type', 'home')->first();
            }
            if (! $page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error(Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        try {
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];

            return Response::json([
                'success' => true,
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => $page->type,
                'status' => $page->status,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'content' => [
                    'sections' => $sections,
                    'metadata' => $content['metadata'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to get page content: ' . $e->getMessage());
        }
    }
}
