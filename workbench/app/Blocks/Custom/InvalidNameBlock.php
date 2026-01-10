<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;


class InvalidNameBlock implements BlockInterface
{


    public static function getType(): string
    {
        return 'invalid-name';
    }

    public static function getOrder(): int
    {
        return 100;
    }

    public static function getGroup(): ?string
    {
        return 'other';
    }

    public static function make(): Block
    {
        return Block::make('invalid-name')
            ->label('Invalid name')
            ->icon('heroicon-o-cube')
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
            'type' => 'invalid-name',
            'titre' => $data['titre'] ?? '',
            // Ajoutez votre logique de transformation ici
        ];
    }
}

