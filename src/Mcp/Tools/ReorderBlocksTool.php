<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class ReorderBlocksTool extends Tool
{
    protected string $name = 'reorder_blocks';

    protected string $title = 'Reorder Blocks';

    protected string $description = 'Reorders blocks in a page. Corresponds to drag-and-drop reordering in the "Contenu" tab in Filament. Two methods: 1) Move one block using "from_index" and "to_index" (0-based), or 2) Provide complete new order using "new_order" array with all block indices. The "new_order" array must contain exactly the same number of indices as there are blocks, with no duplicates.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID (as string or integer). Either page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug (alternative to ID). Either page_id or page_slug required.')->nullable(),
            'from_index' => $schema->integer()->description('From index (0-based) - Current position of the block to move. Use with to_index to move one block.')->nullable(),
            'to_index' => $schema->integer()->description('To index (0-based) - New position where to move the block. Use with from_index to move one block.')->nullable(),
            'new_order' => $schema->array()->description('New order - Complete array of block indices (0-based) in desired order. Must contain exactly the same number of indices as there are blocks, with no duplicates. Alternative to from_index/to_index.')->nullable(),
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
            'from_index' => 'sometimes|integer|min:0',
            'to_index' => 'sometimes|integer|min:0',
            'new_order' => 'sometimes|array',
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

        try {
            $content = $page->content ?? [];
            $sections = $content['sections'] ?? [];
            $totalBlocks = count($sections);

            if ($totalBlocks === 0) {
                return Response::error('Page has no blocks to reorder.');
            }

            // Méthode 1 : Nouvel ordre complet
            if (isset($validated['new_order']) && is_array($validated['new_order'])) {
                $newOrder = $validated['new_order'];
                
                // Vérifier que tous les indices sont valides
                if (count($newOrder) !== $totalBlocks) {
                    return Response::error("New order must contain exactly {$totalBlocks} indices (one for each block).");
                }

                foreach ($newOrder as $index) {
                    if (!is_int($index) || $index < 0 || $index >= $totalBlocks) {
                        return Response::error("Invalid index {$index} in new_order. Must be between 0 and " . ($totalBlocks - 1) . '.');
                    }
                }

                // Vérifier qu'il n'y a pas de doublons
                if (count($newOrder) !== count(array_unique($newOrder))) {
                    return Response::error('New order contains duplicate indices.');
                }

                // Réorganiser selon le nouvel ordre
                $reorderedSections = [];
                foreach ($newOrder as $oldIndex) {
                    $reorderedSections[] = $sections[$oldIndex];
                }
                $sections = $reorderedSections;
            }
            // Méthode 2 : Déplacer un bloc d'un index à un autre
            elseif (isset($validated['from_index']) && isset($validated['to_index'])) {
                $fromIndex = $validated['from_index'];
                $toIndex = $validated['to_index'];

                if ($fromIndex < 0 || $fromIndex >= $totalBlocks) {
                    return Response::error("from_index {$fromIndex} is out of range. Page has {$totalBlocks} blocks (indices 0-" . ($totalBlocks - 1) . ').');
                }

                if ($toIndex < 0 || $toIndex >= $totalBlocks) {
                    return Response::error("to_index {$toIndex} is out of range. Page has {$totalBlocks} blocks (indices 0-" . ($totalBlocks - 1) . ').');
                }

                if ($fromIndex === $toIndex) {
                    return Response::error('from_index and to_index are the same. No reordering needed.');
                }

                // Déplacer le bloc
                $blockToMove = $sections[$fromIndex];
                unset($sections[$fromIndex]);
                $sections = array_values($sections); // Réindexer

                // Insérer à la nouvelle position
                array_splice($sections, $toIndex, 0, [$blockToMove]);
            } else {
                return Response::error('Either provide "from_index" and "to_index" to move a block, or "new_order" to specify complete new order.');
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
                'message' => 'Blocks reordered successfully',
                'total_blocks' => count($sections),
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to reorder blocks: ' . $e->getMessage());
        }
    }
}
