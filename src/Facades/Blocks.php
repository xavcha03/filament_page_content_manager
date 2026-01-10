<?php

namespace Xavcha\PageContentManager\Facades;

use Illuminate\Support\Facades\Facade;
use Xavcha\PageContentManager\Blocks\BlockRegistry;

/**
 * Facade pour accéder facilement au BlockRegistry.
 *
 * @method static string|null get(string $type) Récupère un bloc par son type
 * @method static array all() Récupère tous les blocs enregistrés
 * @method static bool has(string $type) Vérifie si un bloc est enregistré
 * @method static void register(string $type, string $blockClass) Enregistre un bloc manuellement
 * @method static void clearCache() Invalide le cache des blocs
 *
 * @see \Xavcha\PageContentManager\Blocks\BlockRegistry
 */
class Blocks extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return BlockRegistry::class;
    }
}

