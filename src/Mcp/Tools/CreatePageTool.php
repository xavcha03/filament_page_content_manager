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

    protected string $description = 'Creates a new page in the CMS. Corresponds to the "Général" tab in Filament: Title (Titre), Slug (URL), Type (Standard only), Status (Brouillon/Draft or Publié/Published). Optional SEO fields from the "SEO" tab: SEO Title and SEO Description. The page is created empty (no content blocks) - use add_blocks_to_page to add content.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Title (Titre) - The page title as shown in Filament "Général" tab. Required.'),
            'slug' => $schema->string()->description('Slug - The URL path for the page (e.g., "about", "contact"). Must be unique. Required. Cannot be changed after creation.'),
            'type' => $schema->string()->enum(['standard'])->description('Type - Page type. Only "standard" is allowed via MCP (home pages are created differently).'),
            'seo_title' => $schema->string()->description('SEO Title (Titre SEO) - Optional meta title for search engines from "SEO" tab.')->nullable(),
            'seo_description' => $schema->string()->description('SEO Description (Description SEO) - Optional meta description for search engines from "SEO" tab.')->nullable(),
            'status' => $schema->string()->enum(['draft', 'published'])->description('Status (Statut) - Page status: "draft" (Brouillon) or "published" (Publié). Default: draft.'),
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

