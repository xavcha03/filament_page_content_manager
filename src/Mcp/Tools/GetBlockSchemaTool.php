<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Mcp\Helpers\BlockInfoExtractor;

class GetBlockSchemaTool extends Tool
{
    protected string $name = 'get_block_schema';

    protected string $title = 'Get Block Schema';

    protected string $description = 'Retrieves complete schema information for a specific block type including all fields, types, options, examples, and requirements.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('The type of the block (e.g., "hero", "text", "cta")'),
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
            'type' => 'required|string',
        ]);

        try {
            $registry = app(BlockRegistry::class);
            $blockType = $validated['type'];

            // Vérifier que le bloc existe
            if (!$registry->has($blockType)) {
                return Response::error("Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.");
            }

            $blockClass = $registry->get($blockType);
            $extractor = app(BlockInfoExtractor::class);

            // Extraire toutes les informations du bloc
            $blockInfo = $extractor->extract($blockType, $blockClass);

            return Response::json([
                'success' => true,
                'block' => $blockInfo,
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to get block schema: ' . $e->getMessage());
        }
    }
}
