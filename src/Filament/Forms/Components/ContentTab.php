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
     * @return Components\Tabs\Tab
     */
    public static function make(): Components\Tabs\Tab
    {
        $blocks = self::getBlocks();

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
     * Récupère les blocs depuis le registry ou la configuration (rétrocompatibilité).
     *
     * @return array
     */
    protected static function getBlocks(): array
    {
        $registry = app(BlockRegistry::class);
        $allBlocks = $registry->all();
        $blocks = [];

        // Utiliser le registry pour charger les blocs
        foreach ($allBlocks as $type => $blockClass) {
            if (method_exists($blockClass, 'make')) {
                $blocks[] = $blockClass::make();
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
                        $blocks[] = $className::make();
                    }
                }
            }

            // Ajouter les blocs custom
            if (isset($config['custom']) && is_array($config['custom'])) {
                foreach ($config['custom'] as $key => $blockClass) {
                    $className = is_string($key) ? $blockClass : $blockClass;
                    if (is_string($className) && class_exists($className) && method_exists($className, 'make')) {
                        $blocks[] = $className::make();
                    }
                }
            }
        }

        return $blocks;
    }
}

