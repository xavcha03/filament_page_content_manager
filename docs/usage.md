# Usage

## Pages dans Filament

- Créer/editer une page dans l'admin Filament
- **Onglet Général** : titre, slug, type, statut, date de publication (`d/m/Y H:i`)
- **Onglet SEO** : titre SEO, description SEO, case **Ne pas indexer cette page** (`seo_noindex`)
- **Onglet Contenu** : blocs (`content.sections`)

### Liste des pages

Colonnes affichees : titre, slug, statut, indexation (icone ✓/✗), date de publication, modifie le.
Filtre **Corbeille** : pages actives / supprimees uniquement / avec supprimees.
Actions : supprimer (avec politique URL), restaurer, supprimer definitivement.

### Suppression d'une page

La suppression est un **soft delete** (corbeille). A la suppression, choisir la politique pour l'ancienne URL :

- **410** — page supprimee definitivement (defaut configurable)
- **404** — introuvable
- **301** vers une autre page CMS (publiee)
- **301** vers une URL personnalisee

La suppression definitive retire l'enregistrement de la base (slug libere).

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
