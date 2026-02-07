<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class TarifsBlock implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'tarifs';
    }

    public static function make(): Block
    {
        return Block::make('tarifs')
            ->label('Tarifs')
            ->icon('heroicon-o-currency-euro')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre principal')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Repeater::make('plans')
                    ->label('Offres tarifaires')
                    ->schema([
                        TextInput::make('nom')
                            ->label('Nom de l\'offre')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('prix')
                            ->label('Prix')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Ex: 490 € HT, 39 € HT/mois')
                            ->columnSpanFull(),

                        TextInput::make('prix_prefixe')
                            ->label('Préfixe prix')
                            ->default('À partir de')
                            ->maxLength(100)
                            ->helperText('Ex: À partir de, Dès, Forfait')
                            ->columnSpanFull(),

                        TextInput::make('periode')
                            ->label('Période')
                            ->maxLength(100)
                            ->helperText('Ex: /mois, /session, /an')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(600)
                            ->columnSpanFull(),

                        Repeater::make('points')
                            ->label('Points inclus')
                            ->schema([
                                TextInput::make('texte')
                                    ->label('Point')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->minItems(1)
                            ->maxItems(20)
                            ->defaultItems(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['texte'] ?? 'Point')
                            ->columnSpanFull(),

                        Toggle::make('mise_en_avant')
                            ->label('Mettre en avant cette offre')
                            ->default(false)
                            ->columnSpanFull(),

                        TextInput::make('bouton_texte')
                            ->label('Texte du bouton')
                            ->default('Choisir cette offre')
                            ->maxLength(100)
                            ->columnSpanFull(),

                        TextInput::make('bouton_lien')
                            ->label('Lien du bouton')
                            ->maxLength(255)
                            ->helperText('URL, chemin (ex: /contactez-nous) ou ancre (ex: #devis)')
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(12)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['nom'] ?? 'Offre')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'tarifs',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'plans' => static::transformPlans($data['plans'] ?? []),
        ];
    }

    protected static function transformPlans(array $plans): array
    {
        return array_map(function ($plan) {
            if (!is_array($plan)) {
                return $plan;
            }

            $points = $plan['points'] ?? [];
            $normalizedPoints = [];
            foreach ($points as $point) {
                if (is_array($point) && !empty($point['texte'])) {
                    $normalizedPoints[] = $point['texte'];
                } elseif (is_string($point) && trim($point) !== '') {
                    $normalizedPoints[] = $point;
                }
            }

            return [
                'nom' => $plan['nom'] ?? '',
                'prix' => $plan['prix'] ?? '',
                'prix_prefixe' => $plan['prix_prefixe'] ?? 'À partir de',
                'periode' => $plan['periode'] ?? '',
                'description' => $plan['description'] ?? '',
                'points' => $normalizedPoints,
                'mise_en_avant' => (bool) ($plan['mise_en_avant'] ?? false),
                'bouton_texte' => $plan['bouton_texte'] ?? 'Choisir cette offre',
                'bouton_lien' => $plan['bouton_lien'] ?? '',
            ];
        }, $plans);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre principal',
                'type' => 'string',
                'required' => false,
                'max_length' => 200,
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'string',
                'required' => false,
                'max_length' => 500,
            ],
            [
                'name' => 'plans',
                'label' => 'Offres tarifaires',
                'type' => 'array',
                'required' => true,
                'items' => [
                    [
                        'name' => 'nom',
                        'type' => 'string',
                        'required' => true,
                        'max_length' => 200,
                    ],
                    [
                        'name' => 'prix',
                        'type' => 'string',
                        'required' => true,
                        'max_length' => 100,
                    ],
                    [
                        'name' => 'prix_prefixe',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 100,
                        'default' => 'À partir de',
                    ],
                    [
                        'name' => 'periode',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 100,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 600,
                    ],
                    [
                        'name' => 'points',
                        'type' => 'array',
                        'required' => false,
                        'items' => [
                            [
                                'name' => 'texte',
                                'type' => 'string',
                                'required' => true,
                                'max_length' => 255,
                            ],
                        ],
                    ],
                    [
                        'name' => 'mise_en_avant',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                    ],
                    [
                        'name' => 'bouton_texte',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 100,
                        'default' => 'Choisir cette offre',
                    ],
                    [
                        'name' => 'bouton_lien',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 255,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Nos tarifs',
            'description' => 'Choisissez la formule adaptee a votre besoin.',
            'plans' => [
                [
                    'nom' => 'Essentiel',
                    'prix_prefixe' => 'A partir de',
                    'prix' => '990 EUR HT',
                    'periode' => '',
                    'description' => 'Ideal pour demarrer rapidement.',
                    'points' => [
                        ['texte' => 'Site vitrine 5 pages'],
                        ['texte' => 'Base SEO integree'],
                        ['texte' => 'Support email'],
                    ],
                    'mise_en_avant' => false,
                    'bouton_texte' => 'Choisir Essentiel',
                    'bouton_lien' => '/contactez-nous',
                ],
                [
                    'nom' => 'Croissance',
                    'prix_prefixe' => 'A partir de',
                    'prix' => '1490 EUR HT',
                    'periode' => '',
                    'description' => 'Pour accelerer acquisition et conversion.',
                    'points' => [
                        ['texte' => 'Tout Essentiel'],
                        ['texte' => 'Pages conversion avancees'],
                        ['texte' => 'Optimisation SEO renforcee'],
                    ],
                    'mise_en_avant' => true,
                    'bouton_texte' => 'Choisir Croissance',
                    'bouton_lien' => '/contactez-nous',
                ],
            ],
        ];
    }
}
