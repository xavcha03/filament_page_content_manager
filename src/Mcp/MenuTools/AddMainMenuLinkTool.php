<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class AddMainMenuLinkTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'add_main_menu_link';

    protected string $title = 'Add Main Menu Link';

    protected string $description = 'Adds a new link to the main menu. You can provide url/label directly, or provide page_slug/page_id to derive url/label from an existing page.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Link URL (internal path like "/services" or absolute URL like "https://example.com").')->nullable(),
            'label' => $schema->string()->description('Visible label in the menu. If omitted with page_slug/page_id, page title is used.')->nullable(),
            'target_blank' => $schema->boolean()->description('Open link in new tab.')->nullable(),
            'position' => $schema->integer()->description('Optional 0-based position to insert the link. By default, appends at the end.')->nullable(),
            'page_id' => $schema->string()->description('Optional page ID to derive url/label from an existing page.')->nullable(),
            'page_slug' => $schema->string()->description('Optional page slug to derive url/label from an existing page.')->nullable(),
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
            'url' => 'nullable|string|max:500',
            'label' => 'nullable|string|max:255',
            'target_blank' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
            'page_id' => 'nullable|string|max:255',
            'page_slug' => 'nullable|string|max:255',
        ]);

        try {
            $link = $this->resolveLinkPayload($validated);
            $result = $this->menuService()->add($link, $validated['position'] ?? null);

            return Response::json([
                'success' => true,
                'message' => 'Main menu link added successfully.',
                'link' => $result['link'],
                'index' => $result['index'],
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to add main menu link: ' . $e->getMessage());
        }
    }
}

