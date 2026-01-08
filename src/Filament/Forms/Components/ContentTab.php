<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Forms;
use Filament\Schemas\Components;

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
     * Récupère les blocs depuis la configuration.
     *
     * @return array
     */
    protected static function getBlocks(): array
    {
        $config = config('page-content-manager.blocks', []);
        $blocks = [];

        // Ajouter les blocs core
        if (isset($config['core']) && is_array($config['core'])) {
            foreach ($config['core'] as $key => $blockClass) {
                // Support pour array associatif (key => class) ou array indexé (class)
                $className = is_string($key) ? $blockClass : $blockClass;
                if (is_string($className) && class_exists($className) && method_exists($className, 'make')) {
                    $blocks[] = $className::make();
                }
            }
        }

        // Ajouter les blocs custom
        if (isset($config['custom']) && is_array($config['custom'])) {
            foreach ($config['custom'] as $key => $blockClass) {
                // Support pour array associatif (key => class) ou array indexé (class)
                $className = is_string($key) ? $blockClass : $blockClass;
                if (is_string($className) && class_exists($className) && method_exists($className, 'make')) {
                    $blocks[] = $className::make();
                }
            }
        }

        return $blocks;
    }
}

