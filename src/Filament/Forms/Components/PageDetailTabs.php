<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Schemas\Components;

class PageDetailTabs
{
    /**
     * Crée les onglets SEO et Content pour une ressource Filament.
     * 
     * Utilisation dans une ressource Filament :
     * 
     * ```php
     * public static function form(Schema $schema): Schema
     * {
     *     return $schema
     *         ->components([
     *             // Vos champs principaux ici
     *             Forms\Components\TextInput::make('name'),
     *             
     *             // Ajouter les onglets SEO et Content
     *             Components\Tabs::make('tabs')
     *                 ->tabs([
     *                     Components\Tabs\Tab::make('general')
     *                         ->label('Général')
     *                         ->schema([
     *                             // Vos champs principaux
     *                         ]),
     *                     ...PageDetailTabs::make()->toArray(),
     *                 ]),
     *         ]);
     * }
     * ```
     *
     * @return array
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Convertit en tableau d'onglets pour utilisation dans un formulaire Filament.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            SeoTab::make(),
            ContentTab::make(),
        ];
    }
}

