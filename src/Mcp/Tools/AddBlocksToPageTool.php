<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Mcp\Helpers\BlockDataValidator;
use Xavcha\PageContentManager\Models\Page;

class AddBlocksToPageTool extends Tool
{
    protected string $name = 'add_blocks_to_page';

    protected string $title = 'Add Blocks to Page';

    protected string $description = 'Adds content blocks to a page. Corresponds to adding blocks in the "Contenu" tab in Filament. Each block must have a "type" (e.g., "hero", "text", "cta") and "data" matching the block schema. Use get_block_schema to see required fields for each block type. IMPORTANT: For blocks with images (image, hero, gallery, etc.), images must be uploaded via Filament admin first, then reference them by image_id in the block data. Do not include image URLs or base64 data.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Page ID (as string or integer). Either id or slug required.')->nullable(),
            'slug' => $schema->string()->description('Page slug (alternative to ID). Either id or slug required.')->nullable(),
            'blocks' => $schema->array()->description('Array of blocks to add to the page "Contenu" tab. Each block must have "type" (e.g., "hero", "text", "cta") and "data" (object with block fields). Use get_block_schema to see required fields for each block type. IMPORTANT: For image fields, use image_id (MediaFile ID uploaded via Filament admin), not URLs or base64.'),
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

        try {
            $registry = app(BlockRegistry::class);
            $blockValidator = app(BlockDataValidator::class);
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

                $validation = $blockValidator->validateBlockData($blockType, $blockData);
                if ($validation['ok'] !== true) {
                    return Response::error("Invalid block payload for '{$blockType}': {$validation['error']}");
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
