<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

class UpdateMainMenuLinkTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'update_main_menu_link';

    protected string $title = 'Update Main Menu Link';

    protected string $description = 'Updates an existing link by 0-based index. Supports partial update with url/label/target_blank, or page_slug/page_id to derive url/label.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'index' => $schema->integer()->description('0-based link index to update.')->required(),
            'url' => $schema->string()->description('Updated URL.')->nullable(),
            'label' => $schema->string()->description('Updated label.')->nullable(),
            'target_blank' => $schema->boolean()->description('Updated target blank flag.')->nullable(),
            'page_id' => $schema->string()->description('Optional page ID to derive url/label.')->nullable(),
            'page_slug' => $schema->string()->description('Optional page slug to derive url/label.')->nullable(),
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
            'url' => 'nullable|string|max:500',
            'label' => 'nullable|string|max:255',
            'target_blank' => 'nullable|boolean',
            'page_id' => 'nullable|string|max:255',
            'page_slug' => 'nullable|string|max:255',
        ]);

        try {
            $index = (int) $validated['index'];
            $service = $this->menuService();
            $links = $service->all();

            if (! isset($links[$index])) {
                return Response::error("Main menu link not found at index {$index}.");
            }

            $current = $links[$index];
            $hasPageRef = isset($validated['page_id']) || isset($validated['page_slug']);
            $hasDirectField = isset($validated['url']) || isset($validated['label']) || array_key_exists('target_blank', $validated);

            if (! $hasPageRef && ! $hasDirectField) {
                return Response::error('Nothing to update. Provide at least one updatable field.');
            }

            $changes = $hasPageRef
                ? $this->resolveLinkPayload(array_merge($current, $validated))
                : array_filter(
                    [
                        'url' => $validated['url'] ?? null,
                        'label' => $validated['label'] ?? null,
                        'target_blank' => $validated['target_blank'] ?? null,
                    ],
                    static fn (mixed $value): bool => $value !== null
                );

            $result = $service->update($index, $changes);

            return Response::json([
                'success' => true,
                'message' => 'Main menu link updated successfully.',
                'index' => $result['index'],
                'link' => $result['link'],
                'count' => count($result['links']),
                'links' => $result['links'],
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to update main menu link: ' . $e->getMessage());
        }
    }
}

