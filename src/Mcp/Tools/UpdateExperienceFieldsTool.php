<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Models\Page;

class UpdateExperienceFieldsTool extends Tool
{
    protected string $name = 'update_experience_fields';

    protected string $title = 'Update Experience Fields';

    protected string $description = 'Partially updates Experience field values on a page (merge). Does NOT change the Experience structure — only values for the active experience_key (or the key you pass). Use get_page_content and get_experience_schema first. Page must be in content_mode=experience.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Page ID. Either page_id or page_slug required.')->nullable(),
            'page_slug' => $schema->string()->description('Page slug. Either page_id or page_slug required.')->nullable(),
            'experience_key' => $schema->string()->description('Optional. Defaults to the page active experience_key. Must match a registered Experience.')->nullable(),
            'data' => $schema->object()->description('Partial field values to merge. Nested objects are merged recursively.'),
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
            'experience_key' => 'sometimes|string',
            'data' => 'required|array',
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
            if (! $page->isExperienceMode()) {
                return Response::error('Page is not in experience content_mode. Use set_page_content_mode first.');
            }

            $registry = app(ExperienceRegistry::class);
            $key = $validated['experience_key'] ?? $page->experience_key;

            if (! is_string($key) || $key === '') {
                return Response::error('experience_key is missing on the page.');
            }

            if (! $registry->has($key)) {
                return Response::error("Experience key '{$key}' does not exist. Use list_experiences.");
            }

            $previousKey = $page->experience_key;
            $page->experience_key = $key;

            $bag = is_array($page->experience_content) ? $page->experience_content : [];
            $existing = is_array($bag[$key] ?? null) ? $bag[$key] : [];
            $merged = array_replace_recursive($existing, $validated['data']);
            $bag[$key] = $merged;
            $page->experience_content = $bag;

            // If updating a non-active key bag without switching, restore previous active key
            if (isset($validated['experience_key']) && $validated['experience_key'] !== $previousKey) {
                // Keep the key we just wrote as active when explicitly passed
            }

            $page->save();
            $page->refresh();

            return Response::json([
                'success' => true,
                'message' => 'Experience fields updated successfully',
                'experience_key' => $key,
                'data' => $merged,
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content_mode' => $page->content_mode,
                    'experience_key' => $page->experience_key,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to update experience fields: ' . $e->getMessage());
        }
    }
}
