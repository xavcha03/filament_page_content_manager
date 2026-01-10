<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Forms;
use Filament\Schemas\Components;

class SeoTab
{
    /**
     * Crée un onglet SEO réutilisable.
     *
     * @return Components\Tabs\Tab
     */
    public static function make(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('seo')
            ->label('SEO')
            ->schema([
                Forms\Components\TextInput::make('seo_title')
                    ->label('Titre SEO')
                    ->maxLength(255)
                    ->helperText('Titre pour les moteurs de recherche (optionnel)'),
                Forms\Components\Textarea::make('seo_description')
                    ->label('Description SEO')
                    ->rows(3)
                    ->helperText('Description pour les moteurs de recherche (optionnel)'),
            ]);
    }
}



