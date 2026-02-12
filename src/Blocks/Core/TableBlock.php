<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class TableBlock implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'table';
    }

    public static function make(): Block
    {
        return Block::make('table')
            ->label('Tableau')
            ->icon('heroicon-o-table-cells')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Repeater::make('columns')
                    ->label('Colonnes (en-têtes)')
                    ->schema([
                        TextInput::make('label')
                            ->label('En-tête de colonne')
                            ->required()
                            ->maxLength(100)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(15)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Colonne')
                    ->columnSpanFull(),

                Repeater::make('rows')
                    ->label('Lignes')
                    ->schema([
                        Repeater::make('cells')
                            ->label('Cellules')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Valeur')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ])
                            ->minItems(1)
                            ->maxItems(15)
                            ->defaultItems(3)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(50)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => implode(' | ', array_map(fn ($c) => is_array($c) ? ($c['value'] ?? '') : '', $state['cells'] ?? [])) ?: 'Ligne')
                    ->columnSpanFull(),

                TextInput::make('footnote')
                    ->label('Note de bas de tableau')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $columns = [];
        foreach ($data['columns'] ?? [] as $col) {
            if (is_array($col) && !empty($col['label'])) {
                $columns[] = $col['label'];
            }
        }

        $rows = [];
        foreach ($data['rows'] ?? [] as $row) {
            if (!is_array($row) || empty($row['cells'])) {
                continue;
            }
            $cells = [];
            foreach ($row['cells'] as $cell) {
                if (is_array($cell) && array_key_exists('value', $cell)) {
                    $cells[] = $cell['value'];
                } elseif (is_string($cell)) {
                    $cells[] = $cell;
                }
            }
            $rows[] = $cells;
        }

        return [
            'type' => 'table',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'columns' => $columns,
            'rows' => $rows,
            'footnote' => $data['footnote'] ?? '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre',
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
                'name' => 'columns',
                'label' => 'Colonnes',
                'type' => 'array',
                'required' => true,
                'description' => 'Liste des en-têtes de colonnes',
                'items' => [
                    [
                        'name' => 'label',
                        'type' => 'string',
                        'required' => true,
                        'max_length' => 100,
                    ],
                ],
            ],
            [
                'name' => 'rows',
                'label' => 'Lignes',
                'type' => 'array',
                'required' => true,
                'description' => 'Liste de lignes, chaque ligne est un tableau de cellules',
                'items' => [
                    [
                        'name' => 'cells',
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            [
                                'name' => 'value',
                                'type' => 'string',
                                'required' => true,
                                'max_length' => 500,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'footnote',
                'label' => 'Note de bas de tableau',
                'type' => 'string',
                'required' => false,
                'max_length' => 500,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Comparatif materiaux',
            'description' => 'Tableau comparatif des caracteristiques.',
            'columns' => [
                ['label' => 'Materiau'],
                ['label' => 'Durabilite'],
                ['label' => 'Prix'],
            ],
            'rows' => [
                [
                    'cells' => [
                        ['value' => 'Bois'],
                        ['value' => 'Excellente'],
                        ['value' => 'Modere'],
                    ],
                ],
                [
                    'cells' => [
                        ['value' => 'Aluminium'],
                        ['value' => 'Tres bonne'],
                        ['value' => 'Eleve'],
                    ],
                ],
            ],
            'footnote' => 'Prix indicatifs selon fournisseurs.',
        ];
    }
}
