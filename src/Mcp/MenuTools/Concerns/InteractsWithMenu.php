<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\MenuTools\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Xavcha\PageContentManager\Menu\MenuLinksService;

trait InteractsWithMenu
{
    protected function menuService(): MenuLinksService
    {
        return app(MenuLinksService::class);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{url: string, label: string, target_blank: bool}
     */
    protected function resolveLinkPayload(array $payload): array
    {
        $url = isset($payload['url']) ? trim((string) $payload['url']) : '';
        $label = isset($payload['label']) ? trim((string) $payload['label']) : '';
        $targetBlank = (bool) Arr::get($payload, 'target_blank', false);

        $pageId = Arr::get($payload, 'page_id');
        $pageSlug = Arr::get($payload, 'page_slug');

        if (($url === '' || $label === '') && ($pageId !== null || $pageSlug !== null)) {
            $page = $this->resolvePage($pageId, $pageSlug);

            if ($url === '') {
                $url = ($page->type ?? null) === 'home' ? '/' : '/' . ltrim((string) $page->slug, '/');
            }

            if ($label === '') {
                $label = (string) ($page->title ?? '');
            }
        }

        return [
            'url' => $url,
            'label' => $label,
            'target_blank' => $targetBlank,
        ];
    }

    protected function resolvePage(mixed $pageId, mixed $pageSlug): Model
    {
        $pageModelClass = (string) config('page-content-manager.models.page');

        if ($pageModelClass === '' || ! class_exists($pageModelClass)) {
            throw new InvalidArgumentException('Configured page model class is invalid.');
        }

        $query = $pageModelClass::query();

        if ($pageId !== null) {
            $page = $query->find((int) $pageId);

            if ($page === null) {
                throw new InvalidArgumentException("Page not found for page_id={$pageId}.");
            }

            return $page;
        }

        if ($pageSlug !== null && $pageSlug !== '') {
            $page = $query->where('slug', (string) $pageSlug)->first();

            if ($page === null) {
                throw new InvalidArgumentException("Page not found for page_slug={$pageSlug}.");
            }

            return $page;
        }

        throw new InvalidArgumentException('page_id or page_slug must be provided.');
    }
}

