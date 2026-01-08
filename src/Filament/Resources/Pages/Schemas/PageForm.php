<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Schemas;

use Filament\Forms;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        // Récupérer les blocs depuis la config
        $blocks = self::getBlocks();

        return $schema
            ->components([
                Components\Tabs::make('page_tabs')
                    ->tabs([
                        Components\Tabs\Tab::make('general')
                            ->label('Général')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titre')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Générer le slug automatiquement depuis le titre seulement si le slug est vide
                                        // Cela évite d'écraser un slug modifié manuellement
                                        $currentSlug = $get('slug');
                                        if (empty($currentSlug)) {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn ($record) => $record && $record->exists)
                                    ->hidden(fn ($record) => $record && $record?->isHome())
                                    ->helperText('L\'URL de la page (ex: /contact, /about). Généré automatiquement depuis le titre. Non modifiable après création.'),
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'home' => 'Home',
                                        'standard' => 'Standard',
                                    ])
                                    ->required()
                                    ->default('standard')
                                    ->disabled(fn ($record) => $record && $record->exists),
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'scheduled' => 'Planifié',
                                        'published' => 'Publié',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->live(),
                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Date de publication')
                                    ->native(false)
                                    ->required(fn ($get) => $get('status') === 'scheduled')
                                    ->visible(fn ($get) => in_array($get('status'), ['scheduled', 'published']))
                                    ->helperText(fn ($get) => match($get('status')) {
                                        'scheduled' => 'La date de publication est obligatoire pour les pages planifiées.',
                                        'published' => 'Laissez vide pour publier immédiatement, ou choisissez une date pour planifier la publication.',
                                        default => null,
                                    }),
                            ]),
                        Components\Tabs\Tab::make('seo')
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
                            ]),
                        Components\Tabs\Tab::make('content')
                            ->label('Contenu')
                            ->schema([
                                Forms\Components\Builder::make('content.sections')
                                    ->label('Sections')
                                    ->blocks($blocks)
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ]),
                    ])
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

