<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactFormBlock
{
    public static function make(): Block
    {
        return Block::make('contact_form')
            ->label('Formulaire de contact')
            ->icon('heroicon-o-envelope')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->default('Contactez-nous')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TextInput::make('success_message')
                    ->label('Message de confirmation')
                    ->maxLength(500)
                    ->default('Merci pour votre message. Nous vous répondrons dans les plus brefs délais.')
                    ->columnSpanFull(),
            ]);
    }
}





