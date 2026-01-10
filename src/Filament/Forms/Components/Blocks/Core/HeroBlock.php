<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class HeroBlock
{
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
}




