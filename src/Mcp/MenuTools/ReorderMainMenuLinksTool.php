<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class ReorderMainMenuLinksTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'reorder_main_menu_links';

    protected string $title = 'Reorder Main Menu Links';

    protected string $description = 'Reorders all main menu links using a full 0-based index mapping (new_order).';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'new_order' => $schema->array()
                ->description('Full list of indices describing the new order, e.g. [2,0,1].')
                ->items($schema->integer())
                ->required(),
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
            'new_order' => 'required|array',
            'new_order.*' => 'integer|min:0',
        ]);

        try {
            /** @var array<int, int> $newOrder */
            $newOrder = array_map(static fn (mixed $value): int => (int) $value, $validated['new_order']);
            $result = $this->menuService()->reorder($newOrder);

            return Response::json([
                'success' => true,
                'message' => 'Main menu links reordered successfully.',
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to reorder main menu links: ' . $e->getMessage());
        }
    }
}

