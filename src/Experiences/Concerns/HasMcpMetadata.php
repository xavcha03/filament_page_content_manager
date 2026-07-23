<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Experiences\Concerns;

/**
 * Métadonnées MCP optionnelles pour une Experience.
 * Structure figée : MCP ne peut que lire le schéma et merger des valeurs.
 */
trait HasMcpMetadata
{
    public static function getMcpDescription(): string
    {
        try {
            return static::getLabel();
        } catch (\Throwable) {
            return static::getKey();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getMcpExample(): ?array
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpMetadata(): array
    {
        return [];
    }
}
