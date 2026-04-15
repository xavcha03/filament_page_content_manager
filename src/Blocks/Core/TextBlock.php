<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class TextBlock implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'text';
    }

    public static function make(): Block
    {
        return Block::make('text')
            ->label('Texte')
            ->icon('heroicon-o-document-text')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre (affiché en H2)')
                    ->maxLength(200)
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Contenu')
                    ->helperText("Le titre du bloc est déjà affiché comme un titre principal (H2) sur le site. Utilisez les niveaux de titres du menu « Titres » pour structurer le contenu (sous-titres / intertitres), ou « Texte normal » pour du paragraphe.")
                    ->required()
                    ->toolbarButtons([
                        // Inline (le plus utilisé)
                        ['bold', 'italic', 'underline', 'strike', 'link'],
                        // “Typo” (dropdown)
                        [
                            ToolbarButtonGroup::make('Typo', [
                                'lead',
                                'small',
                                'code',
                                'highlight',
                                'textColor',
                                'clearFormatting',
                            ])->textualButtons(),
                        ],
                        // Titres / paragraphe (dropdown)
                        [
                            ToolbarButtonGroup::make('Titres', [
                                'paragraph',
                                'h3',
                                'h4',
                                'h5',
                                'h6',
                            ])->textualButtons(),
                        ],
                        // Alignement (dropdown)
                        [
                            ToolbarButtonGroup::make('Align', [
                                'alignStart',
                                'alignCenter',
                                'alignEnd',
                                'alignJustify',
                            ]),
                        ],
                        // Structure
                        ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                        // Table (insertion). Les actions avancées restent en floating toolbar (contextuelle dans un tableau).
                        ['table'],
                        // Layout (dropdown)
                        [
                            ToolbarButtonGroup::make('Layout', [
                                'grid',
                                'gridDelete',
                                'details',
                            ])->textualButtons(),
                        ],
                        // Historique
                        ['undo', 'redo'],
                    ])
                    ->floatingToolbars([
                        'paragraph' => [
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'code',
                            'highlight',
                            'textColor',
                            'clearFormatting',
                            'link',
                        ],
                        'heading' => [
                            'paragraph',
                            'h3',
                            'h4',
                            'h5',
                            'h6',
                        ],
                        'table' => [
                            'tableAddColumnBefore',
                            'tableAddColumnAfter',
                            'tableDeleteColumn',
                            'tableAddRowBefore',
                            'tableAddRowAfter',
                            'tableDeleteRow',
                            'tableMergeCells',
                            'tableSplitCell',
                            'tableToggleHeaderRow',
                            'tableToggleHeaderCell',
                            'tableDelete',
                        ],
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'text',
            'titre' => $data['titre'] ?? '',
            'content' => $data['content'] ?? '',
        ];
    }

    /**
     * Retourne les champs du bloc pour MCP.
     *
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
                'description' => 'Le titre du bloc de texte',
                'max_length' => 200,
            ],
            [
                'name' => 'content',
                'label' => 'Contenu',
                'type' => 'string',
                'required' => true,
                'description' => 'Le contenu du bloc (format HTML/rich text)',
            ],
        ];
    }

    /**
     * Retourne un exemple de données pour le bloc.
     *
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Titre de la section',
            'content' => '<p>Contenu de la section avec du texte formaté.</p>',
        ];
    }
}






