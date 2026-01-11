<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;

class CTABlock implements BlockInterface
{
    use HasMediaTransformation;

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

    public static function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'simple';

        $transformed = [
            'type' => 'cta',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'variant' => $variant,
            'cta_text' => $data['cta_text'] ?? '',
            'cta_link' => $data['cta_link'] ?? '',
        ];

        // Champs spécifiques au variant "hero"
        if ($variant === 'hero') {
            if (!empty($data['background_image'])) {
                $transformed['background_image'] = static::transformImageUrl($data['background_image']);
            }

            if (!empty($data['phone_number'])) {
                $transformed['phone_number'] = $data['phone_number'];
            }
        }

        // Champs spécifiques au variant "subscription"
        if ($variant === 'subscription' && !empty($data['secondary_cta_text'])) {
            $transformed['secondary_cta_text'] = $data['secondary_cta_text'];
        }

        return $transformed;
    }
}






