<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Models\Page;

class CreatePageWithBlocksTool extends Tool
{
    protected string $name = 'create_page_with_blocks';

    protected string $title = 'Create Page With Blocks';

    protected string $description = 'Creates a new page and immediately sets its content blocks. Use this when generating a full page in one call. Blocks must match schemas; for images use MediaFile IDs (image_id).';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Title (Titre) - The page title as shown in Filament "Général" tab. Required.'),
            'slug' => $schema->string()->description('Slug - The URL path for the page (e.g., "about", "contact"). Must be unique. Required. Cannot be changed after creation.'),
            'type' => $schema->string()->enum(['standard'])->description('Type - Page type. Only "standard" is allowed via MCP.'),
            'seo_title' => $schema->string()->description('SEO Title (Titre SEO) - Optional meta title from "SEO" tab.')->nullable(),
            'seo_description' => $schema->string()->description('SEO Description (Description SEO) - Optional meta description from "SEO" tab.')->nullable(),
            'status' => $schema->string()->enum(['draft', 'published'])->description('Status (Statut) - Page status. Default: draft.')->nullable(),
            'blocks' => $schema->array()->description('Array of blocks to set as page content. Each block must have "type" and "data". Use list_blocks and get_block_schema.'),
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
            'blocks' => 'required|array|min:1',
            'blocks.*.type' => 'required|string',
            'blocks.*.data' => 'required|array',
        ]);

        if (Page::where('slug', $validated['slug'])->exists()) {
            return Response::error('A page with this slug already exists.');
        }

        try {
            $registry = app(BlockRegistry::class);
            $sections = [];

            foreach ($validated['blocks'] as $block) {
                $blockType = $block['type'];
                $blockData = $block['data'];

                if (!$registry->has($blockType)) {
                    return Response::error("Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.");
                }

                $sections[] = [
                    'type' => $blockType,
                    'data' => $blockData,
                ];
            }

            $page = Page::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'type' => $validated['type'] ?? 'standard',
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'content' => [
                    'sections' => $sections,
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
                'total_sections' => count($sections),
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to create page: ' . $e->getMessage());
        }
    }
}
