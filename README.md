# Xavcha Page Content Manager

[![Version](https://img.shields.io/badge/version-0.2.4-blue.svg)](https://github.com/xavcha03/page-content-manager)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Package Laravel Filament professionnel pour gérer les pages avec un système de blocs de contenu flexible et réutilisable.

> **Note** : Ce package est actuellement en version **0.2.4** (pré-v1.0). L'API peut encore évoluer avant la version stable.

## ✨ Fonctionnalités

- 📄 **Ressource Filament complète** pour gérer les pages
- 🧩 **Système de blocs modulaire** (Hero, Text, Image, Gallery, CTA, FAQ, Contact Form)
- 🔌 **Routes API** pour récupérer les pages et leur contenu transformé
- 🎨 **CLI interactif** pour la gestion des blocs (création, inspection, validation, etc.)
- 🔍 **Validation des blocs au démarrage** pour détecter les erreurs tôt
- 🔄 **Système réutilisable** pour ajouter SEO et Content à d'autres ressources Filament
- 🎨 **Transformers personnalisables** pour chaque bloc
- ⚙️ **Configuration flexible** et extensible

## 📦 Installation

### Dépendance requise

Ce package nécessite `xavcha/fillament-xavcha-media-library` disponible sur GitHub.

#### Si la media library n'est PAS installée

**Ajoutez le repository dans votre `composer.json`** :

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/xavcha03/fillament_xavcha_media_library"
    }
  ]
}
```

Puis installez :
```bash
composer require xavcha/page-content-manager
```

#### Si la media library est DÉJÀ installée

Si vous avez déjà la media library installée et que Composer a des problèmes :

```bash
composer require xavcha/page-content-manager --no-update
composer update xavcha/page-content-manager --with-dependencies
```

Voir [Gestion des Dépendances](docs/dependencies.md) pour plus de détails et le dépannage complet.

### Installation du package

```bash
composer require xavcha/page-content-manager
```

Publier la configuration :

```bash
php artisan vendor:publish --tag=page-content-manager-config
```

Exécuter les migrations :

```bash
php artisan migrate
```

## 🚀 Utilisation rapide

### Ressource Page

**IMPORTANT** : Après l'installation, vous devez enregistrer manuellement la ressource dans votre `PanelProvider` :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel
        ->resources([
            PageResource::class,
        ]);
}
```

Voir [Guide d'installation](docs/installation.md) pour plus de détails.

### API

Le package expose deux routes API :

- `GET /api/pages` - Liste toutes les pages publiées
- `GET /api/pages/{slug}` - Récupère une page par son slug

Exemple de réponse :

```json
{
  "id": 1,
  "title": "Accueil",
  "slug": "home",
  "type": "home",
  "seo_title": "Page d'accueil",
  "seo_description": "Description SEO",
  "sections": [
    {
      "type": "hero",
      "data": {
        "titre": "Bienvenue",
        "description": "Description du hero",
        "variant": "hero",
        "image_fond": "https://example.com/image.jpg"
      }
    }
  ],
  "metadata": {
    "schema_version": 1
  }
}
```

## 🎨 Personnalisation

### Désactiver un bloc Core

**Méthode 1 : Via CLI (recommandé)**
```bash
php artisan page-content-manager:block:disable hero --force
```

**Méthode 2 : Via configuration**

Dans `config/page-content-manager.php`, ajoutez le bloc à la liste `disabled_blocks` :

```php
'disabled_blocks' => ['hero'],
```

### Créer un bloc personnalisé

Créez votre bloc dans `app/Blocks/Custom/` - **un seul fichier** contient le formulaire ET la transformation :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    public static function getType(): string
    {
        return 'mon_bloc';
    }

    public static function make(): Block
    {
        return Block::make('mon_bloc')
            ->label('Mon Bloc')
            ->icon('heroicon-o-star')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'mon_bloc',
            'titre' => $data['titre'] ?? '',
        ];
    }
}
```

**C'est tout !** Le bloc est automatiquement découvert et disponible. Aucune configuration nécessaire.

### CLI Interactif pour la gestion des blocs

Le package inclut un système de commandes CLI complet pour gérer vos blocs :

#### Menu interactif principal

```bash
php artisan page-content-manager:blocks
```

Affiche un menu interactif avec toutes les options disponibles.

#### Créer un nouveau bloc

**Mode interactif** :
```bash
php artisan page-content-manager:make-block
```

**Mode non-interactif** (pour les agents IA) :
```bash
php artisan page-content-manager:make-block video \
  --group=media \
  --with-media \
  --order=50 \
  --force
