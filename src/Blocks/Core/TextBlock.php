<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
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
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Contenu')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock',
                        'h2',
                        'h3',
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






