# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

## [0.2.1] - 2025-01-11

### Ajouté
- Système de cache pour BlockRegistry pour améliorer les performances
- Configuration du cache dans `config/page-content-manager.php` avec options :
  - `cache.enabled` : Active/désactive le cache (défaut: `true`)
  - `cache.key` : Clé de cache personnalisable (défaut: `'page-content-manager.blocks.registry'`)
  - `cache.ttl` : Durée de vie du cache en secondes (défaut: `3600`)
- Commande Artisan `page-content-manager:blocks:clear-cache` pour invalider le cache manuellement
- Support de la configuration `disabled_blocks` pour filtrer les blocs désactivés
- Cache automatiquement désactivé en environnement `local` pour détecter immédiatement les nouveaux blocs
- Tests unitaires complets pour le système de cache (9 tests pour BlockRegistry, 5 tests pour la commande)

### Modifié
- `BlockRegistry` utilise maintenant le cache Laravel pour stocker la liste des blocs découverts
- Amélioration des performances : la découverte des blocs n'est effectuée qu'une fois par période de cache

## [0.2.0] - 2025-01-10

### Ajouté
- Suite complète de tests unitaires et fonctionnels (120 tests)
- Helpers de test réutilisables (TestHelpers)
- Documentation complète des tests
- Support des blocs personnalisés avec auto-découverte
- Système de transformation des sections pour l'API
- Trait HasMediaTransformation pour la gestion des médias
- Commande Artisan `page-content-manager:add-page-detail` pour ajouter SEO/Content à d'autres modèles
- Système réutilisable pour ajouter SEO et Content à d'autres ressources Filament

### Modifié
- Architecture des blocs simplifiée (v2.0) : un seul fichier par bloc (formulaire + transformation)
- Amélioration de la gestion des erreurs dans SectionTransformer
- Normalisation automatique du contenu lors de la sauvegarde
- Documentation améliorée avec guides détaillés

### Sécurité
- Validation des règles métier (une seule page Home, pas de suppression de Home, etc.)
- Gestion sécurisée des transformations de blocs avec fallback

## [0.1.0] - 2025-01-08

### Ajouté
- Ressource Filament complète pour gérer les pages
- Système de blocs modulaire avec 7 blocs core :
  - Hero (avec variantes hero/projects)
  - Text
  - Image
  - Gallery
  - CTA (avec variantes simple/hero/subscription)
  - FAQ
  - Contact Form
- Routes API pour récupérer les pages (`GET /api/pages`, `GET /api/pages/{slug}`)
- Gestion des métadonnées SEO (seo_title, seo_description)
- Système de statuts (draft, scheduled, published)
- Support de la publication planifiée (published_at)
- Types de pages (home, standard)
- Migration initiale avec création automatique de la page Home
- Configuration flexible via fichier de config

[0.2.1]: https://github.com/xavcha03/page-content-manager/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/xavcha03/page-content-manager/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/xavcha03/page-content-manager/releases/tag/0.1.0

