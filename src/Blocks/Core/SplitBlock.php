<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class SplitBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'split';
    }

    public static function make(): Block
    {
        return Block::make('split')
            ->label('Texte + Image (Split)')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('texte')
                    ->label('Texte')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),

                Select::make('variant')
                    ->label('Variante')
                    ->options([
                        'left' => 'Image à gauche',
                        'right' => 'Image à droite',
                        'feature' => 'Feature (mise en avant)',
                    ])
                    ->default('left')
                    ->live()
                    ->columnSpanFull(),

                Select::make('background')
                    ->label('Fond')
                    ->options([
                        'light' => 'Clair',
                        'dark' => 'Sombre',
                    ])
                    ->default('light')
                    ->visible(fn ($get) => $get('variant') !== 'feature')
                    ->columnSpanFull(),

                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->collection('split_images')
                    ->acceptedFileTypes(['image/*'])
                    ->single()
                    ->required()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->columnSpanFull(),

                TextInput::make('image_alt')
                    ->label('Texte alternatif de l\'image')
                    ->maxLength(200)
                    ->columnSpanFull(),

                // Bouton optionnel
                TextInput::make('bouton.texte')
                    ->label('Texte du bouton')
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('bouton.lien')
                    ->label('Lien du bouton')
                    ->helperText('URL, chemin (ex: /devis) ou ancre (ex: #section)')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'left';
        $background = $data['background'] ?? 'light';

        $transformed = [
            'type' => 'split',
            'titre' => $data['titre'] ?? '',
            'texte' => $data['texte'] ?? '',
            'variant' => $variant,
            'background' => $variant === 'feature' ? 'light' : $background,
        ];

        // Gestion de l'image
        if (!empty($data['image_id'])) {
            $imageData = static::getMediaFileData($data['image_id']);
            if ($imageData) {
                $transformed['image_url'] = $imageData['url'];
                $transformed['image_width'] = $imageData['width'];
                $transformed['image_height'] = $imageData['height'];
                
                // Utiliser alt_text du MediaFile si pas d'alt personnalisé
                if (!empty($data['image_alt'])) {
                    $transformed['image_alt'] = $data['image_alt'];
                } elseif (!empty($imageData['alt_text'])) {
                    $transformed['image_alt'] = $imageData['alt_text'];
                }
            }
        }

        // Bouton optionnel
        if (!empty($data['bouton'])) {
            $button = $data['bouton'];
            
            if (is_array($button) && !empty($button['texte']) && !empty($button['lien'])) {
                $transformed['bouton'] = [
                    'texte' => $button['texte'],
                    'lien' => $button['lien'],
                ];
            }
        }

        return $transformed;
    }
}


