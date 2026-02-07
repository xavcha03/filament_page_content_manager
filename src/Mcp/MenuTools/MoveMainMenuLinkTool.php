<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class MoveMainMenuLinkTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'move_main_menu_link';

    protected string $title = 'Move Main Menu Link';

    protected string $description = 'Moves one main menu link from one 0-based index to another.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from_index' => $schema->integer()->description('Current 0-based index of the link to move.')->required(),
            'to_index' => $schema->integer()->description('Target 0-based index where the link should be inserted.')->required(),
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
            'from_index' => 'required|integer|min:0',
            'to_index' => 'required|integer|min:0',
        ]);

        try {
            $result = $this->menuService()->move((int) $validated['from_index'], (int) $validated['to_index']);

            return Response::json([
                'success' => true,
                'message' => 'Main menu link moved successfully.',
                'from_index' => (int) $validated['from_index'],
                'to_index' => (int) $validated['to_index'],
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to move main menu link: ' . $e->getMessage());
        }
    }
}

