# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

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

[0.2.0]: https://github.com/xavcha03/page-content-manager/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/xavcha03/page-content-manager/releases/tag/0.1.0

