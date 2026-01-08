<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class FAQBlock
{
    public static function make(): Block
    {
        return Block::make('faq')
            ->label('Section FAQ')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre principal')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Repeater::make('faqs')
                    ->label('Questions fréquentes')
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->required()
                            ->maxLength(300)
                            ->columnSpanFull(),

                        Textarea::make('answer')
                            ->label('Réponse')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(50)
                    ->defaultItems(5)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'Question')
                    ->columnSpanFull(),
            ]);
    }
}

