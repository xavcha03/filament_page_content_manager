<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class GalleryBlock
{
    public static function make(): Block
    {
        return Block::make('gallery')
            ->label('Galerie')
            ->icon('heroicon-o-photo')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                MediaPickerUnified::make('images_ids')
                    ->label('Images')
                    ->collection('gallery_images')
                    ->acceptedFileTypes(['image/*'])
                    ->multiple(true)
                    ->minFiles(1)
                    ->required()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->columnSpanFull(),
            ]);
    }
}



