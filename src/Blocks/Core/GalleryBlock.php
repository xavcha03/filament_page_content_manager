<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class GalleryBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'gallery';
    }

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

    public static function transform(array $data): array
    {
        return [
            'type' => 'gallery',
            'titre' => $data['titre'] ?? '',
            'images' => static::transformMediaFileIds($data['images_ids'] ?? []),
        ];
    }
}





