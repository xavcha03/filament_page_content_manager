# Xavcha Page Content Manager

Package Laravel Filament professionnel pour gérer les pages avec un système de blocs de contenu flexible et réutilisable.

## ✨ Fonctionnalités

- 📄 **Ressource Filament complète** pour gérer les pages
- 🧩 **Système de blocs modulaire** (Hero, Text, Image, Gallery, CTA, FAQ, Contact Form)
- 🔌 **Routes API** pour récupérer les pages et leur contenu transformé
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

Dans `config/page-content-manager.php`, retirez simplement le bloc de la liste :

```php
'blocks' => [
    'core' => [
        // 'hero' => ..., // Bloc désactivé
        'text' => ...,
        // ...
    ],
],
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
- [Créer des blocs personnalisés](docs/custom-blocks.md)
- [Système réutilisable](docs/reusable-system.md)
- [Documentation API](docs/api.md)
- [Tests](docs/testing.md)
- [Migration v2.0](docs/migration-v2.md)

## 🧪 Tests

Le package inclut un environnement de test avec Workbench. Voir [docs/testing.md](docs/testing.md) pour plus de détails.

## 📄 Licence

MIT