```

#### Lister les blocs

```bash
# Liste tous les blocs
php artisan page-content-manager:block:list

# Filtrer par type
php artisan page-content-manager:block:list --core
php artisan page-content-manager:block:list --custom
php artisan page-content-manager:block:list --disabled
php artisan page-content-manager:block:list --group=media

# Sortie JSON (pour les agents IA)
php artisan page-content-manager:block:list --json
```

#### Inspecter un bloc

```bash
php artisan page-content-manager:block:inspect hero

# Avec plus de détails
php artisan page-content-manager:block:inspect hero --detailed --show-schema

# Sortie JSON
php artisan page-content-manager:block:inspect hero --json
```

#### Activer/Désactiver un bloc

```bash
# Désactiver un bloc
php artisan page-content-manager:block:disable faq --force

# Activer un bloc
php artisan page-content-manager:block:enable faq --force
```

#### Statistiques

```bash
php artisan page-content-manager:blocks:stats

# Sortie JSON
php artisan page-content-manager:blocks:stats --json
```

#### Valider tous les blocs

```bash
php artisan page-content-manager:blocks:validate

# Sortie JSON
php artisan page-content-manager:blocks:validate --json
```

#### Autres commandes

```bash
# Invalider le cache des blocs
php artisan page-content-manager:blocks:clear-cache
```

Toutes les commandes supportent le mode non-interactif avec sortie JSON pour une utilisation automatisée (agents IA, scripts, CI/CD).

### Validation des blocs au démarrage

Pour détecter les erreurs dans vos blocs dès le démarrage de l'application, vous pouvez activer la validation automatique :

**Dans votre `.env`** :
```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true
```

**Pour lancer une exception en cas d'erreur** :
```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW=true
```

**Configuration dans `config/page-content-manager.php`** :
```php
'validate_blocks_on_boot' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT', false),
'validate_blocks_on_boot_throw' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW', false),
```

**Note** : La validation est désactivée par défaut pour ne pas impacter les performances en production. Activez-la en développement pour détecter les erreurs tôt.

La validation vérifie :
- ✅ Que toutes les méthodes requises existent (`getType`, `make`, `transform`)
- ✅ Que les méthodes sont statiques
- ✅ Que `getType()` retourne le bon type
- ✅ Que `make()` retourne une instance valide de Block
- ✅ Que `transform()` retourne un array avec la clé 'type'

Les erreurs sont loggées par défaut. Si `validate_blocks_on_boot_throw` est activé, une exception sera lancée en cas d'erreur.

### Groupes de blocs et ordre personnalisé

Pour organiser les blocs et définir leur ordre d'affichage dans le Builder Filament, vous pouvez utiliser le système de groupes de blocs.

**1. Publier la configuration** (si ce n'est pas déjà fait) :
```bash
php artisan vendor:publish --tag=page-content-manager-config
```

**2. Configurer les groupes dans `config/page-content-manager.php`** :
```php
'block_groups' => [
    // Groupe par défaut pour les Pages
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            // ... autres blocs dans l'ordre souhaité
            \App\Blocks\Custom\VideoBlock::class, // Blocs personnalisés
        ],
    ],
    
    // Créer un groupe pour une autre ressource
    'articles' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            \App\Blocks\Custom\AuthorBlock::class,
        ],
    ],
],
```

**3. Utiliser le groupe dans vos ressources Filament** :
```php
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

// Pour les Pages (groupe par défaut)
ContentTab::make() // Utilise le groupe 'pages'

// Pour une autre ressource avec un groupe spécifique
ContentTab::make('articles') // Utilise uniquement les blocs du groupe 'articles'
```

**Avantages** :
- ✅ **Ordre personnalisé** : Définissez l'ordre d'affichage des blocs
- ✅ **Groupes contextuels** : Chaque ressource peut avoir ses propres blocs
- ✅ **Configuration centralisée** : Tout dans un seul fichier de config
- ✅ **Sélectivité** : Chaque groupe peut n'inclure que les blocs pertinents
- ✅ **Pas de modification du code** : Tout se fait via la configuration

**Rétrocompatibilité** : Si aucun groupe n'est spécifié ou si le groupe n'existe pas, tous les blocs disponibles seront affichés dans l'ordre de découverte.

### Utiliser la Facade Blocks

Pour accéder facilement au `BlockRegistry` sans passer par `app(BlockRegistry::class)`, vous pouvez utiliser la Facade `Blocks` :

```php
use Xavcha\PageContentManager\Facades\Blocks;

