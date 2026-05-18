<?php

namespace Xavcha\PageContentManager\Services;

use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Http\Resources\PageResource;
use Xavcha\PageContentManager\Models\Page;

class PageUrlResolver
{
    public const RESOLUTION_PAGE = 'page';

    public const RESOLUTION_NOT_FOUND = 'not_found';

    public const RESOLUTION_GONE = 'gone';

    public const RESOLUTION_REDIRECT = 'redirect';

    /**
     * @return array{
     *     resolution: string,
     *     http_status: int,
     *     page?: Page,
     *     redirect?: array{type: string, slug?: string, url?: string, location?: string},
     *     message?: string
     * }
     */
    public function resolve(string $slug): array
    {
        $page = $this->findPageBySlugIncludingTrashed($slug);

        if (! $page) {
            return [
                'resolution' => self::RESOLUTION_NOT_FOUND,
                'http_status' => 404,
                'message' => 'Page non trouvée',
            ];
        }

        if ($page->trashed()) {
            return $this->resolveTrashedPage($page);
        }

        if (! $page->isPublished()) {
            return [
                'resolution' => self::RESOLUTION_NOT_FOUND,
                'http_status' => 404,
                'message' => 'Page non trouvée',
            ];
        }

        return [
            'resolution' => self::RESOLUTION_PAGE,
            'http_status' => 200,
            'page' => $page,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toJsonResponsePayload(array $resolution): array
    {
        if ($resolution['resolution'] === self::RESOLUTION_PAGE) {
            return (new PageResource($resolution['page']))->resolve();
        }

        $payload = [
            'resolution' => $resolution['resolution'],
            'message' => $resolution['message'] ?? null,
        ];

        if (isset($resolution['redirect'])) {
            $payload['redirect'] = $resolution['redirect'];
        }

        return array_filter($payload, fn ($value) => $value !== null);
    }

    public function redirectLocationHeader(array $resolution): ?string
    {
        if (($resolution['resolution'] ?? null) !== self::RESOLUTION_REDIRECT) {
            return null;
        }

        return $resolution['redirect']['location'] ?? null;
    }

    protected function findPageBySlugIncludingTrashed(string $slug): ?Page
    {
        if ($slug === 'home' || $slug === '') {
            return Page::withTrashed()->where('type', 'home')->first();
        }

        return Page::withTrashed()->where('slug', $slug)->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveTrashedPage(Page $page): array
    {
        $responseType = $page->deleted_response_type;

        if (! $responseType instanceof DeletedPageResponseType) {
            return [
                'resolution' => self::RESOLUTION_GONE,
                'http_status' => 410,
                'message' => 'Page supprimée',
            ];
        }

        return match ($responseType) {
            DeletedPageResponseType::NotFound => [
                'resolution' => self::RESOLUTION_NOT_FOUND,
                'http_status' => 404,
                'message' => 'Page non trouvée',
            ],
            DeletedPageResponseType::Gone => [
                'resolution' => self::RESOLUTION_GONE,
                'http_status' => 410,
                'message' => 'Page supprimée',
            ],
            DeletedPageResponseType::RedirectToPage => $this->resolveRedirectToPage($page),
            DeletedPageResponseType::RedirectToUrl => $this->resolveRedirectToUrl($page),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveRedirectToPage(Page $page): array
    {
        $target = $page->redirectTargetPage;

        if (! $target || $target->trashed() || ! $target->isPublished()) {
            return [
                'resolution' => self::RESOLUTION_GONE,
                'http_status' => 410,
                'message' => 'Page supprimée',
            ];
        }

        $location = $target->isHome() ? '/' : '/' . ltrim($target->slug, '/');

        return [
            'resolution' => self::RESOLUTION_REDIRECT,
            'http_status' => 301,
            'message' => 'Redirection vers une autre page',
            'redirect' => [
                'type' => 'page',
                'slug' => $target->isHome() ? 'home' : $target->slug,
                'location' => $location,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveRedirectToUrl(Page $page): array
    {
        $url = $page->redirect_target_url;

        if (blank($url)) {
            return [
                'resolution' => self::RESOLUTION_GONE,
                'http_status' => 410,
                'message' => 'Page supprimée',
            ];
        }

        return [
            'resolution' => self::RESOLUTION_REDIRECT,
            'http_status' => 301,
            'message' => 'Redirection vers une URL externe',
            'redirect' => [
                'type' => 'url',
                'url' => $url,
                'location' => $url,
            ],
        ];
    }
}
