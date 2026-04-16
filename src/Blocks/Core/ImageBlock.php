<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBlock implements BlockInterface
{
    use HasMcpMetadata;

    use HasMediaTransformation;

    private const DEFAULT_ALIGNMENT = 'center';
    private const DEFAULT_SIZE = 'medium';
    private const DEFAULT_RATIO = 'auto';

    private const ALIGNMENTS = [
        'center' => 'Centré',
        'left' => 'Aligné à gauche',
        'right' => 'Aligné à droite',
    ];

    private const SIZES = [
        'small' => 'Small (contenu)',
        'medium' => 'Medium (défaut)',
        'large' => 'Large (quasi full width)',
    ];

    private const RATIOS = [
        'auto' => 'Auto',
        '16:9' => '16:9',
        '4:3' => '4:3',
    ];

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
                    ->helperText('Obligatoire (SEO + accessibilité). Décrivez l’image de manière utile, sans sur-optimisation.')
                    ->maxLength(200)
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('caption')
                    ->label('Légende')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('alignment')
                    ->label('Alignement de l’image')
                    ->options(self::ALIGNMENTS)
                    ->default(self::DEFAULT_ALIGNMENT)
                    ->native(false)
                    ->columnSpanFull(),

                Select::make('size')
                    ->label('Taille de l’image')
                    ->options(self::SIZES)
                    ->default(self::DEFAULT_SIZE)
                    ->native(false)
                    ->columnSpanFull(),

                Select::make('ratio')
                    ->label('Ratio contrôlé')
                    ->options(self::RATIOS)
                    ->default(self::DEFAULT_RATIO)
                    ->native(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $alignment = $data['alignment'] ?? self::DEFAULT_ALIGNMENT;
        if (!array_key_exists($alignment, self::ALIGNMENTS)) {
            $alignment = self::DEFAULT_ALIGNMENT;
        }

        $size = $data['size'] ?? self::DEFAULT_SIZE;
        if (!array_key_exists($size, self::SIZES)) {
            $size = self::DEFAULT_SIZE;
        }

        $ratio = $data['ratio'] ?? self::DEFAULT_RATIO;
        if (!array_key_exists($ratio, self::RATIOS)) {
            $ratio = self::DEFAULT_RATIO;
        }

        $transformed = [
            'type' => 'image',
            'alt' => $data['alt'] ?? '',
            'caption' => $data['caption'] ?? '',
            'alignment' => $alignment,
            'size' => $size,
            'ratio' => $ratio,
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
                'required' => true,
                'max_length' => 200,
            ],
            [
                'name' => 'caption',
                'label' => 'Legende',
                'type' => 'string',
                'required' => false,
                'max_length' => 255,
            ],
            [
                'name' => 'alignment',
                'label' => 'Alignement de l’image',
                'type' => 'string',
                'required' => false,
                'enum' => array_keys(self::ALIGNMENTS),
                'default' => self::DEFAULT_ALIGNMENT,
            ],
            [
                'name' => 'size',
                'label' => 'Taille de l’image',
                'type' => 'string',
                'required' => false,
                'enum' => array_keys(self::SIZES),
                'default' => self::DEFAULT_SIZE,
            ],
            [
                'name' => 'ratio',
                'label' => 'Ratio contrôlé',
                'type' => 'string',
                'required' => false,
                'enum' => array_keys(self::RATIOS),
                'default' => self::DEFAULT_RATIO,
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
            'alignment' => self::DEFAULT_ALIGNMENT,
            'size' => self::DEFAULT_SIZE,
            'ratio' => self::DEFAULT_RATIO,
        ];
    }
}



