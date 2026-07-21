<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Blocks;

/**
 * Résout l'URL d'une image de preview pour un type de bloc.
 *
 * Convention : fichier `{type}.webp` dans
 * - `public/images/block-previews/` (app, priorité)
 * - `resources/images/block-previews/` (app)
 * - `resources/images/block-previews/` du package
 *
 * Si le bloc définit `getPreviewImageUrl(): ?string`, cette URL est utilisée en premier.
 */
class BlockPreviewResolver
{
    public static function packagePreviewsDirectory(): string
    {
        return dirname(__DIR__, 2) . '/resources/images/block-previews';
    }

    /**
     * @param  class-string|null  $blockClass
     */
    public static function url(string $type, ?string $blockClass = null): ?string
    {
        if (is_string($blockClass) && method_exists($blockClass, 'getPreviewImageUrl')) {
            try {
                $customUrl = $blockClass::getPreviewImageUrl();
                if (is_string($customUrl) && $customUrl !== '') {
                    return $customUrl;
                }
            } catch (\Throwable) {
                // Ignorer et continuer avec la convention fichier
            }
        }

        $path = self::resolveFilePath($type);

        if ($path === null) {
            return null;
        }

        $publicPath = public_path("images/block-previews/{$type}.webp");
        if ($path === $publicPath) {
            return asset("images/block-previews/{$type}.webp");
        }

        return url("_page-content-manager/block-previews/{$type}.webp");
    }

    public static function resolveFilePath(string $type): ?string
    {
        if (! preg_match('/^[a-z0-9_]+$/', $type)) {
            return null;
        }

        $candidates = [
            public_path("images/block-previews/{$type}.webp"),
            resource_path("images/block-previews/{$type}.webp"),
            self::packagePreviewsDirectory() . "/{$type}.webp",
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function exists(string $type): bool
    {
        return self::resolveFilePath($type) !== null;
    }
}
