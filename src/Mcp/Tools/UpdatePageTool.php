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

    protected string $description = 'Updates page information from the "Général" and "SEO" tabs in Filament. Can update: Title (Titre), Slug (URL - use slug_new), Status (Brouillon/Draft, Planifié/Scheduled, or Publié/Published), SEO Title (Titre SEO), and SEO Description (Description SEO). Note: Slug cannot be changed after creation for existing pages (except home page).';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Page ID (as string or integer) to identify the page to update. Either id or slug required.')->nullable(),
            'slug' => $schema->string()->description('Page slug to identify the page to update (alternative to ID). Either id or slug required.')->nullable(),
            'title' => $schema->string()->description('Title (Titre) - New page title from "Général" tab.')->nullable(),
            'slug_new' => $schema->string()->description('Slug - New URL slug. Note: Slug cannot be changed after creation (except for home page). Use slug_new only if absolutely necessary.')->nullable(),
            'seo_title' => $schema->string()->description('SEO Title (Titre SEO) - Meta title from "SEO" tab.')->nullable(),
            'seo_description' => $schema->string()->description('SEO Description (Description SEO) - Meta description from "SEO" tab.')->nullable(),
            'status' => $schema->string()->enum(['draft', 'scheduled', 'published'])->description('Status (Statut) - Page status: "draft" (Brouillon), "scheduled" (Planifié), or "published" (Publié).')->nullable(),
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

