<?php

namespace Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBlock
{
    public static function make(): Block
    {
        return Block::make('image')
            ->label('Image')
            ->icon('heroicon-o-photo')
            ->schema([
                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->collection('content_images')
                    ->acceptedFileTypes(['image/*'])
                    ->single()
                    ->required()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->columnSpanFull(),

                TextInput::make('alt')
                    ->label('Texte alternatif')
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('caption')
                    ->label('Légende')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}