// Récupérer un bloc par son type
$heroBlockClass = Blocks::get('hero');

// Récupérer tous les blocs
$allBlocks = Blocks::all();

// Vérifier si un bloc existe
if (Blocks::has('text')) {
    // Le bloc 'text' est disponible
}

// Enregistrer un bloc manuellement (rarement nécessaire)
Blocks::register('custom_block', \App\Blocks\Custom\MyBlock::class);

// Nettoyer le cache des blocs
Blocks::clearCache();
```

**Avantages** :
- ✅ API plus propre et intuitive
- ✅ Pas besoin d'injecter le service
- ✅ Accès direct depuis n'importe où dans votre code

**Alternative** : Si vous préférez l'injection de dépendances, vous pouvez toujours utiliser :
```php
use Xavcha\PageContentManager\Blocks\BlockRegistry;

public function __construct(BlockRegistry $registry)
{
    $this->registry = $registry;
}
```

## 🔄 Système réutilisable pour autres ressources

Vous pouvez ajouter les onglets SEO et Content à n'importe quelle ressource Filament.

### Exemple : Style de danse

1. **Ajouter les colonnes à la table** :

```bash
php artisan page-content-manager:add-page-detail dance_styles --after=name
```

2. **Mettre à jour le modèle** :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Xavcha\PageContentManager\Models\Concerns\HasPageDetail;

class DanceStyle extends Model
{
    use HasPageDetail;

    protected $fillable = [
        'name',
        'seo_title',
        'seo_description',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
```

3. **Mettre à jour la ressource Filament** :

```php
<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Xavcha\PageContentManager\Filament\Forms\Components\PageDetailTabs;

class DanceStyleResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Tabs::make('tabs')
                    ->tabs([
                        Components\Tabs\Tab::make('general')
                            ->label('Général')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom de la danse')
                                    ->required(),
                            ]),
                        ...PageDetailTabs::tabs(),
                    ]),
            ]);
    }
}
```

**Alternative** : Utiliser les onglets individuellement :

```php
use Xavcha\PageContentManager\Filament\Forms\Components\SeoTab;
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

Components\Tabs::make('tabs')
    ->tabs([
        Components\Tabs\Tab::make('general')
            ->label('Général')
            ->schema([...]),
        SeoTab::make(),
        ContentTab::make(),
    ]),
```

## 📚 Documentation

