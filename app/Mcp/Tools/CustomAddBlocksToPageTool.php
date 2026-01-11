<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Models\Page;

class CustomAddBlocksToPageTool extends Tool
{
    protected string $name = 'custom_add_blocks_to_page';

    protected string $title = 'Custom Add Blocks to Page (including Home)';

    protected string $description = 'Adds content blocks to an existing page, including the home page. You can add one or multiple blocks at once. Each block must have a type and data matching the block schema. This tool allows modification of the home page, unlike the standard add_blocks_to_page tool.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the page (as string or integer). Either id or slug must be provided.'),
            'slug' => $schema->string()->description('The slug of the page (alternative to ID). Either id or slug must be provided.'),
            'blocks' => $schema->array()->description('Array of blocks to add. Each block must have "type" and "data" fields.'),
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
            'blocks' => 'required|array|min:1',
            'blocks.*.type' => 'required|string',
            'blocks.*.data' => 'required|array',
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

        // Note: This tool allows updating the home page, unlike AddBlocksToPageTool
        // No restriction check for home page here

        try {
            $registry = app(BlockRegistry::class);
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];

            // Valider et ajouter chaque bloc
            $addedBlocks = [];
            foreach ($validated['blocks'] as $block) {
                $blockType = $block['type'];
                $blockData = $block['data'];

                // Vérifier que le bloc existe
                if (!$registry->has($blockType)) {
                    return Response::error("Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.");
                }

                // Ajouter la section
                $sections[] = [
                    'type' => $blockType,
                    'data' => $blockData,
                ];

                $addedBlocks[] = [
                    'type' => $blockType,
                    'data' => $blockData,
                ];
            }

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
                'message' => 'Blocks added successfully',
                'added_blocks' => $addedBlocks,
                'total_sections' => count($sections),
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to add blocks: ' . $e->getMessage());
        }
    }
}
