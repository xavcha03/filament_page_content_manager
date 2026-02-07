<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class ReplaceMainMenuLinksTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'replace_main_menu_links';

    protected string $title = 'Replace Main Menu Links';

    protected string $description = 'Replaces the entire main menu links array in one operation. Useful when an agent wants deterministic full-state output.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'links' => $schema->array()
                ->description('Complete links array. Each link must include url and label, and can include target_blank.')
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
            'links' => 'required|array',
            'links.*.url' => 'required|string|max:500',
            'links.*.label' => 'required|string|max:255',
            'links.*.target_blank' => 'nullable|boolean',
        ]);

        try {
            /** @var array<int, array<string, mixed>> $links */
            $links = $validated['links'];
            $replaced = $this->menuService()->replaceAll($links);

            return Response::json([
                'success' => true,
                'message' => 'Main menu links replaced successfully.',
                'count' => count($replaced),
                'links' => $replaced,
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to replace main menu links: ' . $e->getMessage());
        }
    }
}

