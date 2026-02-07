<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class UpsertMainMenuLinkTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'upsert_main_menu_link';

    protected string $title = 'Upsert Main Menu Link';

    protected string $description = 'Creates or updates a menu link by URL key. If a link with the same URL exists, it is updated in place. Otherwise, a new link is appended or inserted at position.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Link URL used as unique key for upsert. Can be omitted if page_slug/page_id is provided.')->nullable(),
            'label' => $schema->string()->description('Visible label in the menu.')->nullable(),
            'target_blank' => $schema->boolean()->description('Open link in new tab.')->nullable(),
            'position' => $schema->integer()->description('Optional insertion position if link does not already exist.')->nullable(),
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
            $service = $this->menuService();
            $existingIndex = $service->findIndexByUrl($link['url']);

            if ($existingIndex !== null) {
                $result = $service->update($existingIndex, $link);

                return Response::json([
                    'success' => true,
                    'message' => 'Main menu link updated (upsert match by URL).',
                    'operation' => 'update',
                    'index' => $result['index'],
                    'link' => $result['link'],
                    'count' => count($result['links']),
                    'links' => $result['links'],
                ]);
            }

            $result = $service->add($link, $validated['position'] ?? null);

            return Response::json([
                'success' => true,
                'message' => 'Main menu link created (upsert new URL).',
                'operation' => 'create',
                'index' => $result['index'],
                'link' => $result['link'],
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to upsert main menu link: ' . $e->getMessage());
        }
    }
}

