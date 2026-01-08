# Améliorations Proposées

## 🚀 Priorité Haute

### 1. Cache pour BlockRegistry
**Problème** : La découverte automatique des blocs se fait à chaque requête, ce qui peut être coûteux.

**Solution** : Mettre en cache la liste des blocs découverts.

```php
// Dans BlockRegistry
protected function autoDiscoverBlocks(): void
{
    if ($this->autoDiscovered) {
        return;
    }

    $cacheKey = 'page-content-manager.blocks.registry';
    $cached = Cache::remember($cacheKey, 3600, function () {
        // Découverte des blocs
    });
    
    // ...
}
```

**Bénéfice** : Performance améliorée, surtout en production.

---

### 2. CLI Interactif pour la gestion des blocs 🎨
**Problème** : Gestion des blocs dispersée, pas d'outil unifié.

**Solution** : Créer un CLI interactif et beau avec plusieurs commandes :

#### 2.1 Lister les blocs
```bash
php artisan page-content-manager:blocks

┌─────────────────────────────────────────────────────────┐
│  📦 Blocs disponibles                                    │
├─────────────────────────────────────────────────────────┤
│  Core (7 blocs)                                          │
│  ✅ hero          - Section Hero                          │
│  ✅ text          - Texte                                 │
│  ✅ image         - Image                                 │
│  ✅ gallery       - Galerie                               │
│  ✅ cta           - Appel à l'action                      │
│  ✅ faq           - Section FAQ                           │
│  ✅ contact_form  - Formulaire de contact                 │
│                                                           │
│  Custom (2 blocs)                                         │
│  ✅ video         - Vidéo                                 │
│  ✅ testimonial   - Témoignage                            │
│                                                           │
│  Désactivés (1 bloc)                                      │
│  ❌ old_block     - Ancien bloc (désactivé)              │
└─────────────────────────────────────────────────────────┘
```

#### 2.2 Créer un nouveau bloc

**Mode interactif** (pour les humains) :
```bash
php artisan page-content-manager:make-block

  Quel est le nom de votre bloc ?
  > video

  Quelle catégorie ? [content/media/forms/other]
  > media

  Voulez-vous utiliser le trait HasMediaTransformation ? (yes/no)
  > yes

  ✅ Bloc créé avec succès !
  
  📁 app/Blocks/Custom/VideoBlock.php
  📝 N'oubliez pas d'implémenter la méthode transform() !
```

**Mode non-interactif** (pour les agents IA) :
```bash
# Tous les paramètres en ligne de commande
php artisan page-content-manager:make-block video \
  --group=media \
  --with-media \
  --order=50 \
  --force

# Ou version courte
php artisan page-content-manager:make-block video -g media -m -o 50 -f

# Paramètres disponibles :
# --name, -n          : Nom du bloc (requis si non-interactif)
# --group, -g         : Groupe/catégorie (content/media/forms/other)
# --with-media, -m    : Utiliser le trait HasMediaTransformation
# --order, -o         : Ordre d'affichage (défaut: 100)
# --force, -f         : Écraser si le fichier existe déjà
# --namespace, -N     : Namespace personnalisé (défaut: App\Blocks\Custom)
```

**Détection automatique** : Si `--name` est fourni, le mode non-interactif est activé automatiquement.

#### 2.3 Désactiver/Activer un bloc

**Mode interactif** :
```bash
php artisan page-content-manager:block:disable hero

  ⚠️  Attention : Le bloc 'hero' sera désactivé.
  Êtes-vous sûr ? (yes/no)
  > yes

  ✅ Bloc 'hero' désactivé avec succès !
  📝 Ajouté à la liste des blocs désactivés dans config.
```

**Mode non-interactif** :
```bash
# Désactiver
php artisan page-content-manager:block:disable hero --force
php artisan page-content-manager:block:disable hero -f

# Activer
php artisan page-content-manager:block:enable hero --force
php artisan page-content-manager:block:enable hero -f

# Paramètres :
# --force, -f  : Pas de confirmation (requis en mode non-interactif)
```

#### 2.4 Menu interactif principal

