<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class TextBlock implements BlockInterface
{
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

                Textarea::make('content')
                    ->label('Contenu')
                    ->required()
                    ->rows(6)
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
}



