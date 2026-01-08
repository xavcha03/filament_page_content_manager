# Créer des blocs personnalisés

## Structure d'un bloc

Un bloc est une classe avec une méthode statique `make()` qui retourne un `Block` Filament.

## Exemple : Bloc simple

Créer `app/Filament/Forms/Components/Blocks/Custom/VideoBlock.php` :

```php
<?php

namespace App\Filament\Forms\Components\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class VideoBlock
{
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
}
```

## Enregistrer le bloc

Dans `config/page-content-manager.php` :

```php
'blocks' => [
    'custom' => [
        'video' => \App\Filament\Forms\Components\Blocks\Custom\VideoBlock::class,
    ],
],
```

## Créer un transformer (optionnel)

Pour transformer les données du bloc dans l'API, créez un transformer :

`app/Services/Blocks/Transformers/Custom/VideoBlockTransformer.php` :

```php
<?php

namespace App\Services\Blocks\Transformers\Custom;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;

class VideoBlockTransformer implements BlockTransformerInterface
{
    public function getType(): string
    {
        return 'video';
    }

    public function transform(array $data): array
    {
        return [
            'type' => 'video',
            'titre' => $data['titre'] ?? '',
            'video_url' => $data['video_url'] ?? '',
            'description' => $data['description'] ?? '',
            'embed_url' => $this->convertToEmbedUrl($data['video_url'] ?? ''),
        ];
    }

    protected function convertToEmbedUrl(string $url): string
    {
        // Logique pour convertir l'URL en URL d'embed
        // Exemple pour YouTube
        if (str_contains($url, 'youtube.com/watch')) {
            $videoId = parse_str(parse_url($url, PHP_URL_QUERY), $params);
            return "https://www.youtube.com/embed/{$params['v']}";
        }
        
        return $url;
    }
}
```

Le transformer sera automatiquement découvert et utilisé.

## Remplacer un bloc Core

Pour remplacer un bloc core par votre propre version :

1. Créez votre bloc avec le même type (ex: `'hero'`)
2. Ajoutez-le dans `custom` au lieu de `core`
3. Retirez le bloc core de la configuration

Le bloc custom sera utilisé à la place du bloc core.