**Mode interactif** :
```bash
php artisan page-content-manager:blocks

  ┌─────────────────────────────────────┐
  │  🎨 Gestionnaire de Blocs           │
  ├─────────────────────────────────────┤
  │  1. 📋 Lister les blocs              │
  │  2. ➕ Créer un nouveau bloc          │
  │  3. 🗑️  Désactiver un bloc           │
  │  4. ✅ Activer un bloc                │
  │  5. 🔍 Inspecter un bloc              │
  │  6. 📊 Statistiques                  │
  │  7. 🧪 Valider les blocs              │
  │  0. ❌ Quitter                       │
  └─────────────────────────────────────┘

  Choisissez une option [0-7]:
  > 2
```

**Mode non-interactif** (pour les agents IA) :
```bash
# Exécuter directement une action sans menu
php artisan page-content-manager:blocks list
php artisan page-content-manager:blocks create video --group=media
php artisan page-content-manager:blocks disable hero --force
php artisan page-content-manager:blocks enable hero --force
php artisan page-content-manager:blocks inspect hero
php artisan page-content-manager:blocks stats
php artisan page-content-manager:blocks validate

# Ou avec des sous-commandes dédiées (recommandé)
php artisan page-content-manager:block:list
php artisan page-content-manager:make-block video --group=media
php artisan page-content-manager:block:disable hero --force
```

#### 2.5 Inspecter un bloc

**Mode interactif** :
```bash
php artisan page-content-manager:block:inspect hero

  ┌─────────────────────────────────────┐
  │  🔍 Bloc: hero                       │
  ├─────────────────────────────────────┤
  │  Classe: HeroBlock                   │
  │  Namespace: Xavcha\...\Core          │
  │  Type: hero                          │
  │  Ordre: 10                           │
  │  Groupe: content                     │
  │  Statut: ✅ Actif                    │
  │                                     │
  │  Champs du formulaire:               │
  │  - titre (required)                  │
  │  - description (required)            │
  │  - variant (select)                  │
  │  - image_fond_id (media)             │
  │                                     │
  │  Transformation: ✅ Implémentée      │
  │  Validation: ✅ Implémentée          │
  └─────────────────────────────────────┘
```

**Mode non-interactif** (sortie JSON pour les agents IA) :
```bash
# Sortie JSON pour parsing facile
php artisan page-content-manager:block:inspect hero --json

{
  "type": "hero",
  "class": "HeroBlock",
  "namespace": "Xavcha\\PageContentManager\\Blocks\\Core",
  "order": 10,
  "group": "content",
  "status": "active",
  "fields": [
    {"name": "titre", "type": "text", "required": true},
    {"name": "description", "type": "textarea", "required": true}
  ],
  "has_transform": true,
  "has_validation": true
}

# Options disponibles :
# --json, -j        : Sortie JSON (pour les agents IA)
# --verbose, -v     : Plus de détails
# --show-schema     : Afficher le schéma complet
# --show-transform  : Afficher la méthode transform()
```

#### 2.6 Statistiques

**Mode interactif** :
```bash
php artisan page-content-manager:blocks:stats

  ┌─────────────────────────────────────┐
  │  📊 Statistiques des Blocs           │
  ├─────────────────────────────────────┤
  │  Total: 9 blocs                       │
  │  Core: 7 blocs                        │
  │  Custom: 2 blocs                      │
  │  Actifs: 8 blocs                      │
  │  Désactivés: 1 bloc                   │
  │                                     │
  │  Utilisation dans les pages:         │
  │  hero: 15 pages                       │
  │  text: 23 pages                       │
  │  image: 8 pages                       │
  └─────────────────────────────────────┘
```

**Mode non-interactif** (sortie JSON) :
```bash
php artisan page-content-manager:blocks:stats --json

{
  "success": true,
  "data": {
    "total": 9,
    "core": 7,
    "custom": 2,
    "active": 8,
    "disabled": 1,
    "usage": {
      "hero": 15,
      "text": 23,
      "image": 8
    }
  }
}
```

**Bénéfice** : Expérience développeur améliorée, gestion centralisée, visibilité claire, compatible agents IA.

---

### 3. Validation des blocs au démarrage
**Problème** : Les erreurs dans les blocs ne sont découvertes qu'à l'utilisation.

