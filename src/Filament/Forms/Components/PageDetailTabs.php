<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Schemas\Components;

class PageDetailTabs
{
    /**
     * Retourne directement les onglets SEO et Content pour une ressource Filament.
     * 
     * Utilisation recommandée dans une ressource Filament :
     * 
     * ```php
     * public static function form(Schema $schema): Schema
     * {
     *     return $schema
     *         ->components([
     *             Components\Tabs::make('tabs')
     *                 ->tabs([
     *                     Components\Tabs\Tab::make('general')
     *                         ->label('Général')
     *                         ->schema([
     *                             Forms\Components\TextInput::make('name')
     *                                 ->label('Nom')
     *                                 ->required(),
     *                         ]),
     *                     ...PageDetailTabs::tabs(),
     *                 ]),
     *         ]);
     * }
     * ```
     * 
     * Alternative : Utiliser les onglets individuellement
     * 
     * ```php
     * use Xavcha\PageContentManager\Filament\Forms\Components\SeoTab;
     * use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;
     * 
     * Components\Tabs::make('tabs')
     *     ->tabs([
     *         Components\Tabs\Tab::make('general')
     *             ->label('Général')
     *             ->schema([...]),
     *         SeoTab::make(),
     *         ContentTab::make(),
     *     ]),
     * ```
     *
     * @return array<int, Components\Tabs\Tab>
     */
    public static function tabs(): array
    {
        return [
            SeoTab::make(),
            ContentTab::make(),
        ];
    }

    /**
     * Crée une instance pour utilisation fluide (déprécié, utilisez tabs()).
     *
     * @deprecated Utilisez PageDetailTabs::tabs() à la place
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Convertit en tableau d'onglets pour utilisation dans un formulaire Filament.
     *
     * @deprecated Utilisez PageDetailTabs::tabs() à la place
     * @return array<int, Components\Tabs\Tab>
     */
    public function toArray(): array
    {
        return self::tabs();
    }
}

