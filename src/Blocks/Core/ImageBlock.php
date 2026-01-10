<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'image';
    }

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

    public static function transform(array $data): array
    {
        $transformed = [
            'type' => 'image',
            'alt' => $data['alt'] ?? '',
            'caption' => $data['caption'] ?? '',
        ];

        if (!empty($data['image_id'])) {
            $imageUrl = static::getMediaFileUrl($data['image_id']);
            if ($imageUrl) {
                $transformed['image_url'] = $imageUrl;
            }
        }

        return $transformed;
    }
}



