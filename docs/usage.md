# Usage

## Pages dans Filament

- Créer/editer une page dans l'admin Filament
- **Onglet Général** : titre, slug, type (home/standard), **mode de contenu** (blocs / experience), modèle d'Experience si besoin, statut, date de publication (`d/m/Y H:i`)
- **Onglet SEO** : titre SEO, description SEO, case **Ne pas indexer cette page** (`seo_noindex`)
- **Onglet Contenu** :
  - mode **blocs** : builder (`content.sections`)
  - mode **experience** : formulaire fixe de l'Experience selectionnee (voir `docs/experiences.md`)

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

### Prévisualisation

- **Publiée** : bouton **Ouvrir** (site public)
- **Brouillon / planifiée** : bouton **Prévisualiser** (lien signé vers `/preview/{slug}?preview_token=...`)

Integration frontend : `docs/preview-frontend.md`.

### Export / Import

- **Exporter** : depuis l'édition d'une page ou en sélection multiple dans la liste
- **Importer** : action **Importer** dans la liste, avec récapitulatif avant validation
- Format : archive `.xavcha-page.zip` (contenu + médias)

Voir `docs/page-transfer.md`.

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
