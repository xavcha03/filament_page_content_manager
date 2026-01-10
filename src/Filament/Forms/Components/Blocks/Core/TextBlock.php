<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Textarea;
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

                Textarea::make('content')
                    ->label('Contenu')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ]);
    }
}



