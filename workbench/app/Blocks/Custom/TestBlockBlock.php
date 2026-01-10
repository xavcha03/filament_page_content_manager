<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;


class TestBlockBlock implements BlockInterface
{


    public static function getType(): string
    {
        return 'test-block';
    }

    public static function getOrder(): int
    {
        return 50;
    }

    public static function getGroup(): ?string
    {
        return 'content';
    }

    public static function make(): Block
    {
        return Block::make('test-block')
            ->label('Test block')
            ->icon('heroicon-o-document-text')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                // Ajoutez vos champs ici
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'test-block',
            'titre' => $data['titre'] ?? '',
            // Ajoutez votre logique de transformation ici
        ];
    }
}

