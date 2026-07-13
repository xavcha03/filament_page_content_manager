# Export / Import de pages

Permet d'exporter une ou plusieurs pages CMS vers une archive `.xavcha-page.zip`, puis de les réimporter dans un autre environnement (local, staging, production).

## Cas d'usage

- Travailler le contenu en local ou sur le staging, puis pousser en production
- Sauvegarder une page avant une refonte
- Dupliquer une page d'un site vers un autre (mêmes blocs disponibles)

## Format de l'archive

```
mon-export.xavcha-page.zip
├── manifest.json
├── pages/
│   ├── home/
│   │   └── page.json
│   └── tarifs/
│       └── page.json
└── media/
    ├── manifest.json
    ├── {uuid}.jpg
    └── {uuid}.webp
```

Les médias sont référencés par **UUID** dans le contenu (`media:{uuid}`), pas par ID de base de données.

## Filament

### Exporter

- **Édition d'une page** : action **Exporter** dans l'en-tête
- **Liste des pages** : sélection multiple → action groupée **Exporter**

### Importer

- **Liste des pages** : action **Importer**
- Téléverser une archive `.xavcha-page.zip`
- Consulter le **récapitulatif** :
  - nouvelle page si le slug n'existe pas
  - remplacement si le slug existe déjà (y compris la page Home)
  - médias à importer / déjà présents
  - avertissements (blocs inconnus, médias manquants)
- Choisir :
  - **Remplacer** ou **Ignorer** les pages existantes
  - **Importer en brouillon** (recommandé en production)

## Commandes Artisan

```bash
# Export d'une page
ddev artisan page-content-manager:page:export tarifs

# Export multiple
ddev artisan page-content-manager:page:export --pages=home --pages=tarifs --output=export.zip

# Prévisualisation d'un import
ddev artisan page-content-manager:page:import export.xavcha-page.zip --dry-run

# Import avec remplacement
ddev artisan page-content-manager:page:import export.xavcha-page.zip --mode=replace --draft

# Conserver le statut de publication
ddev artisan page-content-manager:page:import export.xavcha-page.zip --keep-status
```

## Prérequis

- Extension PHP **zip** (`ZipArchive`)
- Package `xavcha/fillament-xavcha-media-library` pour les médias embarqués
- Les **mêmes blocs** (core + custom) doivent exister sur l'environnement cible

## Limites connues

- Les politiques de redirection de suppression ne sont pas exportées
- Les blocs absents sur la cible génèrent des avertissements
- L'optimisation WebP de la médiathèque peut transformer le fichier importé (la référence UUID reste valide)

## Configuration

Dans `config/page-content-manager.php` :

```php
'transfer' => [
    'format_version' => 1,
    'extension' => 'xavcha-page.zip',
    'import_force_draft_default' => true,
    'max_upload_size_mb' => 50,
],
```
