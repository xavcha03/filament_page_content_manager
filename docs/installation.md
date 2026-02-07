# Installation

## Prerequis

- PHP ^8.2
- Laravel ^12
- Filament ^4.5
- Media library : `xavcha/fillament-xavcha-media-library`

## Installer

Si la media library n'est pas installee, ajouter le repository dans `composer.json` :

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
php artisan vendor:publish --tag=page-content-manager-config
php artisan migrate
```

## Enregistrer la ressource Filament

Ajouter dans votre `PanelProvider` :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel->resources([
        PageResource::class,
    ]);
}
```

## Verifier

- Les pages apparaissent dans Filament
- `GET /api/pages` fonctionne
- `php artisan page-content-manager:block:list` liste vos blocs
