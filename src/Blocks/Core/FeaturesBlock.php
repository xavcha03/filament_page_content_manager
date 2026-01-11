<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class FeaturesBlock implements BlockInterface
{
    public static function getType(): string
    {
        return 'features';
    }

    public static function make(): Block
    {
        return Block::make('features')
            ->label('Features / Avantages')
            ->icon('heroicon-o-sparkles')
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

                Select::make('columns')
                    ->label('Nombre de colonnes')
                    ->options([
                        '3' => '3 colonnes',
                        '4' => '4 colonnes',
                        '6' => '6 colonnes',
                    ])
                    ->default('3')
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->label('Éléments')
                    ->schema([
                        TextInput::make('icone')
                            ->label('Icône')
                            ->helperText('Nom de l\'icône Heroicons (ex: star, check, bolt)')
                            ->maxLength(50)
                            ->columnSpanFull(),

                        TextInput::make('titre')
                            ->label('Titre')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        Textarea::make('texte')
                            ->label('Texte')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(12)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['titre'] ?? 'Élément')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'features',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'columns' => (int) ($data['columns'] ?? 3),
            'items' => static::transformItems($data['items'] ?? []),
        ];
    }

    protected static function transformItems(array $items): array
    {
        return array_map(function ($item) {
            if (!is_array($item)) {
                return $item;
            }

            return [
                'icone' => $item['icone'] ?? '',
                'titre' => $item['titre'] ?? '',
                'texte' => $item['texte'] ?? '',
            ];
        }, $items);
    }
}