**Solution** : Valider les blocs au boot du service provider.

```php
// Dans ServiceProvider
public function boot(): void
{
    // ...
    
    if ($this->app->runningInConsole()) {
        $this->validateBlocks();
    }
}
```

**Bénéfice** : Détection précoce des erreurs.

---

### 4. Ordre des blocs dans le Builder
**Problème** : Les blocs apparaissent dans un ordre aléatoire.

**Solution** : Ajouter une propriété `$order` dans BlockInterface.

```php
interface BlockInterface
{
    public static function getOrder(): int; // 0 par défaut
    // ...
}
```

**Bénéfice** : Contrôle sur l'ordre d'affichage.

---

## 📊 Priorité Moyenne

### 5. Groupes/Catégories de blocs
**Problème** : Tous les blocs sont mélangés, difficile de s'y retrouver.

**Solution** : Ajouter un système de groupes.

```php
interface BlockInterface
{
    public static function getGroup(): ?string; // 'content', 'media', 'forms', etc.
}
```

**Bénéfice** : Meilleure organisation dans le Builder Filament.

---

### 6. Facade pour faciliter l'utilisation
**Problème** : Accès au registry nécessite `app(BlockRegistry::class)`.

**Solution** : Créer une Facade.

```php
use Xavcha\PageContentManager\Facades\Blocks;

Blocks::get('hero');
Blocks::all();
Blocks::has('text');
```

**Bénéfice** : API plus propre et intuitive.

---

### 7. Events/Hooks pour personnalisation
**Problème** : Pas de moyen de personnaliser le comportement.

**Solution** : Ajouter des événements.

```php
// Avant transformation
event(new BlockTransforming($blockType, $data));

// Après transformation
event(new BlockTransformed($blockType, $transformedData));
```

**Bénéfice** : Extensibilité accrue.

---

### 8. Validation stricte des données de blocs
**Problème** : Pas de validation que les données correspondent au schéma.

**Solution** : Ajouter une méthode `validate()` dans BlockInterface.

```php
public static function validate(array $data): array; // Retourne les erreurs
```

**Bénéfice** : Données plus fiables.

---

## 🔧 Priorité Basse

### 9. Logging amélioré
**Problème** : Erreurs silencieusement ignorées dans BlockRegistry.

**Solution** : Ajouter des logs détaillés.

```php
Log::debug('Bloc découvert', ['type' => $type, 'class' => $className]);
Log::warning('Bloc ignoré', ['reason' => '...']);
```

**Bénéfice** : Meilleur debugging.

---

### 10. Configuration pour désactiver des blocs
**Problème** : Pour désactiver un bloc, il faut le retirer de la config.

**Solution** : Ajouter une liste `disabled_blocks` dans la config avec gestion via CLI.

```php
// config/page-content-manager.php
'disabled_blocks' => ['faq', 'contact_form'],
```

**Commandes CLI associées** :
```bash
# Désactiver un bloc
php artisan page-content-manager:block:disable faq

# Activer un bloc
php artisan page-content-manager:block:enable faq

# Lister les blocs désactivés
php artisan page-content-manager:blocks --disabled
```

**Bénéfice** : Plus flexible que de retirer de la config, gestion via CLI.

---

### 11. Tests unitaires pour les blocs
**Problème** : Pas de tests pour valider les blocs.

**Solution** : Créer des tests pour chaque bloc core.

```php
class HeroBlockTest extends TestCase
{
    public function test_make_returns_block()
    {
        $block = HeroBlock::make();
        $this->assertInstanceOf(Block::class, $block);
    }
    
    public function test_transform_returns_correct_structure()
    {
        $data = ['titre' => 'Test'];
        $transformed = HeroBlock::transform($data);
        $this->assertArrayHasKey('type', $transformed);
    }
}
```

**Bénéfice** : Fiabilité accrue.

---

### 12. Documentation avec exemples visuels
**Problème** : Documentation textuelle uniquement.

**Solution** : Ajouter des screenshots/exemples dans la doc.

**Bénéfice** : Meilleure compréhension.

---

### 13. Support des traductions
**Problème** : Labels des blocs en dur.

**Solution** : Utiliser les traductions Laravel.

