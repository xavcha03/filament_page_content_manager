<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class ListPagesTool extends Tool
{
    protected string $name = 'list_pages';

    protected string $title = 'List Pages';

    protected string $description = 'Lists all pages from the pages table in Filament. Returns page ID, title, slug, type (home/standard), content_mode, experience_key, and status. Can filter by status or type. Useful to get page IDs or slugs before using other tools.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['draft', 'scheduled', 'published', 'all'])->description('Filter pages by status'),
            'type' => $schema->string()->enum(['home', 'standard', 'all'])->description('Filter pages by type'),
            'content_mode' => $schema->string()->enum(['blocks', 'experience', 'all'])->description('Filter pages by content mode'),
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
            'status' => 'sometimes|string|in:draft,scheduled,published,all',
            'type' => 'sometimes|string|in:home,standard,all',
            'content_mode' => 'sometimes|string|in:blocks,experience,all',
        ]);

        try {
            $query = Page::query();

            // Filter by status
            if (isset($validated['status']) && $validated['status'] !== 'all') {
                $query->where('status', $validated['status']);
            }

            // Filter by type
            if (isset($validated['type']) && $validated['type'] !== 'all') {
                $query->where('type', $validated['type']);
            }

            if (isset($validated['content_mode']) && $validated['content_mode'] !== 'all') {
                $query->where('content_mode', $validated['content_mode']);
            }

            $pages = $query->orderBy('title')->get();

            return Response::json([
                'success' => true,
                'pages' => $pages->map(function (Page $page) {
                    return [
                        'id' => $page->id,
                        'title' => $page->title,
                        'slug' => $page->slug,
                        'type' => $page->type,
                        'content_mode' => $page->content_mode ?? Page::CONTENT_MODE_BLOCKS,
                        'experience_key' => $page->experience_key,
                        'status' => $page->status,
                    ];
                })->toArray(),
                'count' => $pages->count(),
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to list pages: ' . $e->getMessage());
        }
    }
}

