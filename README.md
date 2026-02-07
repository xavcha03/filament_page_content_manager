# Xavcha Page Content Manager

Package Laravel Filament pour gerer des pages avec un systeme de blocs de contenu flexible et reutilisable.

> Note : version 0.2.4 (pre-v1). L'API peut evoluer.

## Resume rapide

- Ressource Filament complete pour les pages
- Blocs modulaires et extensibles
- API pour recuperer les pages et leur contenu transforme
- Serveur MCP pour agents IA
- CLI pour creer, inspecter, valider et gerer les blocs
- Systemes reutilisables SEO + Content pour d'autres ressources
- Transformers et evenements personnalisables

## Blocs core

- Hero
- Text
- Image
- Gallery
- CTA
- FAQ
- Contact Form
- Features
- Logo Cloud
- Services
- Split
- Testimonials

## Installation

### Dependance requise

Ce package depend de `xavcha/fillament-xavcha-media-library` (GitHub).

Si la media library n'est pas installee, ajoute le repository dans `composer.json` :

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/xavcha03/fillament_xavcha_media_library"
    }
  ]
}
```

Puis :

```bash
composer require xavcha/page-content-manager
```

Si la media library est deja installee et que Composer bloque :

```bash
composer require xavcha/page-content-manager --no-update
composer update xavcha/page-content-manager --with-dependencies
```

### Publish + migrations

```bash
php artisan vendor:publish --tag=page-content-manager-config
php artisan migrate
```

## Demarrage rapide

### Ressource Page (Filament)

Enregistrer manuellement la ressource :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel
        ->resources([
            PageResource::class,
        ]);
}
```

## API

Routes exposees (prefix configurable) :

- `GET /api/pages`
- `GET /api/pages/{slug}`

Exemple de reponse :

```json
{
  "id": 1,
  "title": "Accueil",
  "slug": "home",
  "type": "home",
  "seo_title": "Page d'accueil",
  "seo_description": "Description SEO",
  "sections": [
    {
      "type": "hero",
      "data": {
        "titre": "Bienvenue",
        "description": "Description du hero",
        "variant": "hero",
        "image_fond": "https://example.com/image.jpg"
      }
    }
  ],
  "metadata": {
    "schema_version": 1
  }
}
```

## Blocs personnalises

Cree un bloc dans `app/Blocks/Custom/` (un seul fichier, form + transform) :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    public static function getType(): string
    {
        return 'mon_bloc';
    }

    public static function make(): Block
    {
        return Block::make('mon_bloc')
            ->label('Mon Bloc')
            ->icon('heroicon-o-star')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'mon_bloc',
            'titre' => $data['titre'] ?? '',
        ];
    }
}
```

## CLI blocs

Menu interactif :

```bash
php artisan page-content-manager:blocks
```

Creation bloc (mode IA) :

```bash
php artisan page-content-manager:make-block video \
  --group=media \
  --with-media \
  --order=50 \
  --force
```

Commandes utiles :

```bash
php artisan page-content-manager:block:list
php artisan page-content-manager:block:inspect hero
php artisan page-content-manager:block:disable faq --force
php artisan page-content-manager:block:enable faq --force
php artisan page-content-manager:blocks:stats
php artisan page-content-manager:blocks:validate
php artisan page-content-manager:blocks:clear-cache
```

Toutes les commandes supportent le mode JSON pour les agents IA : `--json`.

## Validation automatique des blocs

Dans `.env` :

```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW=true
```

## Groupes de blocs et ordre

Configuration dans `config/page-content-manager.php` :

```php
'block_groups' => [
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            // ...
        ],
    ],
    'articles' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \App\Blocks\Custom\AuthorBlock::class,
        ],
    ],
],
```

Utilisation :

```php
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

ContentTab::make();         // groupe 'pages'
ContentTab::make('articles');
```

## Facade Blocks

```php
use Xavcha\PageContentManager\Facades\Blocks;

Blocks::get('hero');
Blocks::all();
Blocks::has('text');
Blocks::register('custom_block', \App\Blocks\Custom\MyBlock::class);
Blocks::clearCache();
```

## Evenements de transformation

- `BlockTransforming` (avant)
- `BlockTransformed` (apres)

## Serveur MCP (agents IA)

Le serveur MCP est fourni pour creer et gerer des pages via agents.
Voir `docs/mcp-server.md`.

## Systeme reutilisable (autres ressources)

Ajoute SEO + Content a n'importe quelle ressource Filament.
Voir `docs/reusable-system.md`.

## Tests

```bash
# Avec ddev
ddev exec vendor/bin/phpunit

# Ou direct
composer test
```

## Documentation

- `docs/installation.md`
- `docs/usage.md`
- `docs/blocks-architecture.md`
- `docs/custom-blocks.md`
- `docs/api.md`
- `docs/mcp-server.md`
- `docs/reusable-system.md`
- `docs/testing.md`
- `docs/migration-v2.md`

## Licence

MIT
