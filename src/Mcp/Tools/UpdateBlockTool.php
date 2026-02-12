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

class UpdateBlockTool extends Tool
{
    protected string $name = 'update_block';

    protected string $title = 'Update Block';

    protected string $description = 'Updates an existing block in a page. Corresponds to editing a block in the "Contenu" tab in Filament. Use block_index (0-based) to identify which block to update. The block type cannot be changed - only the data can be modified. IMPORTANT: For blocks with images, images must be uploaded via Filament admin first, then reference them by image_id. Do not include image URLs or base64 data.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID (as string or integer). Either page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug (alternative to ID). Either page_id or page_slug required.')->nullable(),
            'block_index' => $schema->integer()->description('Block index (0-based) - The position of the block in the page "Contenu" tab. Use get_page_content to see current block indices.'),
            'data' => $schema->object()->description('New block data - Object with all block fields. Use get_block_schema to see required fields. IMPORTANT: For image fields, use image_id (MediaFile ID uploaded via Filament admin), not URLs or base64.'),
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
            return Response::error(\Xavcha\PageContentManager\Mcp\Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        try {
            $registry = app(BlockRegistry::class);
            $blockValidator = app(BlockDataValidator::class);
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

            $validation = $blockValidator->validateBlockData($blockType, $validated['data']);
            if ($validation['ok'] !== true) {
                return Response::error("Invalid block payload for '{$blockType}': {$validation['error']}");
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
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to update block: ' . $e->getMessage());
        }
    }
}