```php
->label(__('page-content-manager::blocks.hero.label'))
```

**Bénéfice** : Internationalisation.

---

### 14. API versioning
**Problème** : Pas de versioning pour l'API.

**Solution** : Ajouter un préfixe de version.

```php
/api/v1/pages
/api/v2/pages
```

**Bénéfice** : Compatibilité future.

---

### 15. Rate limiting pour l'API
**Problème** : API publique sans protection.

**Solution** : Ajouter du rate limiting.

```php
Route::middleware(['throttle:60,1'])->group(function () {
    // Routes API
});
```

**Bénéfice** : Protection contre l'abus.

---

## 🎯 Recommandations Immédiates

Pour une version 2.1, je recommande d'implémenter :

1. ✅ **Cache pour BlockRegistry** (Performance)
2. ✅ **CLI Interactif pour la gestion des blocs** (DX) ⭐ **NOUVEAU**
   - Commande `make-block` pour créer un bloc
   - Commande `blocks` avec menu interactif
   - Commandes `disable/enable` pour gérer les blocs
   - Commande `inspect` pour voir les détails
   - Commande `stats` pour les statistiques
3. ✅ **Ordre des blocs** (UX)
4. ✅ **Facade** (DX)
5. ✅ **Groupes de blocs** (UX)
6. ✅ **Configuration disabled_blocks** (Flexibilité)

Ces améliorations apportent le plus de valeur avec un effort raisonnable.

## 🛠️ Détails d'Implémentation du CLI

### Structure des commandes

```
php artisan page-content-manager:blocks          # Menu interactif principal
php artisan page-content-manager:make-block      # Créer un bloc (interactif)
php artisan page-content-manager:block:list       # Lister les blocs
php artisan page-content-manager:block:inspect    # Inspecter un bloc
php artisan page-content-manager:block:disable   # Désactiver un bloc
php artisan page-content-manager:block:enable    # Activer un bloc
php artisan page-content-manager:blocks:stats     # Statistiques
php artisan page-content-manager:blocks:validate  # Valider tous les blocs
```

### Exemple de commande make-block

La commande génère un fichier de bloc avec :
- Structure de base complète
- Méthodes `getType()`, `make()`, `transform()`
- Trait `HasMediaTransformation` si demandé
- Commentaires et exemples
- Validation de base

### Bibliothèques recommandées

- **Laravel Prompts** (inclus dans Laravel 11+) pour l'interactivité
- **Symfony Console** pour les tableaux et le formatage
- **Termwind** pour le styling (optionnel, mais beau)

### Exemple de sortie formatée

```php
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Laravel\Prompts\{select, text, confirm, info, warning};

// Utilisation de tableaux formatés
$table = new Table($this->output);
$table->setHeaders(['Type', 'Classe', 'Statut', 'Ordre']);
$table->setRows([
    ['hero', 'HeroBlock', '✅ Actif', '10'],
    ['text', 'TextBlock', '✅ Actif', '20'],
]);
$table->render();
```

### Structure de fichiers générés par make-block

La commande `make-block` génère un fichier complet avec :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
// use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation; // Si demandé

class VideoBlock implements BlockInterface
{
    // use HasMediaTransformation; // Si demandé

    public static function getType(): string
    {
        return 'video';
    }

    public static function getOrder(): int
    {
        return 100; // Ordre d'affichage dans le Builder
    }

    public static function getGroup(): ?string
    {
        return 'media'; // Groupe pour organiser les blocs
    }

    public static function make(): Block
    {
        return Block::make('video')
            ->label('Vidéo')
            ->icon('heroicon-o-video-camera')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                // Ajoutez vos champs ici
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'video',
            'titre' => $data['titre'] ?? '',
            // Ajoutez votre logique de transformation ici
        ];
    }
}
```

### Gestion de la configuration disabled_blocks

La commande `disable/enable` modifie automatiquement le fichier de configuration :

```php
// Avant
'disabled_blocks' => [],

