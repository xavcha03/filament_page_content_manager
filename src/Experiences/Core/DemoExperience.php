<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Experiences\Core;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavcha\PageContentManager\Experiences\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Experiences\Contracts\ExperienceInterface;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

/**
 * Mini Experience de démo pour tester le mode content_mode=experience.
 * Désactivable via config disabled_experiences = ['demo'].
 */
class DemoExperience implements ExperienceInterface
{
    use HasMediaTransformation;
    use HasMcpMetadata;

    public static function getKey(): string
    {
        return 'demo';
    }

    public static function getLabel(): string
    {
        return 'Demo Experience';
    }

    public static function make(): array
    {
        return [
            TextInput::make('eyebrow')
                ->label('Surtitre')
                ->maxLength(80)
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('Titre principal')
                ->required()
                ->maxLength(200)
                ->columnSpanFull(),
            Textarea::make('intro')
                ->label('Introduction')
                ->rows(4)
                ->maxLength(800)
                ->columnSpanFull(),
            MediaPickerUnified::make('hero_image_id')
                ->label('Image principale')
                ->collection('experience_demo')
                ->acceptedFileTypes(['image/*'])
                ->single()
                ->showUpload(true)
                ->showLibrary(true)
                ->columnSpanFull(),
            TextInput::make('cta_label')
                ->label('Libellé CTA')
                ->maxLength(80),
            TextInput::make('cta_url')
                ->label('URL CTA')
                ->maxLength(500),
            Repeater::make('highlights')
                ->label('Points forts')
                ->schema([
                    TextInput::make('label')
                        ->label('Texte')
                        ->required()
                        ->maxLength(120),
                ])
                ->defaultItems(0)
                ->maxItems(6)
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    public static function transform(array $data): array
    {
        $highlights = [];
        foreach ($data['highlights'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $highlights[] = ['label' => $label];
        }

        return [
            'eyebrow' => $data['eyebrow'] ?? '',
            'title' => $data['title'] ?? '',
            'intro' => $data['intro'] ?? '',
            'hero_image' => static::getMediaFileData($data['hero_image_id'] ?? null),
            'cta' => [
                'label' => $data['cta_label'] ?? '',
                'url' => $data['cta_url'] ?? '',
            ],
            'highlights' => $highlights,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'eyebrow',
                'label' => 'Surtitre',
                'type' => 'string',
                'required' => false,
            ],
            [
                'name' => 'title',
                'label' => 'Titre principal',
                'type' => 'string',
                'required' => true,
            ],
            [
                'name' => 'intro',
                'label' => 'Introduction',
                'type' => 'string',
                'required' => false,
            ],
            [
                'name' => 'hero_image_id',
                'label' => 'Image principale',
                'type' => 'media_id',
                'required' => false,
                'description' => 'MediaFile ID uploadé via Filament',
            ],
            [
                'name' => 'cta_label',
                'label' => 'Libellé CTA',
                'type' => 'string',
                'required' => false,
            ],
            [
                'name' => 'cta_url',
                'label' => 'URL CTA',
                'type' => 'string',
                'required' => false,
            ],
            [
                'name' => 'highlights',
                'label' => 'Points forts',
                'type' => 'array',
                'required' => false,
                'description' => 'Liste d\'objets { label: string }, max 6',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'eyebrow' => 'Démo package',
            'title' => 'Page Experience de test',
            'intro' => 'Contenu figé côté code, éditable uniquement en valeurs.',
            'cta_label' => 'En savoir plus',
            'cta_url' => '/contact',
            'highlights' => [
                ['label' => 'Schéma figé'],
                ['label' => 'MCP contenu only'],
            ],
        ];
    }

    public static function getMcpDescription(): string
    {
        return 'Mini Experience de démonstration pour tester le mode Experience (titre, intro, image, CTA, highlights).';
    }
}
