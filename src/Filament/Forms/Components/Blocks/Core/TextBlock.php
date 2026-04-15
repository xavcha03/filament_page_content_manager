<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

class TextBlock
{
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
                        'h1',
                        'h2',
                        'h3',
                        'lead',
                        'small',
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'code',
                        'highlight',
                        'textColor',
                        'clearFormatting',
                        'alignJustify',
                        'bulletList',
                        'orderedList',
                        'link',
                        'blockquote',
                        'horizontalRule',
                        'details',
                        'grid',
                        'gridDelete',
                        'codeBlock',
                        'table',
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}






