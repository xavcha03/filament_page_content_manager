<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Models\Page;

class UpdateBlockTool extends Tool
{
    protected string $name = 'update_block';

    protected string $title = 'Update Block';

    protected string $description = 'Updates an existing block in a page. You can modify block data without recreating the entire page structure.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('The ID of the page (as string or integer)')->nullable(),
            'page_slug' => $schema->string()->description('The slug of the page (alternative to ID)')->nullable(),
            'block_index' => $schema->integer()->description('The index of the block to update (0-based)'),
            'data' => $schema->object()->description('The new data for the block'),
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
            'page_id' => 'sometimes|string',
            'page_slug' => 'sometimes|string',
            'block_index' => 'required|integer|min:0',
            'data' => 'required|array',
        ]);

        // Convertir page_id en integer si c'est une string
        if (isset($validated['page_id'])) {
            $validated['page_id'] = is_numeric($validated['page_id']) ? (int) $validated['page_id'] : null;
            if ($validated['page_id'] === null) {
                return Response::error('Invalid page_id format. ID must be a number.');
            }
        }

        // Find the page by ID or slug
        if (isset($validated['page_id'])) {
            $page = Page::find($validated['page_id']);
            if (!$page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (isset($validated['page_slug'])) {
            $page = Page::where('slug', $validated['page_slug'])->first();
            if (!$page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error('Either "page_id" or "page_slug" must be provided to identify the page.');
        }

        // Prevent updating home page via MCP
        if ($page->isHome()) {
            return Response::error('Home page cannot be updated via MCP.');
        }

        try {
            $registry = app(BlockRegistry::class);
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];
            $blockIndex = $validated['block_index'];

            // Vérifier que l'index existe
            if (!isset($sections[$blockIndex])) {
                return Response::error("Block at index {$blockIndex} does not exist. Page has " . count($sections) . ' blocks.');
            }

            $existingBlock = $sections[$blockIndex];
            $blockType = $existingBlock['type'] ?? null;

            if (!$blockType) {
                return Response::error('Block at index ' . $blockIndex . ' has no type.');
            }

            // Vérifier que le bloc existe dans le registry
            if (!$registry->has($blockType)) {
                return Response::error("Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.");
            }

            // Mettre à jour les données du bloc
            $sections[$blockIndex] = [
                'type' => $blockType,
                'data' => $validated['data'],
            ];

            // Mettre à jour le contenu
            $content['sections'] = $sections;
            if (!isset($content['metadata'])) {
                $content['metadata'] = [];
            }
            if (!isset($content['metadata']['schema_version'])) {
                $content['metadata']['schema_version'] = 1;
            }

            $page->content = $content;
            $page->save();
            $page->refresh();

            return Response::json([
                'success' => true,
                'message' => 'Block updated successfully',
                'block' => [
                    'index' => $blockIndex,
                    'type' => $blockType,
                    'data' => $validated['data'],
                ],
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to update block: ' . $e->getMessage());
        }
    }
}
