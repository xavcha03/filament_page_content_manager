<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class CTABlock implements BlockInterface
{
    public static function getType(): string
    {
        return 'cta';
    }

    public static function make(): Block
    {
        return Block::make('cta')
            ->label('Appel à l\'action (CTA)')
            ->icon('heroicon-o-arrow-right-circle')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TextInput::make('cta_text')
                    ->label('Texte du bouton')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('cta_link')
                    ->label('Lien du bouton')
                    ->required()
                    ->helperText('URL, chemin (ex: /devis) ou ancre (ex: #section)')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'cta',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'cta_text' => $data['cta_text'] ?? '',
            'cta_link' => $data['cta_link'] ?? '',
        ];
    }
}






