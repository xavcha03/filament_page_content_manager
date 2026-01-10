<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Forms;
use Filament\Schemas\Components;
use Xavcha\PageContentManager\Blocks\BlockRegistry;

class ContentTab
{
    /**
     * Crée un onglet Content réutilisable avec le système de blocs.
     *
     * @param string $group Nom du groupe de blocs à utiliser (défaut: 'pages')
     * @return Components\Tabs\Tab
     */
    public static function make(string $group = 'pages'): Components\Tabs\Tab
    {
        $blocks = self::getBlocksForGroup($group);

        return Components\Tabs\Tab::make('content')
            ->label('Contenu')
            ->schema([
                Forms\Components\Builder::make('content.sections')
                    ->label('Sections')
                    ->blocks($blocks)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Récupère les blocs pour un groupe spécifique depuis la configuration.
     *
     * @param string $group Nom du groupe
     * @return array
     */
    protected static function getBlocksForGroup(string $group): array
    {
        $config = config('page-content-manager.block_groups', []);

        // Si le groupe existe dans la config, utiliser l'ordre défini
        if (isset($config[$group]['blocks']) && is_array($config[$group]['blocks'])) {
            $blocks = [];
            $disabledBlocks = config('page-content-manager.disabled_blocks', []);

            foreach ($config[$group]['blocks'] as $blockClass) {
                if (!is_string($blockClass) || !class_exists($blockClass)) {
                    continue;
                }

                if (!method_exists($blockClass, 'make')) {
                    continue;
                }

                // Vérifier que le bloc n'est pas désactivé
                try {
                    $type = $blockClass::getType();
                    if (in_array($type, $disabledBlocks, true)) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    // Si getType() échoue, ignorer le bloc
                    continue;
                }

                try {
                    $blocks[] = $blockClass::make();
                } catch (\Throwable $e) {
                    // Si make() échoue, ignorer le bloc
                    continue;
                }
            }

            return $blocks;
        }

        // Fallback : utiliser tous les blocs disponibles (comportement actuel)
        return self::getAllBlocks();
    }

    /**
     * Récupère tous les blocs disponibles depuis le registry (rétrocompatibilité).
     *
     * @return array
     */
    protected static function getAllBlocks(): array
    {
        $registry = app(BlockRegistry::class);
        $allBlocks = $registry->all();
        $blocks = [];

        // Utiliser le registry pour charger les blocs
        foreach ($allBlocks as $type => $blockClass) {
            if (method_exists($blockClass, 'make')) {
                try {
                    $blocks[] = $blockClass::make();
                } catch (\Throwable $e) {
                    // Ignorer les blocs qui ne peuvent pas être créés
                    continue;
                }
            }
        }

        // Rétrocompatibilité : si aucun bloc trouvé dans le registry, utiliser la config
        if (empty($blocks)) {
            $config = config('page-content-manager.blocks', []);

            // Ajouter les blocs core
            if (isset($config['core']) && is_array($config['core'])) {
                foreach ($config['core'] as $key => $blockClass) {
                    $className = is_string($key) ? $blockClass : $blockClass;
                    if (is_string($className) && class_exists($className) && method_exists($className, 'make')) {
                        try {
                            $blocks[] = $className::make();
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }
                }
            }

            // Ajouter les blocs custom
            if (isset($config['custom']) && is_array($config['custom'])) {
                foreach ($config['custom'] as $key => $blockClass) {
                    $className = is_string($key) ? $blockClass : $blockClass;
                    if (is_string($className) && class_exists($className) && method_exists($className, 'make')) {
                        try {
                            $blocks[] = $className::make();
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }
                }
            }
        }

        return $blocks;
    }
}

