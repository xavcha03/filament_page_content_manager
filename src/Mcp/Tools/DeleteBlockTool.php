<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class DeleteBlockTool extends Tool
{
    protected string $name = 'delete_block';

    protected string $title = 'Delete Block';

    protected string $description = 'Deletes a specific block from a page by its index. Useful to remove obsolete or misplaced blocks.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('The ID of the page (as string or integer)')->nullable(),
            'page_slug' => $schema->string()->description('The slug of the page (alternative to ID)')->nullable(),
            'block_index' => $schema->integer()->description('The index of the block to delete (0-based)'),
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
            $blockIndex = $validated['block_index'];

            // Vérifier que l'index existe
            if (!isset($sections[$blockIndex])) {
                return Response::error("Block at index {$blockIndex} does not exist. Page has " . count($sections) . ' blocks.');
            }

            $deletedBlock = $sections[$blockIndex];

            // Supprimer le bloc
            unset($sections[$blockIndex]);
            $sections = array_values($sections); // Réindexer le tableau

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
                'message' => 'Block deleted successfully',
                'deleted_block' => $deletedBlock,
                'remaining_blocks' => count($sections),
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to delete block: ' . $e->getMessage());
        }
    }
}