// Après php artisan page-content-manager:block:disable faq
'disabled_blocks' => ['faq'],
```

Le BlockRegistry respecte automatiquement cette liste et ignore les blocs désactivés.

### Exemple de menu interactif complet

```bash
$ php artisan page-content-manager:blocks

  ╔═══════════════════════════════════════════════════════╗
  ║                                                       ║
  ║     🎨  Gestionnaire de Blocs - Page Content Manager ║
  ║                                                       ║
  ╚═══════════════════════════════════════════════════════╝

  ┌───────────────────────────────────────────────────────┐
  │  📋 Actions disponibles                               │
  ├───────────────────────────────────────────────────────┤
  │  1. 📋 Lister tous les blocs                           │
  │  2. ➕ Créer un nouveau bloc                           │
  │  3. 🗑️  Désactiver un bloc                            │
  │  4. ✅ Activer un bloc                                 │
  │  5. 🔍 Inspecter un bloc en détail                    │
  │  6. 📊 Afficher les statistiques                      │
  │  7. 🧪 Valider tous les blocs                         │
  │  8. 🔄 Rafraîchir le cache des blocs                 │
  │  0. ❌ Quitter                                        │
  └───────────────────────────────────────────────────────┘

  Choisissez une option [0-8]: 
```

### Commandes avec options avancées

```bash
# Lister avec filtres
php artisan page-content-manager:block:list --core
php artisan page-content-manager:block:list --custom
php artisan page-content-manager:block:list --disabled
php artisan page-content-manager:block:list --group=media

# Créer avec options
php artisan page-content-manager:make-block Video --group=media --with-media --order=50

# Inspecter avec détails
php artisan page-content-manager:block:inspect hero --verbose
php artisan page-content-manager:block:inspect hero --show-schema
php artisan page-content-manager:block:inspect hero --show-transform
```

### Validation des blocs

La commande `blocks:validate` vérifie :
- ✅ Toutes les méthodes requises existent
- ✅ Le type retourné par `getType()` correspond au nom de classe
- ✅ La méthode `make()` retourne un Block valide
- ✅ La méthode `transform()` retourne un array avec 'type'
- ✅ Pas de conflits de types entre blocs
- ✅ Les blocs désactivés ne sont pas utilisés dans les pages

**Mode interactif** :
```bash
$ php artisan page-content-manager:blocks:validate

  🔍 Validation des blocs en cours...

  ✅ hero - OK
  ✅ text - OK
  ⚠️  video - Avertissement: méthode transform() retourne un type incorrect
  ❌ old_block - Erreur: méthode getType() manquante

  Résumé:
  - 6 blocs valides
  - 1 bloc avec avertissement
  - 1 bloc avec erreur
```

**Mode non-interactif** (JSON pour agents IA) :
```bash
$ php artisan page-content-manager:blocks:validate --json

{
  "success": false,
  "valid": 6,
  "warnings": 1,
  "errors": 1,
  "results": [
    {
      "type": "hero",
      "status": "valid",
      "errors": [],
      "warnings": []
    },
    {
      "type": "video",
      "status": "warning",
      "errors": [],
      "warnings": ["méthode transform() retourne un type incorrect"]
    },
    {
      "type": "old_block",
      "status": "error",
      "errors": ["méthode getType() manquante"],
      "warnings": []
    }
  ]
}
```

### Tableau récapitulatif des options non-interactives

| Commande | Paramètres non-interactifs | Sortie JSON |
|----------|---------------------------|-------------|
| `make-block` | `--name`, `--group`, `--with-media`, `--order`, `--force` | ❌ |
| `block:list` | `--core`, `--custom`, `--disabled`, `--group=X` | ✅ `--json` |
| `block:inspect` | `{type}` (requis) | ✅ `--json` |
| `block:disable` | `{type}` + `--force` | ❌ |
| `block:enable` | `{type}` + `--force` | ❌ |
| `blocks:stats` | Aucun paramètre requis | ✅ `--json` |
| `blocks:validate` | Aucun paramètre requis | ✅ `--json` |

### Exemple d'utilisation par un Agent IA

```bash
# 1. Lister tous les blocs disponibles
php artisan page-content-manager:block:list --json

# 2. Créer un nouveau bloc
php artisan page-content-manager:make-block testimonial \
  --group=content \
  --order=50 \
  --force

# 3. Inspecter le bloc créé
php artisan page-content-manager:block:inspect testimonial --json

