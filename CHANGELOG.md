# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

## [0.2.4] - 2025-01-XX

### Ajouté
- **Facade Blocks pour faciliter l'accès au BlockRegistry** 🎯
  - Facade `Blocks` pour un accès simplifié au `BlockRegistry`
  - Méthode `has(string $type): bool` dans `BlockRegistry` pour vérifier l'existence d'un bloc
  - API plus propre et intuitive : `Blocks::get()`, `Blocks::all()`, `Blocks::has()`, etc.
  - Enregistrement de `BlockRegistry` comme singleton dans le ServiceProvider
- Tests unitaires pour la Facade (9 tests)

### Modifié
- `BlockRegistry` : Ajout de la méthode `has()` pour vérifier l'existence d'un bloc
- `PageContentManagerServiceProvider` : Enregistrement de `BlockRegistry` comme singleton

### Documentation
- Ajout de la section "Utiliser la Facade Blocks" dans le README
- Documentation complète dans `docs/blocks-architecture.md`

## [0.2.3] - 2025-01-XX

### Ajouté
- **Système de groupes de blocs avec ordre personnalisé** 🎯
  - Configuration `block_groups` pour organiser les blocs par contexte (Pages, Articles, etc.)
  - Méthode `ContentTab::make($group)` pour utiliser un groupe spécifique
  - Ordre d'affichage personnalisable via la configuration
  - Support de plusieurs groupes pour différentes ressources
  - Exclusion automatique des blocs désactivés
  - Rétrocompatibilité : fallback vers tous les blocs si le groupe n'existe pas
- Tests unitaires pour `ContentTab` avec groupes (11 tests)

### Modifié
- `ContentTab` accepte maintenant un paramètre `$group` pour spécifier le groupe de blocs
- `PageForm` utilise maintenant `ContentTab::make('pages')` pour une meilleure cohérence
- Configuration par défaut inclut un groupe `pages` avec tous les blocs core dans un ordre logique

### Documentation
- Ajout de la section "Groupes de blocs et ordre personnalisé" dans le README
- Documentation complète dans `docs/blocks-architecture.md`
- Exemples d'utilisation dans `docs/usage.md`

## [0.2.2] - 2025-01-XX

### Ajouté
- **CLI Interactif complet pour la gestion des blocs** 🎨
  - Commande `page-content-manager:make-block` pour créer des blocs personnalisés (mode interactif et non-interactif)
  - Commande `page-content-manager:blocks` avec menu interactif principal
  - Commande `page-content-manager:block:list` pour lister les blocs avec filtres (--core, --custom, --disabled, --group)
  - Commande `page-content-manager:block:inspect` pour inspecter un bloc en détail
  - Commandes `page-content-manager:block:disable/enable` pour activer/désactiver des blocs
  - Commande `page-content-manager:blocks:stats` pour afficher les statistiques
  - Commande `page-content-manager:blocks:validate` pour valider tous les blocs
  - Support mode non-interactif avec sortie JSON pour les agents IA
  - Suggestions de blocs similaires en cas d'erreur de frappe
  - Barre de progression pour les opérations longues (validation)
  - Gestion d'erreurs améliorée avec messages détaillés
  - Validation renforcée des noms de blocs avec messages clairs
- **Validation des blocs au démarrage** 🔍
  - Classe `BlockValidator` pour valider les blocs de manière centralisée
  - Option de configuration `validate_blocks_on_boot` pour activer la validation au démarrage
  - Option `validate_blocks_on_boot_throw` pour lancer une exception en cas d'erreur
  - Validation automatique des méthodes requises, types, et structure des blocs
  - Logging des erreurs et avertissements
- Helper `BlockCommandHelper` avec méthodes utilitaires partagées
- Classe `ExitCodes` pour les codes de sortie standardisés
- Tests unitaires pour toutes les nouvelles commandes et la validation

### Modifié
- Amélioration des messages de feedback dans toutes les commandes
- Gestion d'erreurs avec try-catch pour les opérations de fichiers
- Messages d'erreur plus informatifs avec instructions de résolution

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

[0.2.4]: https://github.com/xavcha03/page-content-manager/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/xavcha03/page-content-manager/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/xavcha03/page-content-manager/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/xavcha03/page-content-manager/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/xavcha03/page-content-manager/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/xavcha03/page-content-manager/releases/tag/0.1.0

