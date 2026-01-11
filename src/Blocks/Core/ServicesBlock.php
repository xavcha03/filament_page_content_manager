<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ServicesBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'services';
    }

    public static function make(): Block
    {
        return Block::make('services')
            ->label('Services / Offres')
            ->icon('heroicon-o-briefcase')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre principal')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Repeater::make('services')
                    ->label('Services')
                    ->schema([
                        MediaPickerUnified::make('image_id')
                            ->label('Image')
                            ->collection('service_images')
                            ->acceptedFileTypes(['image/*'])
                            ->single()
                            ->showUpload(true)
                            ->showLibrary(true)
                            ->columnSpanFull(),

                        TextInput::make('titre')
                            ->label('Titre')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TextInput::make('lien')
                            ->label('Lien vers la page détaillée')
                            ->helperText('URL, chemin (ex: /services/nom-service) ou ancre (ex: #section)')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('bouton_texte')
                            ->label('Texte du bouton')
                            ->default('En savoir plus')
                            ->maxLength(100)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(20)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['titre'] ?? 'Service')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'services',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'services' => static::transformServices($data['services'] ?? []),
        ];
    }

    protected static function transformServices(array $services): array
    {
        return array_map(function ($service) {
            if (!is_array($service)) {
                return $service;
            }

            $transformed = [
                'titre' => $service['titre'] ?? '',
                'description' => $service['description'] ?? '',
                'lien' => $service['lien'] ?? '',
                'bouton_texte' => $service['bouton_texte'] ?? 'En savoir plus',
            ];

            // Gestion de l'image
            if (!empty($service['image_id'])) {
                $imageData = static::getMediaFileData($service['image_id']);
                if ($imageData) {
                    $transformed['image_url'] = $imageData['url'];
                    $transformed['image_width'] = $imageData['width'];
                    $transformed['image_height'] = $imageData['height'];
                    
                    if (!empty($imageData['alt_text'])) {
                        $transformed['image_alt'] = $imageData['alt_text'];
                    }
                }
            }

            return $transformed;
        }, $services);
    }
}


