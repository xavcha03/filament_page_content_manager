<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class CreatePageTool extends Tool
{
    protected string $name = 'create_page';

    protected string $title = 'Create Page';

    protected string $description = 'Creates a new page with the specified title, slug, and optional content. The page will be created as a draft by default.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The title of the page'),
            'slug' => $schema->string()->description('The URL slug for the page (must be unique)'),
            'type' => $schema->string()->enum(['standard'])->description('The type of page. Only "standard" is allowed'),
            'seo_title' => $schema->string()->description('Optional SEO title for the page')->nullable(),
            'seo_description' => $schema->string()->description('Optional SEO description for the page')->nullable(),
            'status' => $schema->string()->enum(['draft', 'published'])->description('The status of the page'),
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
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'type' => 'sometimes|string|in:standard',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,published',
        ]);

        // Vérifier l'unicité du slug manuellement
        if (Page::where('slug', $validated['slug'])->exists()) {
            return Response::error('A page with this slug already exists.');
        }

        try {
            $page = Page::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'type' => $validated['type'] ?? 'standard',
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'content' => [
                    'sections' => [],
                    'metadata' => [
                        'schema_version' => 1,
                    ],
                ],
            ]);

            return Response::json([
                'success' => true,
                'message' => 'Page created successfully',
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'type' => $page->type,
                    'status' => $page->status,
                    'seo_title' => $page->seo_title,
                    'seo_description' => $page->seo_description,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to create page: ' . $e->getMessage());
        }
    }
}

