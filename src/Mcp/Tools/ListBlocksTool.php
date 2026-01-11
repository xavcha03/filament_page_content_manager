<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Mcp\Helpers\BlockInfoExtractor;

class ListBlocksTool extends Tool
{
    protected string $name = 'list_blocks';

    protected string $title = 'List Blocks';

    protected string $description = 'Lists all available content blocks that can be used to build pages. Each block includes its type, description, and available fields.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
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
        try {
            $registry = app(BlockRegistry::class);
            $allBlocks = $registry->all();

            $blocks = [];

            foreach ($allBlocks as $type => $blockClass) {
                try {
                    $blockInfo = BlockInfoExtractor::extract($type, $blockClass);
                    $blocks[] = $blockInfo;
                } catch (\Throwable $e) {
                    // Ignorer les blocs qui ne peuvent pas être analysés
                    continue;
                }
            }

            // Trier par ordre si disponible, sinon par type
            usort($blocks, function ($a, $b) {
                $orderA = $a['order'] ?? 999;
                $orderB = $b['order'] ?? 999;
                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }
                return ($a['type'] ?? '') <=> ($b['type'] ?? '');
            });

            return Response::json([
                'success' => true,
                'blocks' => $blocks,
                'count' => count($blocks),
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to list blocks: ' . $e->getMessage());
        }
    }
}

