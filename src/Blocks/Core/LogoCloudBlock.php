<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class LogoCloudBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'logo_cloud';
    }

    public static function make(): Block
    {
        return Block::make('logo_cloud')
            ->label('Logo Cloud (Clients/Partenaires)')
            ->icon('heroicon-o-building-office-2')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre (optionnel)')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Repeater::make('logos')
                    ->label('Logos')
                    ->schema([
                        MediaPickerUnified::make('logo_id')
                            ->label('Logo')
                            ->collection('client_logos')
                            ->acceptedFileTypes(['image/*'])
                            ->single()
                            ->required()
                            ->showUpload(true)
                            ->showLibrary(true)
                            ->columnSpanFull(),

                        TextInput::make('nom')
                            ->label('Nom de l\'entreprise')
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('lien')
                            ->label('Lien (optionnel)')
                            ->helperText('URL vers le site de l\'entreprise')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(50)
                    ->defaultItems(6)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['nom'] ?? 'Logo')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'logo_cloud',
            'titre' => $data['titre'] ?? '',
            'logos' => static::transformLogos($data['logos'] ?? []),
        ];
    }

    protected static function transformLogos(array $logos): array
    {
        return array_map(function ($logo) {
            if (!is_array($logo)) {
                return $logo;
            }

            $transformed = [
                'nom' => $logo['nom'] ?? '',
                'lien' => $logo['lien'] ?? '',
            ];

            // Gestion du logo
            if (!empty($logo['logo_id'])) {
                $logoData = static::getMediaFileData($logo['logo_id']);
                if ($logoData) {
                    $transformed['logo_url'] = $logoData['url'];
                    $transformed['logo_width'] = $logoData['width'];
                    $transformed['logo_height'] = $logoData['height'];
                    
                    if (!empty($logoData['alt_text'])) {
                        $transformed['logo_alt'] = $logoData['alt_text'];
                    } elseif (!empty($transformed['nom'])) {
                        // Utiliser le nom comme alt par défaut
                        $transformed['logo_alt'] = 'Logo ' . $transformed['nom'];
                    }
                }
            }

            return $transformed;
        }, $logos);
    }
}

