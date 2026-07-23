<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Schemas;

use Filament\Forms;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;
use Xavcha\PageContentManager\Filament\Forms\Components\ExperienceContentTab;
use Xavcha\PageContentManager\Filament\Forms\Components\SeoTab;
use Xavcha\PageContentManager\Models\Page;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
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
                                    ->disabled(fn ($record) => $record && $record->exists)
                                    ->helperText('Rôle de la page (home ou standard). Distinct du mode de contenu.'),
                                Forms\Components\Select::make('content_mode')
                                    ->label('Mode de contenu')
                                    ->options([
                                        Page::CONTENT_MODE_BLOCKS => 'Blocs',
                                        Page::CONTENT_MODE_EXPERIENCE => 'Experience',
                                    ])
                                    ->required()
                                    ->default(Page::CONTENT_MODE_BLOCKS)
                                    ->live()
                                    ->disabled(fn (): bool => app(ExperienceRegistry::class)->all() === [])
                                    ->helperText(function (): string {
                                        if (app(ExperienceRegistry::class)->all() === []) {
                                            return 'Aucune Experience enregistrée dans app/Experiences. Le mode reste Blocs.';
                                        }

                                        return 'Blocs : composition libre. Experience : formulaire fixe défini par le développeur. L\'autre contenu est conservé mais non utilisé par le frontend.';
                                    })
                                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                        if ($state !== Page::CONTENT_MODE_EXPERIENCE) {
                                            return;
                                        }

                                        $options = app(ExperienceRegistry::class)->options();
                                        if ($options === []) {
                                            $set('content_mode', Page::CONTENT_MODE_BLOCKS);
                                            $set('experience_key', null);

                                            return;
                                        }

                                        $current = $get('experience_key');
                                        if (blank($current) || ! array_key_exists((string) $current, $options)) {
                                            $set('experience_key', array_key_first($options));
                                            self::syncExperienceFieldsFromBag($set, $get, array_key_first($options));
                                        }
                                    }),
                                Forms\Components\Select::make('experience_key')
                                    ->label('Modèle d\'Experience')
                                    ->options(fn (): array => app(ExperienceRegistry::class)->options())
                                    ->required(fn (Get $get): bool => $get('content_mode') === Page::CONTENT_MODE_EXPERIENCE)
                                    ->visible(fn (Get $get): bool => $get('content_mode') === Page::CONTENT_MODE_EXPERIENCE)
                                    ->live()
                                    ->helperText('Changer de modèle conserve le contenu de chaque Experience séparément.')
                                    ->afterStateUpdated(function (?string $state, ?string $old, Set $set, Get $get): void {
                                        $bag = $get('experience_content');
                                        if (! is_array($bag)) {
                                            $bag = [];
                                        }

                                        if (is_string($old) && $old !== '') {
                                            $bag[$old] = $get('experience_fields') ?? [];
                                        }

                                        $set('experience_content', $bag);
                                        self::syncExperienceFieldsFromBag($set, $get, $state, $bag);
                                    }),
                                Forms\Components\Hidden::make('experience_content')
                                    ->default([]),
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
                                    ->displayFormat('d/m/Y H:i')
                                    ->required(fn ($get) => $get('status') === 'scheduled')
                                    ->visible(fn ($get) => in_array($get('status'), ['scheduled', 'published']))
                                    ->helperText(fn ($get) => match ($get('status')) {
                                        'scheduled' => 'La date de publication est obligatoire pour les pages planifiées.',
                                        'published' => 'Laissez vide pour publier immédiatement, ou choisissez une date pour planifier la publication.',
                                        default => null,
                                    }),
                            ]),
                        SeoTab::make(),
                        ContentTab::make('pages')
                            ->visible(fn (Get $get): bool => ($get('content_mode') ?? Page::CONTENT_MODE_BLOCKS) === Page::CONTENT_MODE_BLOCKS),
                        ExperienceContentTab::make()
                            ->visible(fn (Get $get): bool => ($get('content_mode') ?? Page::CONTENT_MODE_BLOCKS) === Page::CONTENT_MODE_EXPERIENCE),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $bag
     */
    protected static function syncExperienceFieldsFromBag(Set $set, Get $get, ?string $key, ?array $bag = null): void
    {
        if ($bag === null) {
            $bag = $get('experience_content');
            if (! is_array($bag)) {
                $bag = [];
            }
        }

        if (! is_string($key) || $key === '') {
            $set('experience_fields', []);

            return;
        }

        $set('experience_fields', is_array($bag[$key] ?? null) ? $bag[$key] : []);
    }
}
