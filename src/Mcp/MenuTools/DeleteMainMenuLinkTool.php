<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

#[IsDestructive]
class DeleteMainMenuLinkTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'delete_main_menu_link';

    protected string $title = 'Delete Main Menu Link';

    protected string $description = 'Deletes a link from the main menu using 0-based index.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'index' => $schema->integer()->description('0-based link index to delete.')->required(),
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
            'index' => 'required|integer|min:0',
        ]);

        try {
            $result = $this->menuService()->delete((int) $validated['index']);

            return Response::json([
                'success' => true,
                'message' => 'Main menu link deleted successfully.',
                'deleted_link' => $result['deleted_link'],
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to delete main menu link: ' . $e->getMessage());
        }
    }
}