- [Guide d'installation](docs/installation.md)
- [Gestion des dépendances](docs/dependencies.md) ⚠️ Important
- [Guide d'utilisation](docs/usage.md)
- [Architecture des blocs](docs/blocks-architecture.md) ⭐ Nouveau
- [Créer des blocs personnalisés](docs/custom-blocks.md) - Inclut la commande `make-block`
- [Système réutilisable](docs/reusable-system.md)
- [Documentation API](docs/api.md)
- [Tests](docs/testing.md)
- [Migration v2.0](docs/migration-v2.md)
- [Améliorations proposées](docs/improvements.md) - Roadmap et fonctionnalités

## 🧪 Tests

Le package inclut un environnement de test avec Workbench. Voir [docs/testing.md](docs/testing.md) pour plus de détails.

## 👨‍💻 Bonnes pratiques de développement

### Tests unitaires et fonctionnels

**⚠️ Obligatoire** : Toute nouvelle fonctionnalité ou modification doit être accompagnée de tests.

- **Tests unitaires** : Pour tester les classes isolément (blocs, transformers, traits, etc.)
- **Tests fonctionnels** : Pour tester les intégrations (API, modèles, service provider, etc.)

#### Exécuter les tests

```bash
# Avec ddev
ddev exec vendor/bin/phpunit

# Ou directement
composer test
```

#### Structure des tests

- `tests/Unit/` : Tests unitaires pour les classes isolées
- `tests/Feature/` : Tests fonctionnels pour les intégrations
- `tests/Helpers/` : Helpers réutilisables pour les tests

#### Exemple de test

```php
<?php

namespace Xavcha\PageContentManager\Tests\Unit;

use Xavcha\PageContentManager\Tests\TestCase;

class MonNouveauBlocTest extends TestCase
{
    public function test_get_type_returns_correct_type(): void
    {
        $this->assertEquals('mon_bloc', MonNouveauBloc::getType());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = ['titre' => 'Test'];
        $result = MonNouveauBloc::transform($data);
        
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('mon_bloc', $result['type']);
    }
}
```

### Versionnement

**⚠️ Obligatoire** : Toute version publiée doit être taguée dans Git.

#### Convention de versionnement

Le package suit [Semantic Versioning](https://semver.org/lang/fr/) :

- **0.x.0** : Versions majeures (ajouts de fonctionnalités, changements d'API)
- **0.0.x** : Versions mineures (nouvelles fonctionnalités rétrocompatibles)
- **0.0.0.x** : Versions patch (corrections de bugs)

#### Processus de versionnement

1. **Mettre à jour le CHANGELOG.md** :
   - Ajouter une nouvelle section `[X.Y.Z] - YYYY-MM-DD`
   - Documenter tous les changements (Ajouté, Modifié, Supprimé, Sécurité)

2. **Mettre à jour la version dans `composer.json`** :
   ```json
   {
     "version": "0.2.2"
   }
   ```

3. **Mettre à jour le README.md** :
   - Badge de version
   - Section "Versions" avec la nouvelle version

4. **Créer un commit** :
   ```bash
   git add CHANGELOG.md composer.json README.md
   git commit -m "Version 0.2.1 - Description des changements"
   ```

5. **Créer un tag Git annoté** :
   ```bash
   git tag -a v0.2.1 -m "Version 0.2.1 - Description des changements"
   ```

6. **Pousser le commit et le tag** :
   ```bash
   git push origin main
   git push origin v0.2.1
   ```

### Standards de code

- **PSR-12** : Respecter les standards de codage PHP
- **Type hints** : Utiliser les types stricts (`declare(strict_types=1);`)
- **Documentation** : Documenter les méthodes publiques avec PHPDoc
- **Nommage** : Utiliser des noms explicites et cohérents

### Workflow de développement

1. **Créer une branche** pour la fonctionnalité/correction :
   ```bash
   git checkout -b feature/nouvelle-fonctionnalite
   # ou
   git checkout -b fix/correction-bug
   ```

2. **Développer et tester** :
   - Écrire le code
   - Écrire les tests
   - Vérifier que tous les tests passent

3. **Mettre à jour la documentation** si nécessaire :
   - README.md
   - Documentation dans `docs/`
   - Exemples d'utilisation

4. **Commit** avec un message clair :
   ```bash
   git commit -m "feat: Ajout de la fonctionnalité X"
   # ou
   git commit -m "fix: Correction du bug Y"
   ```

5. **Merge** dans `main` après validation

### Ajout de nouvelles fonctionnalités

Avant d'ajouter une nouvelle fonctionnalité :

1. ✅ Vérifier qu'elle n'existe pas déjà dans [docs/improvements.md](docs/improvements.md)
2. ✅ S'assurer qu'elle est compatible avec l'architecture actuelle
3. ✅ Écrire les tests **avant** ou **pendant** le développement (TDD recommandé)
4. ✅ Mettre à jour la documentation
5. ✅ Mettre à jour le CHANGELOG lors de la version

### Modification de l'API

Si une modification casse la rétrocompatibilité :

1. ⚠️ **Augmenter le numéro de version majeure** (ex: 0.2.0 → 0.3.0)
2. ⚠️ **Documenter clairement** les breaking changes dans le CHANGELOG
3. ⚠️ **Fournir un guide de migration** si nécessaire

### Environnement de développement

Le package utilise **DDEV** pour l'environnement PHP :

```bash
# Exécuter les tests
ddev exec vendor/bin/phpunit

# Installer les dépendances
ddev composer install

# Exécuter les commandes Artisan
ddev artisan migrate
```

## 📋 Changelog

Voir [CHANGELOG.md](CHANGELOG.md) pour la liste complète des changements.

## 🔖 Versions

- **0.2.4** (actuelle) - Facade Blocks pour faciliter l'accès au BlockRegistry
- **0.2.3** - Groupes de blocs avec ordre personnalisé, configuration flexible
- **0.2.2** - CLI interactif pour la gestion des blocs, validation des blocs au démarrage
- **0.2.1** - Système de cache pour BlockRegistry, amélioration des performances
- **0.2.0** - Suite complète de tests, améliorations de l'architecture
- **0.1.0** - Version initiale avec fonctionnalités de base

## 📄 Licence

MIT
