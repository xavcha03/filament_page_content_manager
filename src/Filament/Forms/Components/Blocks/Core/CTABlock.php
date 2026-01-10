<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class CTABlock
{
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

                Select::make('variant')
                    ->label('Variante')
                    ->options([
                        'simple' => 'Simple (fond bleu)',
                        'hero' => 'Hero (avec image de fond)',
                        'subscription' => 'Subscription (avec bouton secondaire)',
                    ])
                    ->default('simple')
                    ->live()
                    ->columnSpanFull(),

                // Champs pour le bouton principal
                TextInput::make('cta_text')
                    ->label('Texte du bouton principal')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('cta_link')
                    ->label('Lien du bouton principal')
                    ->required()
                    ->helperText('URL, chemin (ex: /devis) ou ancre (ex: #section)')
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Champs pour variant "hero"
                FileUpload::make('background_image')
                    ->label('Image de fond')
                    ->image()
                    ->disk('public')
                    ->directory('cta')
                    ->visibility('public')
                    ->visible(fn ($get) => $get('variant') === 'hero')
                    ->columnSpanFull(),

                TextInput::make('phone_number')
                    ->label('Numéro de téléphone')
                    ->maxLength(50)
                    ->helperText('Affiché sous le bouton (pour variant hero)')
                    ->visible(fn ($get) => $get('variant') === 'hero')
                    ->columnSpanFull(),

                // Champs pour variant "subscription"
                TextInput::make('secondary_cta_text')
                    ->label('Texte du bouton secondaire')
                    ->maxLength(100)
                    ->visible(fn ($get) => $get('variant') === 'subscription')
                    ->columnSpanFull(),
            ]);
    }
}



