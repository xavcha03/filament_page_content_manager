<?php

namespace Xavcha\PageContentManager\Services;

use Xavcha\PageContentManager\Models\Page;

class PagePreviewService
{
    public function isEnabled(): bool
    {
        return (bool) config('page-content-manager.preview.enabled', true);
    }

    public function createToken(Page $page): string
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('La prévisualisation est désactivée.');
        }

        if ($page->trashed()) {
            throw new \RuntimeException('Impossible de prévisualiser une page dans la corbeille.');
        }

        $payload = [
            'pid' => $page->id,
            'slug' => $page->isHome() ? 'home' : $page->slug,
            'exp' => now()->addMinutes($this->ttlMinutes())->timestamp,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return $encodedPayload . '.' . $signature;
    }

    public function buildFrontendPreviewUrl(Page $page): string
    {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = rtrim((string) config('page-content-manager.preview.path', '/preview'), '/');
        $slug = $page->isHome() ? 'home' : ltrim($page->slug, '/');
        $token = $this->createToken($page);

        return $baseUrl . $path . '/' . $slug . '?preview_token=' . urlencode($token);
    }

    /**
     * Résout une page via token de prévisualisation (brouillon / planifié autorisés).
     */
    public function resolvePageFromToken(string $token, string $slug): ?Page
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $payload = $this->decodeToken($token);

        if ($payload === null) {
            return null;
        }

        if (! $this->slugMatches($slug, (string) ($payload['slug'] ?? ''))) {
            return null;
        }

        $page = Page::query()->find($payload['pid'] ?? null);

        if (! $page || $page->trashed()) {
            return null;
        }

        if (! $this->slugMatches($slug, $page->isHome() ? 'home' : $page->slug)) {
            return null;
        }

        return $page;
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('page-content-manager.preview.ttl_minutes', 60));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;

        if (! hash_equals($this->sign($encodedPayload), $signature)) {
            return null;
        }

        try {
            $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['exp'], $payload['pid'])) {
            return null;
        }

        if ((int) $payload['exp'] < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    protected function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret());
    }

    protected function secret(): string
    {
        $secret = (string) config('page-content-manager.preview.secret', '');

        if ($secret !== '') {
            return $secret;
        }

        return (string) config('app.key');
    }

    protected function slugMatches(string $requestedSlug, string $tokenSlug): bool
    {
        $requested = $this->normalizeSlug($requestedSlug);
        $expected = $this->normalizeSlug($tokenSlug);

        return $requested === $expected;
    }

    protected function normalizeSlug(string $slug): string
    {
        $slug = trim($slug, '/');

        return $slug === '' ? 'home' : $slug;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