# 4. Valider tous les blocs
php artisan page-content-manager:blocks:validate --json

# 5. Obtenir les statistiques
php artisan page-content-manager:blocks:stats --json
```

Toutes ces commandes peuvent être exécutées sans interaction humaine, parfait pour les agents IA ! 🤖

### 📋 Guide Complet pour Agents IA

#### Règles de Détection du Mode Non-Interactif

1. **Paramètre requis fourni** → Mode non-interactif activé automatiquement
2. **Flag `--force` ou `-f`** → Pas de confirmation (requis pour disable/enable)
3. **Flag `--json` ou `-j`** → Sortie JSON structurée
4. **Flag `--no-interaction` ou `-n`** → Force le mode non-interactif même sans paramètres

#### Liste Complète des Commandes Non-Interactives

```bash
# ============================================
# CRÉATION ET GESTION
# ============================================

# Créer un bloc (tous les paramètres optionnels sauf --name)
php artisan page-content-manager:make-block {name} \
  [--group=content|media|forms|other] \
  [--with-media] \
  [--order=100] \
  [--force] \
  [--namespace=App\\Blocks\\Custom]

# Exemple complet
php artisan page-content-manager:make-block testimonial \
  --group=content \
  --order=50 \
  --force

# ============================================
# LISTAGE ET INSPECTION
# ============================================

# Lister tous les blocs (JSON)
php artisan page-content-manager:block:list --json

# Lister avec filtres (JSON)
php artisan page-content-manager:block:list --json --core
php artisan page-content-manager:block:list --json --custom
php artisan page-content-manager:block:list --json --disabled
php artisan page-content-manager:block:list --json --group=media

# Inspecter un bloc (JSON)
php artisan page-content-manager:block:inspect {type} --json
php artisan page-content-manager:block:inspect hero --json --verbose

# ============================================
# ACTIVATION/DÉSACTIVATION
# ============================================

# Désactiver un bloc (--force requis)
php artisan page-content-manager:block:disable {type} --force
php artisan page-content-manager:block:disable hero --force

# Activer un bloc (--force requis)
php artisan page-content-manager:block:enable {type} --force
php artisan page-content-manager:block:enable hero --force

# ============================================
# STATISTIQUES ET VALIDATION
# ============================================

# Statistiques (JSON)
php artisan page-content-manager:blocks:stats --json

# Valider tous les blocs (JSON)
php artisan page-content-manager:blocks:validate --json

# ============================================
# MENU INTERACTIF (avec action directe)
# ============================================

# Exécuter une action directement sans menu
php artisan page-content-manager:blocks list
php artisan page-content-manager:blocks create {name} --group=X
php artisan page-content-manager:blocks disable {type} --force
php artisan page-content-manager:blocks enable {type} --force
php artisan page-content-manager:blocks inspect {type}
php artisan page-content-manager:blocks stats
php artisan page-content-manager:blocks validate
```

#### Format JSON Standardisé

Toutes les sorties JSON suivent ce format :

```json
{
  "success": true|false,
  "data": { ... },
  "errors": ["erreur1", "erreur2"],
  "warnings": ["avertissement1"],
  "message": "Message optionnel"
}
```

#### Codes de Sortie

- `0` : Succès
- `1` : Erreur générale
- `2` : Paramètres invalides
- `3` : Bloc non trouvé
- `4` : Erreur de validation

#### Exemple de Workflow Complet pour Agent IA

```bash
#!/bin/bash
# Workflow automatisé pour créer et valider un bloc

# 1. Vérifier les blocs existants
php artisan page-content-manager:block:list --json > blocks.json

# 2. Créer un nouveau bloc
php artisan page-content-manager:make-block testimonial \
  --group=content \
  --order=50 \
  --force

# 3. Vérifier que le bloc a été créé
php artisan page-content-manager:block:inspect testimonial --json > block_info.json

# 4. Valider tous les blocs
php artisan page-content-manager:blocks:validate --json > validation.json

# 5. Obtenir les statistiques finales
php artisan page-content-manager:blocks:stats --json > stats.json
```

Toutes les commandes sont **100% non-interactives** quand les paramètres appropriés sont fournis ! 🚀

