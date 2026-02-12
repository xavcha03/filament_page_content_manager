<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Mcp\Messages;
use Xavcha\PageContentManager\Models\Page;

class UpdatePageTool extends Tool
{
    protected string $name = 'update_page';

    protected string $title = 'Update Page';

    protected string $description = 'Use when you need to change a page\'s title, SEO fields, or status without creating a new page. Updates page information from the "Général" and "SEO" tabs in Filament. Can update: Title (Titre), Slug (URL - use slug_new), Status (draft/scheduled/published), SEO Title and Description. IMPORTANT: For the home page, the slug is fixed by the system and must NOT be changed (do not send slug or slug_new for the home page – update only the title). For other pages, avoid changing the slug unless explicitly requested. Identify the page with page_id or page_slug (e.g. "home" for home page).';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID (numeric). One of page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug (e.g. "home" for home page, "contact"). One of page_id or page_slug required.')->nullable(),
            'id' => $schema->string()->description('Alias for page_id.')->nullable(),
            'slug' => $schema->string()->description('Alias for page_slug.')->nullable(),
            'title' => $schema->string()->description('Title (Titre) - New page title from "Général" tab.')->nullable(),
            'slug_new' => $schema->string()->description('Slug - New URL slug. IMPORTANT: Do NOT use slug_new for the home page (its slug is managed by the system and must remain stable). For other pages, only use slug_new if explicitly requested and if changing the URL is safe.')->nullable(),
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
            'page_id' => 'sometimes|string',
            'page_slug' => 'sometimes|string',
            'title' => 'sometimes|string|max:255',
            'slug_new' => 'sometimes|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,scheduled,published',
        ]);

        // Normaliser page_id / page_slug vers id / slug
        if (isset($validated['page_id']) && ! isset($validated['id'])) {
            $validated['id'] = $validated['page_id'];
        }
        if (empty($validated['slug']) && ! empty($validated['page_slug'])) {
            $validated['slug'] = $validated['page_slug'];
        }

        // Convertir id en integer si c'est une string (pour compatibilité avec les outils MCP)
        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;
            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
            }
        }

        // Find the page by ID, slug, or home-page alias
        if (isset($validated['id'])) {
            $page = Page::find($validated['id']);
            if (! $page) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (! empty($validated['slug'])) {
            $slug = trim((string) $validated['slug']);
            $page = Page::where('slug', $slug)->first();
            // Si slug "home" / "accueil" / "" et pas trouvé, cibler la page d'accueil (type=home)
            if (! $page && in_array(strtolower($slug), ['home', 'accueil', ''], true)) {
                $page = Page::where('type', 'home')->first();
            }
            if (! $page) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error(Messages::PAGE_IDENTIFIER_REQUIRED);
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

