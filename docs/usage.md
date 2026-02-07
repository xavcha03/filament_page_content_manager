# Usage

## Pages dans Filament

- Créer/editer une page dans l'admin Filament
- Champs : titre, slug, type, statut, SEO
- Onglet Contenu : ajouter des blocs

## Blocs disponibles

Ne pas utiliser de liste statique.
Utiliser :

```bash
php artisan page-content-manager:block:list
php artisan page-content-manager:block:inspect hero
```

## Groupes et ordre

Pour limiter ou ordonner les blocs par ressource :

```php
'block_groups' => [
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            // ...
        ],
    ],
],
```

## Desactivation

```bash
php artisan page-content-manager:block:disable hero --force
```

ou

```php
'disabled_blocks' => ['hero'],
```

## API

- `GET /api/pages`
- `GET /api/pages/{slug}`

Voir `docs/api.md`.
