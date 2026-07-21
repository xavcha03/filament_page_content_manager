# Sélecteur de blocs Filament

L’onglet **Contenu** ouvre une **modale** pour ajouter une section (recherche, groupes, cards).

## Métadonnées d’un bloc

Dans la classe du bloc :

```php
public static function getGroup(): string
{
    return 'Contenu'; // Layout | Contenu | Média | Conversion | Social proof | Autres
}

public static function getDescription(): string
{
    return 'Courte description affichée sous le label.';
}
```

## Image de preview (optionnelle)

Convention de fichier : `{type}.webp`

Emplacements (premier trouvé gagne) :

1. `public/images/block-previews/{type}.webp`
2. `resources/images/block-previews/{type}.webp` (app)
3. Preview package : `vendor/xavcha/page-content-manager/resources/images/block-previews/{type}.webp`

Si le fichier est absent, la card s’affiche **sans** image.

Override possible :

```php
public static function getPreviewImageUrl(): ?string
{
    return asset('images/mon-apercu.webp');
}
```

Publier les previews du package :

```bash
php artisan vendor:publish --tag=page-content-manager-block-previews
```

## Stub `make-block`

```bash
php artisan page-content-manager:make-block mon-bloc --group=Contenu
# ou alias : --group=content
```
