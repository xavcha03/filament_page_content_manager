<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class SplitBlock implements BlockInterface
{
    use HasMcpMetadata;

    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'split';
    }

    public static function make(): Block
    {
        return Block::make('split')
            ->label('Texte + Image (Split)')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->maxLength(200)
                    ->columnSpanFull(),

                RichEditor::make('texte')
                    ->label('Texte')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock',
                        'h2',
                        'h3',
                    ])
                    ->columnSpanFull(),

                Select::make('variant')
                    ->label('Variante')
                    ->options([
                        'left' => 'Image à gauche',
                        'right' => 'Image à droite',
                        'feature' => 'Feature (mise en avant)',
                    ])
                    ->default('left')
                    ->live()
                    ->columnSpanFull(),

                Select::make('background')
                    ->label('Fond')
                    ->options([
                        'light' => 'Clair',
                        'dark' => 'Sombre',
                    ])
                    ->default('light')
                    ->visible(fn ($get) => $get('variant') !== 'feature')
                    ->columnSpanFull(),

                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->collection('split_images')
                    ->acceptedFileTypes(['image/*'])
                    ->single()
                    ->required()
                    ->showUpload(true)
                    ->showLibrary(true)
                    ->columnSpanFull(),

                TextInput::make('image_alt')
                    ->label('Texte alternatif de l\'image')
                    ->maxLength(200)
                    ->columnSpanFull(),

                // Bouton optionnel
                TextInput::make('bouton.texte')
                    ->label('Texte du bouton')
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('bouton.lien')
                    ->label('Lien du bouton')
                    ->helperText('URL, chemin (ex: /devis) ou ancre (ex: #section)')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'left';
        $background = $data['background'] ?? 'light';

        $transformed = [
            'type' => 'split',
            'titre' => $data['titre'] ?? '',
            'texte' => $data['texte'] ?? '',
            'variant' => $variant,
            'background' => $variant === 'feature' ? 'light' : $background,
        ];

        // Gestion de l'image
        if (!empty($data['image_id'])) {
            $imageData = static::getMediaFileData($data['image_id']);
            if ($imageData) {
                $transformed['image_url'] = $imageData['url'];
                $transformed['image_width'] = $imageData['width'];
                $transformed['image_height'] = $imageData['height'];
                
                // Utiliser alt_text du MediaFile si pas d'alt personnalisé
                if (!empty($data['image_alt'])) {
                    $transformed['image_alt'] = $data['image_alt'];
                } elseif (!empty($imageData['alt_text'])) {
                    $transformed['image_alt'] = $imageData['alt_text'];
                }
            }
        }

        // Bouton optionnel
        if (!empty($data['bouton'])) {
            $button = $data['bouton'];
            
            if (is_array($button) && !empty($button['texte']) && !empty($button['lien'])) {
                $transformed['bouton'] = [
                    'texte' => $button['texte'],
                    'lien' => $button['lien'],
                ];
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
                'name' => 'texte',
                'label' => 'Texte',
                'type' => 'string',
                'required' => true,
            ],
            [
                'name' => 'variant',
                'label' => 'Variante',
                'type' => 'string',
                'required' => false,
                'options' => ['left', 'right', 'feature'],
                'default' => 'left',
            ],
            [
                'name' => 'background',
                'label' => 'Fond',
                'type' => 'string',
                'required' => false,
                'options' => ['light', 'dark'],
                'default' => 'light',
            ],
            [
                'name' => 'image_id',
                'label' => 'Image',
                'type' => 'integer',
                'required' => true,
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
                'name' => 'bouton',
                'label' => 'Bouton',
                'type' => 'object',
                'required' => false,
                'fields' => [
                    [
                        'name' => 'texte',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 100,
                    ],
                    [
                        'name' => 'lien',
                        'type' => 'string',
                        'required' => false,
                        'max_length' => 255,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Une approche simple',
            'texte' => 'Nous combinons strategie, design et developpement.',
            'variant' => 'left',
            'background' => 'light',
            'image_id' => 401,
            'image_alt' => 'Equipe au travail',
            'bouton' => [
                'texte' => 'En savoir plus',
                'lien' => '/apropos',
            ],
        ];
    }
}

