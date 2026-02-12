<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class CaseStudyBlock implements BlockInterface
{
    use HasMediaTransformation, HasMcpMetadata;

    public static function getType(): string
    {
        return 'case_study';
    }

    public static function make(): Block
    {
        return Block::make('case_study')
            ->label('Cas d\'étude (Portfolio)')
            ->icon('heroicon-o-briefcase')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('probleme')
                    ->label('Problème')
                    ->rows(4)
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Textarea::make('solution')
                    ->label('Solution')
                    ->rows(4)
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Textarea::make('resultat')
                    ->label('Résultat')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),

                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->collection('case_study_images')
                    ->acceptedFileTypes(['image/*'])
                    ->single()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->columnSpanFull(),

                TextInput::make('image_alt')
                    ->label('Texte alternatif de l\'image')
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('cta_text')
                    ->label('Texte du bouton')
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('cta_link')
                    ->label('Lien du bouton')
                    ->helperText('URL, chemin (ex: /projets) ou ancre (ex: #contact)')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $transformed = [
            'type' => 'case_study',
            'titre' => $data['titre'] ?? '',
            'probleme' => $data['probleme'] ?? '',
            'solution' => $data['solution'] ?? '',
            'resultat' => $data['resultat'] ?? '',
            'cta_text' => $data['cta_text'] ?? '',
            'cta_link' => $data['cta_link'] ?? '',
        ];

        // Gestion de l'image
        if (!empty($data['image_id'])) {
            $imageData = static::getMediaFileData($data['image_id']);
            if ($imageData) {
                $transformed['image_url'] = $imageData['url'];
                $transformed['image_width'] = $imageData['width'];
                $transformed['image_height'] = $imageData['height'];

                if (!empty($data['image_alt'])) {
                    $transformed['image_alt'] = $data['image_alt'];
                } elseif (!empty($imageData['alt_text'])) {
                    $transformed['image_alt'] = $imageData['alt_text'];
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
                'name' => 'titre',
                'label' => 'Titre',
                'type' => 'string',
                'required' => false,
                'max_length' => 200,
            ],
            [
                'name' => 'probleme',
                'label' => 'Problème',
                'type' => 'string',
                'required' => false,
                'max_length' => 1000,
            ],
            [
                'name' => 'solution',
                'label' => 'Solution',
                'type' => 'string',
                'required' => false,
                'max_length' => 1000,
            ],
            [
                'name' => 'resultat',
                'label' => 'Résultat',
                'type' => 'string',
                'required' => false,
                'max_length' => 500,
            ],
            [
                'name' => 'image_id',
                'label' => 'Image',
                'type' => 'integer',
                'required' => false,
                'description' => 'ID MediaFile',
            ],
            [
                'name' => 'image_alt',
                'label' => 'Texte alternatif',
                'type' => 'string',
                'required' => false,
                'max_length' => 200,
            ],
            [
                'name' => 'cta_text',
                'label' => 'Texte du bouton',
                'type' => 'string',
                'required' => false,
                'max_length' => 100,
            ],
            [
                'name' => 'cta_link',
                'label' => 'Lien du bouton',
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
            'titre' => 'Refonte site e-commerce',
            'probleme' => 'Le site existant avait un taux de rebond eleve et une mauvaise experience mobile.',
            'solution' => 'Nous avons refondu l\'interface, optimise les parcours et ameliore les performances.',
            'resultat' => '+40% de conversions, -30% de taux de rebond.',
            'image_id' => null,
            'image_alt' => 'Avant/apres du projet',
            'cta_text' => 'Voir le projet',
            'cta_link' => '/projets/refonte-ecommerce',
        ];
    }
}
