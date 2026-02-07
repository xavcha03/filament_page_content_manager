<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class ListMainMenuTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'list_main_menu';

    protected string $title = 'List Main Menu';

    protected string $description = 'Lists all links from the main menu. Returns the normalized list with count and index for each link.';

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
                'links' => array_map(
                    fn (array $link, int $index): array => ['index' => $index] + $link,
                    $links,
                    array_keys($links)
                ),
                'count' => count($links),
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to list main menu links: ' . $e->getMessage());
        }
    }
}

