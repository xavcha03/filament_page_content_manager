<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Throwable;
use Xavcha\PageContentManager\Mcp\MenuTools\Concerns\InteractsWithMenu;

#[IsDestructive]
class ReplaceMainMenuLinksTool extends Tool
{
    use InteractsWithMenu;

    protected string $name = 'replace_main_menu_links';

    protected string $title = 'Replace Main Menu Links';

    protected string $description = 'Use when you want to set the whole main menu in one call (e.g. after building the list from site structure). Replaces the entire main menu links array. Accepts either "links" (array of { url, label }) or "pages" (array of { slug, title }); pages are converted to links. Prefer this for deterministic full-state updates.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'links' => $schema->array()
                ->description('Complete links array. Each item: url (string), label (string), target_blank (optional bool). Use either "links" or "pages".')
                ->nullable(),
            'pages' => $schema->array()
                ->description('Alternative to "links": array of { slug, title } or { url, label }. Converted to menu links (url = /slug, label = title).')
                ->nullable(),
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
        $input = $request->all();

        // Accept "pages" as alternative to "links" (e.g. list of pages from list_pages / site structure)
        if ((! isset($input['links']) || $input['links'] === [] || $input['links'] === null)
            && ! empty($input['pages']) && is_array($input['pages'])) {
            $input['links'] = $this->normalizePagesToLinks($input['pages']);
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            $input,
            [
                'links' => 'required|array|min:0',
                'links.*.url' => 'required|string|max:500',
                'links.*.label' => 'required|string|max:255',
                'links.*.target_blank' => 'nullable|boolean',
            ],
            [
                'links.required' => 'Provide either "links" (array of { url, label }) or "pages" (array of { slug, title } or { url, label }).',
            ]
        );

        if ($validator->fails()) {
            return Response::error('Invalid menu data: ' . implode(' ', $validator->errors()->all()));
        }

        $links = $validator->validated()['links'];

        try {
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

    /**
     * @param array<int, array<string, mixed>> $pages
     * @return array<int, array{url: string, label: string, target_blank: bool}>
     */
    private function normalizePagesToLinks(array $pages): array
    {
        return array_map(function (mixed $page): array {
            if (! is_array($page)) {
                return ['url' => '/', 'label' => 'Page', 'target_blank' => false];
            }
            $url = isset($page['url']) ? trim((string) $page['url']) : '';
            $label = isset($page['label']) ? trim((string) $page['label']) : '';
            $slug = isset($page['slug']) ? trim((string) $page['slug'], '/') : '';
            $title = isset($page['title']) ? trim((string) $page['title']) : '';

            if ($url === '' && $slug !== '') {
                $url = $slug === '' ? '/' : '/' . $slug;
            }
            if ($url === '') {
                $url = '/';
            }
            if ($label === '' && $title !== '') {
                $label = $title;
            }
            if ($label === '') {
                $label = 'Page';
            }

            return [
                'url' => $url,
                'label' => $label,
                'target_blank' => (bool) ($page['target_blank'] ?? false),
            ];
        }, $pages);
    }
}

