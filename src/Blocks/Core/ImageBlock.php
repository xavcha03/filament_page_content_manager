<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBlock implements BlockInterface
{
    use HasMcpMetadata;

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
            $imageData = static::getMediaFileData($data['image_id']);
            if ($imageData) {
                $transformed['image_url'] = $imageData['url'];
                $transformed['width'] = $imageData['width'];
                $transformed['height'] = $imageData['height'];
                // Utiliser alt_text du MediaFile si pas d'alt personnalisé
                if (empty($transformed['alt']) && !empty($imageData['alt_text'])) {
                    $transformed['alt'] = $imageData['alt_text'];
                }
            }
        }

        return $transformed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'image_id',
                'label' => 'Image',
                'type' => 'integer',
                'required' => true,
                'description' => 'ID MediaFile',
            ],
            [
                'name' => 'alt',
                'label' => 'Texte alternatif',
                'type' => 'string',
                'required' => false,
                'max_length' => 200,
            ],
            [
                'name' => 'caption',
                'label' => 'Legende',
                'type' => 'string',
                'required' => false,
                'max_length' => 255,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'image_id' => 123,
            'alt' => 'Photo du studio',
            'caption' => 'Notre equipe en action',
        ];
    }
}



