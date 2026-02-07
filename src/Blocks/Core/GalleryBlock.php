<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class GalleryBlock implements BlockInterface
{
    use HasMcpMetadata;

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre',
                'type' => 'string',
                'required' => false,
                'max_length' => 200,
            ],
            [
                'name' => 'images_ids',
                'label' => 'Images',
                'type' => 'array',
                'required' => true,
                'description' => 'Liste d\'IDs MediaFile',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Galerie recente',
            'images_ids' => [101, 102, 103],
        ];
    }
}





