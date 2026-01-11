<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class UpdatePageTool extends Tool
{
    protected string $name = 'update_page';

    protected string $title = 'Update Page';

    protected string $description = 'Updates an existing page by ID or slug. You can update the title, slug, SEO fields, and status.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the page to update (as string or integer)')->nullable(),
            'slug' => $schema->string()->description('The slug of the page to update (alternative to ID)')->nullable(),
            'title' => $schema->string()->description('The new title of the page')->nullable(),
            'slug_new' => $schema->string()->description('The new URL slug for the page (must be unique)')->nullable(),
            'seo_title' => $schema->string()->description('Optional SEO title for the page')->nullable(),
            'seo_description' => $schema->string()->description('Optional SEO description for the page')->nullable(),
            'status' => $schema->string()->enum(['draft', 'scheduled', 'published'])->description('The new status of the page')->nullable(),
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
            'id' => 'sometimes|string',
            'slug' => 'sometimes|string',
            'title' => 'sometimes|string|max:255',
            'slug_new' => 'sometimes|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,scheduled,published',
        ]);

        // Convertir id en integer si c'est une string (pour compatibilité avec les outils MCP)
        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;
            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
            }
        }

        // Find the page by ID or slug
        if (isset($validated['id'])) {
            $page = Page::find($validated['id']);
            if (!$page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (isset($validated['slug'])) {
            $page = Page::where('slug', $validated['slug'])->first();
            if (!$page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error('Either "id" or "slug" must be provided to identify the page.');
        }

        try {
            $updateData = [];

            if (isset($validated['title'])) {
                $updateData['title'] = $validated['title'];
            }

            if (isset($validated['slug_new'])) {
                // Vérifier l'unicité du nouveau slug
                if (Page::where('slug', $validated['slug_new'])->where('id', '!=', $page->id)->exists()) {
                    return Response::error('A page with this slug already exists.');
                }
                $updateData['slug'] = $validated['slug_new'];
            }

            if (isset($validated['seo_title'])) {
                $updateData['seo_title'] = $validated['seo_title'];
            }

            if (isset($validated['seo_description'])) {
                $updateData['seo_description'] = $validated['seo_description'];
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if (empty($updateData)) {
                return Response::error('No fields to update. Provide at least one field to update.');
            }

            $page->update($updateData);
            $page->refresh();

            return Response::json([
                'success' => true,
                'message' => 'Page updated successfully',
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'type' => $page->type,
                    'status' => $page->status,
                    'seo_title' => $page->seo_title,
                    'seo_description' => $page->seo_description,
                    'is_home' => $page->isHome(),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to update page: ' . $e->getMessage());
        }
    }
}

