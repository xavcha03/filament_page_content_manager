<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class HeroBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'hero';
    }

    public static function make(): Block
    {
        return Block::make('hero')
            ->label('Section Hero')
            ->icon('heroicon-o-photo')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre principal')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Select::make('variant')
                    ->label('Variante')
                    ->options([
                        'hero' => 'Hero standard',
                        'projects' => 'Hero projets (galerie)',
                    ])
                    ->default('hero')
                    ->live()
                    ->columnSpanFull(),

                // Champs pour variant "hero"
                MediaPickerUnified::make('image_fond_id')
                    ->label('Image de fond')
                    ->collection('hero_images')
                    ->acceptedFileTypes(['image/*'])
                    ->single()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->visible(fn ($get) => $get('variant') !== 'projects')
                    ->columnSpanFull(),

                TextInput::make('image_fond_alt')
                    ->label('Texte alternatif de l\'image de fond')
                    ->maxLength(200)
                    ->visible(fn ($get) => $get('variant') !== 'projects' && $get('image_fond_id'))
                    ->columnSpanFull(),

                // Champs pour variant "projects"
                MediaPickerUnified::make('images_ids')
                    ->label('Images de la galerie')
                    ->collection('hero_gallery')
                    ->acceptedFileTypes(['image/*'])
                    ->multiple(true)
                    ->minFiles(1)
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->visible(fn ($get) => $get('variant') === 'projects')
                    ->columnSpanFull(),

                // Bouton principal (optionnel)
                TextInput::make('bouton_principal.texte')
                    ->label('Texte du bouton principal')
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('bouton_principal.lien')
                    ->label('Lien du bouton principal')
                    ->helperText('URL, chemin (ex: /devis) ou ancre (ex: #section)')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'hero';
        
        $transformed = [
            'type' => 'hero',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'variant' => $variant,
        ];

        // Gestion selon la variante
        if ($variant === 'projects') {
            // Variante projects : utiliser le tableau images_ids (IDs de MediaFile)
            $transformed['images'] = static::transformMediaFileIds($data['images_ids'] ?? []);
        } else {
            // Variante hero standard : utiliser image_fond_id (ID de MediaFile)
            if (!empty($data['image_fond_id'])) {
                $imageUrl = static::getMediaFileUrl($data['image_fond_id']);
                if ($imageUrl) {
                    $transformed['image_fond'] = $imageUrl;
                    
                    if (!empty($data['image_fond_alt'])) {
                        $transformed['image_fond_alt'] = $data['image_fond_alt'];
                    }
                }
            }
            // Support rétrocompatibilité : si image_fond existe (ancien format avec chemin)
            elseif (!empty($data['image_fond'])) {
                $transformed['image_fond'] = static::transformImageUrl($data['image_fond']);
                
                if (!empty($data['image_fond_alt'])) {
                    $transformed['image_fond_alt'] = $data['image_fond_alt'];
                }
            }
        }

        // Bouton principal (optionnel)
        if (!empty($data['bouton_principal'])) {
            $button = $data['bouton_principal'];
            
            if (is_array($button) && !empty($button['texte']) && !empty($button['lien'])) {
                $transformed['bouton_principal'] = [
                    'texte' => $button['texte'],
                    'lien' => $button['lien'],
                ];
            }
        }

        return $transformed;
    }
}

