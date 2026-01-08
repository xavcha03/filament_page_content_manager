# Créer des blocs personnalisés

## Nouvelle Architecture (v2.0+)

Depuis la version 2.0, les blocs sont simplifiés : **un seul fichier** contient le formulaire ET la transformation.

## Structure d'un bloc

Un bloc doit implémenter `BlockInterface` avec trois méthodes :
- `getType()` : Retourne le type unique du bloc
- `make()` : Crée le formulaire Filament
- `transform()` : Transforme les données pour l'API

## Exemple : Bloc simple

Créez `app/Blocks/Custom/VideoBlock.php` :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class VideoBlock implements BlockInterface
{
    public static function getType(): string
    {
        return 'video';
    }

    public static function make(): Block
    {
        return Block::make('video')
            ->label('Vidéo')
            ->icon('heroicon-o-video-camera')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('video_url')
                    ->label('URL de la vidéo')
                    ->required()
                    ->url()
                    ->helperText('URL YouTube, Vimeo, etc.')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'video',
            'titre' => $data['titre'] ?? '',
            'video_url' => $data['video_url'] ?? '',
            'description' => $data['description'] ?? '',
            'embed_url' => static::convertToEmbedUrl($data['video_url'] ?? ''),
        ];
    }

    protected static function convertToEmbedUrl(string $url): string
    {
        // Logique pour convertir l'URL en URL d'embed
        // Exemple pour YouTube
        if (str_contains($url, 'youtube.com/watch')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            return "https://www.youtube.com/embed/{$params['v']}";
        }
        
        return $url;
    }
}
```

## Auto-découverte

Le bloc est **automatiquement découvert** et disponible dans Filament. Aucune configuration nécessaire !

Les blocs sont recherchés dans :
- Package : `src/Blocks/Core/`
- Application : `app/Blocks/Custom/`

## Bloc avec Médias

Si votre bloc utilise des médias, utilisez le trait `HasMediaTransformation` :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBloc implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'image_bloc';
    }

    public static function make(): Block
    {
        return Block::make('image_bloc')
            ->schema([
                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->single()
                    ->required(),
            ]);
    }

    public static function transform(array $data): array
    {
        $imageUrl = static::getMediaFileUrl($data['image_id'] ?? null);

        return [
            'type' => 'image_bloc',
            'image' => $imageUrl,
        ];
    }
}
```

## Remplacer un bloc Core

Pour remplacer un bloc core par votre propre version :

1. Créez votre bloc dans `app/Blocks/Custom/` avec le même type (ex: `'hero'`)
2. Le bloc custom remplacera automatiquement le bloc core

Le bloc custom sera utilisé à la place du bloc core.

## Voir aussi

- [Architecture des blocs](blocks-architecture.md) - Guide complet de la nouvelle architecture
- [Migration v2.0](migration-v2.md) - Guide de migration depuis l'ancien système
