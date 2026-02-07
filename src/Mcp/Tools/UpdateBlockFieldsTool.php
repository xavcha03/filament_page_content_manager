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

class UpdateBlockFieldsTool extends Tool
{
    protected string $name = 'update_block_fields';

    protected string $title = 'Update Block Fields';

    protected string $description = 'Partially updates an existing block without replacing the entire data. Provide only the fields you want to change. Uses block_index (0-based). Use get_page_content to find indices and get_block_schema for field names.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID (as string or integer). Either page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug (alternative to ID). Either page_id or page_slug required.')->nullable(),
            'block_index' => $schema->integer()->description('Block index (0-based) - The position of the block in the page "Contenu" tab.'),
            'data' => $schema->object()->description('Partial block data - Only the fields you want to change. Nested objects are merged.'),
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

        if (isset($validated['page_id'])) {
            $validated['page_id'] = is_numeric($validated['page_id']) ? (int) $validated['page_id'] : null;
            if ($validated['page_id'] === null) {
                return Response::error('Invalid page_id format. ID must be a number.');
            }
        }

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

        try {
            $registry = app(BlockRegistry::class);
            $blockValidator = app(BlockDataValidator::class);
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];
            $blockIndex = $validated['block_index'];

            if (!isset($sections[$blockIndex])) {
                return Response::error("Block at index {$blockIndex} does not exist. Page has " . count($sections) . ' blocks.');
            }

            $existingBlock = $sections[$blockIndex];
            $blockType = $existingBlock['type'] ?? null;

            if (!$blockType) {
                return Response::error('Block at index ' . $blockIndex . ' has no type.');
            }

            if (!$registry->has($blockType)) {
                return Response::error("Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.");
            }

            $existingData = is_array($existingBlock['data'] ?? null) ? $existingBlock['data'] : [];
            $patchedData = array_replace_recursive($existingData, $validated['data']);

            $validation = $blockValidator->validateBlockData($blockType, $patchedData);
            if ($validation['ok'] !== true) {
                return Response::error("Invalid block payload for '{$blockType}': {$validation['error']}");
            }

            $sections[$blockIndex] = [
                'type' => $blockType,
                'data' => $patchedData,
            ];

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
                'message' => 'Block fields updated successfully',
                'block' => [
                    'index' => $blockIndex,
                    'type' => $blockType,
                    'data' => $patchedData,
                ],
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to update block fields: ' . $e->getMessage());
        }
    }
}
