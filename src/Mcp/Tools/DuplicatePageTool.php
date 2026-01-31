<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Models\Page;

class DuplicatePageTool extends Tool
{
    protected string $name = 'duplicate_page';

    protected string $title = 'Duplicate Page';

    protected string $description = 'Creates a duplicate of an existing page. Corresponds to the duplicate action in Filament pages list. Copies all page data (title, content blocks, SEO fields) to a new page. The new page will have a different slug (auto-generated or provided via new_slug) and can have a different title and status. Original page content and blocks are fully copied.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Page ID (as string or integer) to identify the page to duplicate. Either id or slug required.')->nullable(),
            'slug' => $schema->string()->description('Page slug to identify the page to duplicate (alternative to ID). Either id or slug required.')->nullable(),
            'new_slug' => $schema->string()->description('New slug - URL slug for the duplicated page (must be unique). If not provided, will be auto-generated as "{original-slug}-copy-{number}".')->nullable(),
            'new_title' => $schema->string()->description('New title - Title for the duplicated page. If not provided, will use "{original title} (Copy)".')->nullable(),
            'status' => $schema->string()->enum(['draft', 'published'])->description('Status (Statut) - Status for the duplicated page: "draft" (Brouillon) or "published" (Publié). Default: draft.')->nullable(),
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
            'new_slug' => 'sometimes|string|max:255',
            'new_title' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:draft,published',
        ]);

        // Convertir id en integer si c'est une string
        if (isset($validated['id'])) {
            $validated['id'] = is_numeric($validated['id']) ? (int) $validated['id'] : null;
            if ($validated['id'] === null) {
                return Response::error('Invalid ID format. ID must be a number.');
            }
        }

        // Find the page by ID or slug
        if (isset($validated['id'])) {
            $originalPage = Page::find($validated['id']);
            if (!$originalPage) {
                return Response::error('Page not found with the provided ID.');
            }
        } elseif (isset($validated['slug'])) {
            $originalPage = Page::where('slug', $validated['slug'])->first();
            if (!$originalPage) {
                return Response::error('Page not found with the provided slug.');
            }
        } else {
            return Response::error('Either "id" or "slug" must be provided to identify the page to duplicate.');
        }

        try {
            // Générer le nouveau slug si non fourni
            $newSlug = $validated['new_slug'] ?? null;
            if (!$newSlug) {
                $baseSlug = $originalPage->slug;
                $counter = 1;
                do {
                    $newSlug = $baseSlug . '-copy-' . $counter;
                    $counter++;
                } while (Page::where('slug', $newSlug)->exists() && $counter < 100);

                if ($counter >= 100) {
                    return Response::error('Could not generate a unique slug. Please provide a custom slug.');
                }
            } else {
                // Vérifier l'unicité du slug fourni
                if (Page::where('slug', $newSlug)->exists()) {
                    return Response::error('A page with this slug already exists.');
                }
            }

            // Générer le nouveau titre si non fourni
            $newTitle = $validated['new_title'] ?? ($originalPage->title . ' (Copy)');

            // Créer la page dupliquée
            $duplicatedPage = Page::create([
                'title' => $newTitle,
                'slug' => $newSlug,
                'type' => $originalPage->type,
                'status' => $validated['status'] ?? 'draft',
                'seo_title' => $originalPage->seo_title,
                'seo_description' => $originalPage->seo_description,
                'content' => $originalPage->content, // Copier tout le contenu
            ]);

            return Response::json([
                'success' => true,
                'message' => 'Page duplicated successfully',
                'original_page' => [
                    'id' => $originalPage->id,
                    'title' => $originalPage->title,
                    'slug' => $originalPage->slug,
                ],
                'duplicated_page' => [
                    'id' => $duplicatedPage->id,
                    'title' => $duplicatedPage->title,
                    'slug' => $duplicatedPage->slug,
                    'type' => $duplicatedPage->type,
                    'status' => $duplicatedPage->status,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to duplicate page: ' . $e->getMessage());
        }
    }
}
