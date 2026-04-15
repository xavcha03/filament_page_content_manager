# Usage

## Pages dans Filament

- Créer/editer une page dans l'admin Filament
- Champs : titre, slug, type, statut, SEO
- Onglet Contenu : ajouter des blocs

## Blocs disponibles

Ne pas utiliser de liste statique.
Utiliser :

```bash
ddev artisan page-content-manager:block:list
ddev artisan page-content-manager:block:inspect hero
ddev artisan page-content-manager:block:inspect tarifs
```

Exemple de bloc pricing core :
- `tarifs` : plans avec `nom`, `prix`, `prix_prefixe`, `periode`, `description`, `points`, `mise_en_avant`, `bouton_texte`, `bouton_lien`.

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
ddev artisan page-content-manager:block:disable hero --force
```

ou

```php
'disabled_blocks' => ['hero'],
```

## API

- `GET /api/pages`
- `GET /api/pages/{slug}`

Voir `docs/api.md`.
