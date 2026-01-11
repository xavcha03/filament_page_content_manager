<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class FAQBlock implements BlockInterface
{
    public static function getType(): string
    {
        return 'faq';
    }

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

    public static function transform(array $data): array
    {
        return [
            'type' => 'faq',
            'titre' => $data['titre'] ?? '',
            'faqs' => static::transformFAQs($data['faqs'] ?? []),
        ];
    }

    protected static function transformFAQs(array $faqs): array
    {
        return array_map(function ($faq) {
            if (!is_array($faq)) {
                return $faq;
            }

            return [
                'question' => $faq['question'] ?? '',
                'answer' => $faq['answer'] ?? '',
            ];
        }, $faqs);
    }
}





