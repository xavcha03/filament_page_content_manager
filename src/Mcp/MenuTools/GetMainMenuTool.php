<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class GetMainMenuTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'get_main_menu';

    protected string $title = 'Get Main Menu';

    protected string $description = 'Returns the complete main menu payload. Useful to inspect the exact state before update, move, reorder, or replace operations.';

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
            $links = $this->menuService()->all();

            return Response::json([
                'success' => true,
                'menu' => [
                    'links' => $links,
                    'count' => count($links),
                ],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to get main menu: ' . $e->getMessage());
        }
    }
}

