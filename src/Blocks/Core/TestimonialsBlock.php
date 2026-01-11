<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class TestimonialsBlock implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'testimonials';
    }

    public static function make(): Block
    {
        return Block::make('testimonials')
            ->label('Témoignages')
            ->icon('heroicon-o-chat-bubble-left-right')
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

                Repeater::make('temoignages')
                    ->label('Témoignages')
                    ->schema([
                        Textarea::make('avis')
                            ->label('Avis')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        TextInput::make('auteur')
                            ->label('Auteur')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('fonction')
                            ->label('Fonction / Poste')
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('entreprise')
                            ->label('Entreprise')
                            ->maxLength(200)
                            ->columnSpanFull(),

                        MediaPickerUnified::make('photo_id')
                            ->label('Photo (optionnel)')
                            ->collection('testimonial_photos')
                            ->acceptedFileTypes(['image/*'])
                            ->single()
                            ->showUpload(true)
                            ->showLibrary(true)
                            ->columnSpanFull(),

                        Select::make('note')
                            ->label('Note')
                            ->options([
                                '1' => '1 étoile',
                                '2' => '2 étoiles',
                                '3' => '3 étoiles',
                                '4' => '4 étoiles',
                                '5' => '5 étoiles',
                            ])
                            ->default('5')
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->maxItems(12)
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['auteur'] ?? 'Témoignage')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'testimonials',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'temoignages' => static::transformTestimonials($data['temoignages'] ?? []),
        ];
    }

    protected static function transformTestimonials(array $testimonials): array
    {
        return array_map(function ($testimonial) {
            if (!is_array($testimonial)) {
                return $testimonial;
            }

            $transformed = [
                'avis' => $testimonial['avis'] ?? '',
                'auteur' => $testimonial['auteur'] ?? '',
                'fonction' => $testimonial['fonction'] ?? '',
                'entreprise' => $testimonial['entreprise'] ?? '',
                'note' => (int) ($testimonial['note'] ?? 5),
            ];

            // Gestion de la photo
            if (!empty($testimonial['photo_id'])) {
                $photoData = static::getMediaFileData($testimonial['photo_id']);
                if ($photoData) {
                    $transformed['photo_url'] = $photoData['url'];
                    $transformed['photo_width'] = $photoData['width'];
                    $transformed['photo_height'] = $photoData['height'];
                    
                    if (!empty($photoData['alt_text'])) {
                        $transformed['photo_alt'] = $photoData['alt_text'];
                    }
                }
            }

            return $transformed;
        }, $testimonials);
    }
}

