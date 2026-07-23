<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Models\Page;

class SetPageContentModeTool extends Tool
{
    protected string $name = 'set_page_content_mode';

    protected string $title = 'Set Page Content Mode';

    protected string $description = 'Switches a page between content_mode "blocks" and "experience". Existing block content and experience_content bags are preserved; only the active renderer changes. When switching to experience, experience_key is required and must be a registered Experience.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID. Either page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug. Either page_id or page_slug required.')->nullable(),
            'content_mode' => $schema->string()->enum(['blocks', 'experience'])->description('Target content mode.'),
            'experience_key' => $schema->string()->description('Required when content_mode is experience.')->nullable(),
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
            'content_mode' => 'required|string|in:blocks,experience',
            'experience_key' => 'sometimes|nullable|string',
        ]);

        if (isset($validated['page_id'])) {
            $validated['page_id'] = is_numeric($validated['page_id']) ? (int) $validated['page_id'] : null;
            if ($validated['page_id'] === null) {
                return Response::error('Invalid page_id format. ID must be a number.');
            }
        }

        if (isset($validated['page_id'])) {
            $page = Page::find($validated['page_id']);
            if (! $page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (isset($validated['page_slug'])) {
            $page = Page::where('slug', $validated['page_slug'])->first();
            if (! $page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error(\Xavcha\PageContentManager\Mcp\Messages::PAGE_IDENTIFIER_REQUIRED);
        }

        try {
            $mode = $validated['content_mode'];

            if ($mode === Page::CONTENT_MODE_EXPERIENCE) {
                $key = $validated['experience_key'] ?? $page->experience_key;
                if (! is_string($key) || $key === '') {
                    return Response::error('experience_key is required when switching to experience mode.');
                }

                if (! app(ExperienceRegistry::class)->has($key)) {
                    return Response::error("Experience key '{$key}' does not exist. Use list_experiences.");
                }

                $page->content_mode = Page::CONTENT_MODE_EXPERIENCE;
                $page->experience_key = $key;
            } else {
                $page->content_mode = Page::CONTENT_MODE_BLOCKS;
            }

            $page->save();
            $page->refresh();

            return Response::json([
                'success' => true,
                'message' => 'Page content mode updated. Other content payloads were preserved.',
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content_mode' => $page->content_mode,
                    'experience_key' => $page->experience_key,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to set page content mode: ' . $e->getMessage());
        }
    }
}
