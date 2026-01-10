# Architecture des Blocs

## Nouvelle Architecture Simplifiée

Depuis la version 2.0, le système de blocs a été simplifié. Chaque bloc est maintenant un seul fichier qui contient à la fois :
- Le formulaire Filament (méthode `make()`)
- La transformation pour l'API (méthode `transform()`)

## Structure

```
src/Blocks/
├── Contracts/
│   └── BlockInterface.php      # Interface que tous les blocs doivent implémenter
├── Concerns/
│   └── HasMediaTransformation.php  # Trait pour les helpers de transformation média
├── Core/                        # Blocs par défaut du package
│   ├── HeroBlock.php
│   ├── TextBlock.php
│   └── ...
├── BlockRegistry.php           # Auto-découverte des blocs
└── SectionTransformer.php     # Service de transformation des sections
```

## Créer un Bloc Personnalisé

### Méthode 1 : Utiliser la commande CLI (Recommandé)

La méthode la plus simple est d'utiliser la commande `make-block` :

```bash
# Mode interactif
php artisan page-content-manager:make-block

# Mode non-interactif
php artisan page-content-manager:make-block mon-bloc \
  --group=content \
  --order=50 \
  --force
```

La commande génère automatiquement le fichier avec toute la structure nécessaire.

Voir [README.md](../README.md#cli-interactif-pour-la-gestion-des-blocs) pour plus de détails.

### Méthode 2 : Créer manuellement

### 1. Créer le fichier du bloc

Créez votre bloc dans `app/Blocks/Custom/MonBloc.php` :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    /**
     * Retourne le type unique du bloc.
     */
    public static function getType(): string
    {
        return 'mon_bloc';
    }

    /**
     * Crée le schéma Filament pour le formulaire.
     */
    public static function make(): Block
    {
        return Block::make('mon_bloc')
            ->label('Mon Bloc')
            ->icon('heroicon-o-star')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Transforme les données pour l'API.
     */
    public static function transform(array $data): array
    {
        return [
            'type' => 'mon_bloc',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
        ];
    }
}
```

### 2. C'est tout !

Le bloc est automatiquement découvert et disponible dans Filament. Aucune configuration nécessaire !

## Bloc avec Médias

Si votre bloc utilise des médias, utilisez le trait `HasMediaTransformation` :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;
use Xavier\MediaLibraryPro\Forms\Components\MediaPickerUnified;

class ImageBloc implements BlockInterface
{
    use HasMediaTransformation;

    public static function getType(): string
    {
        return 'image_bloc';
    }

    public static function make(): Block
    {
        return Block::make('image_bloc')
            ->label('Bloc Image')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required(),

                MediaPickerUnified::make('image_id')
                    ->label('Image')
                    ->collection('images')
                    ->single()
                    ->required(),
            ]);
    }

    public static function transform(array $data): array
    {
        $imageUrl = static::getMediaFileUrl($data['image_id'] ?? null);

        return [
            'type' => 'image_bloc',
            'titre' => $data['titre'] ?? '',
            'image' => $imageUrl,
        ];
    }
}
```

## Avantages de la Nouvelle Architecture

1. **Un seul fichier** : Formulaire + transformation dans le même endroit
2. **Auto-découverte** : Pas besoin de configuration
3. **Plus simple** : Moins de fichiers à gérer
4. **Plus maintenable** : Tout est au même endroit
5. **Type-safe** : Interface claire avec `BlockInterface`

## Migration depuis l'Ancien Système

Si vous avez des blocs dans l'ancien système :

1. **Ancien** : `app/Filament/Forms/Components/Blocks/Custom/MonBloc.php` + `app/Services/Blocks/Transformers/Custom/MonBlocTransformer.php`
2. **Nouveau** : `app/Blocks/Custom/MonBloc.php` (un seul fichier)

Copiez simplement le code du formulaire dans `make()` et le code du transformer dans `transform()`.

## Désactiver un Bloc Core

Pour désactiver un bloc core, vous pouvez :

1. **Option 1 (Recommandé)** : Utiliser la commande CLI
   ```bash
   php artisan page-content-manager:block:disable faq --force
   ```

2. **Option 2** : Ajouter manuellement dans `config/page-content-manager.php` :
   ```php
   'disabled_blocks' => ['faq', 'contact_form'],
   ```

3. **Option 3** : Créer votre propre bloc avec le même type dans `app/Blocks/Custom/` (il remplacera le bloc core)

Pour réactiver un bloc :

## Événements de transformation

Le package expose deux événements Laravel pour personnaliser le processus de transformation des blocs :

### BlockTransforming

Déclenché **avant** la transformation d'un bloc. Permet de modifier les données brutes avant qu'elles ne soient transformées.

```php
use Xavcha\PageContentManager\Events\BlockTransforming;
use Illuminate\Support\Facades\Event;

Event::listen(BlockTransforming::class, function (BlockTransforming $event) {
    if ($event->blockType === 'hero') {
        $data = $event->getData();
        $data['custom_field'] = 'valeur personnalisée';
        $event->setData($data);
    }
});
```

### BlockTransformed

Déclenché **après** la transformation d'un bloc. Permet de modifier les données transformées avant qu'elles ne soient retournées.

```php
use Xavcha\PageContentManager\Events\BlockTransformed;
use Illuminate\Support\Facades\Event;

Event::listen(BlockTransformed::class, function (BlockTransformed $event) {
    $transformedData = $event->getTransformedData();
    $transformedData['metadata'] = [
        'transformed_at' => now()->toIso8601String(),
    ];
    $event->setTransformedData($transformedData);
});
```

### Cas d'usage

- **Enrichissement de données** : Ajouter des informations depuis une API externe ou la base de données
- **Logging et analytics** : Tracker l'utilisation des blocs
- **Validation personnalisée** : Valider les données avant transformation
- **A/B testing** : Modifier le contenu selon des règles métier
- **Multi-tenant** : Adapter les données selon le tenant

Voir [README.md](../README.md#événements-pour-personnaliser-la-transformation) et [docs/improvements.md](improvements.md#5-eventshooks-pour-personnalisation) pour plus d'exemples.

Pour réactiver un bloc :
```bash
php artisan page-content-manager:block:enable faq --force
```

## Système de Cache

Depuis la version 0.2.1, le `BlockRegistry` utilise un système de cache pour améliorer les performances. La liste des blocs découverts est mise en cache pour éviter de scanner les fichiers à chaque requête.

### Configuration

Le cache est configurable dans `config/page-content-manager.php` :

```php
'cache' => [
    'enabled' => env('PAGE_CONTENT_MANAGER_CACHE_ENABLED', true),
    'key' => 'page-content-manager.blocks.registry',
    'ttl' => env('PAGE_CONTENT_MANAGER_CACHE_TTL', 3600), // 1 heure par défaut
],
```

### Comportement

- **En production** : Le cache est activé par défaut pour améliorer les performances
- **En développement local** : Le cache est automatiquement désactivé pour détecter immédiatement les nouveaux blocs
- **TTL** : Par défaut, le cache expire après 1 heure (3600 secondes)

### Invalider le Cache

Pour invalider manuellement le cache des blocs :

```bash
php artisan page-content-manager:blocks:clear-cache
```

**Quand invalider le cache ?**
- Après avoir créé un nouveau bloc personnalisé en production
- Après avoir modifié un bloc existant
- Si un bloc n'apparaît pas dans le Builder Filament

**Note** : En environnement `local`, le cache est automatiquement désactivé, donc vous n'avez pas besoin de l'invalider manuellement.

## Groupes de Blocs et Ordre Personnalisé

Depuis la version 0.2.3, vous pouvez organiser les blocs en groupes et définir leur ordre d'affichage dans le Builder Filament.

### Configuration des Groupes

**1. Publier la configuration** :
```bash
php artisan vendor:publish --tag=page-content-manager-config
```

**2. Modifier `config/page-content-manager.php`** dans votre projet :

```php
'block_groups' => [
    // Groupe par défaut pour les Pages
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\GalleryBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\CTABlock::class,
            \Xavcha\PageContentManager\Blocks\Core\FAQBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ContactFormBlock::class,
            // Vos blocs personnalisés
            \App\Blocks\Custom\VideoBlock::class,
            \App\Blocks\Custom\TestimonialBlock::class,
        ],
    ],
    
    // Groupe pour une autre ressource (ex: Articles)
    'articles' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            \App\Blocks\Custom\AuthorBlock::class,
            \App\Blocks\Custom\RelatedArticlesBlock::class,
        ],
    ],
],
```

### Utilisation dans les Ressources Filament

```php
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

// Pour les Pages (groupe par défaut)
ContentTab::make() // Utilise le groupe 'pages'

// Pour une autre ressource avec un groupe spécifique
ContentTab::make('articles') // Utilise uniquement les blocs du groupe 'articles'
```

### Avantages

- ✅ **Ordre personnalisé** : Les blocs apparaissent dans l'ordre défini dans la configuration
- ✅ **Groupes contextuels** : Chaque ressource peut avoir ses propres blocs et ordre
- ✅ **Configuration centralisée** : Tout est dans un seul fichier facilement modifiable
- ✅ **Sélectivité** : Chaque groupe peut n'inclure que les blocs pertinents
- ✅ **Pas de modification du code** : Toute la personnalisation se fait via la configuration

### Rétrocompatibilité

- Si aucun groupe n'est spécifié, le groupe `pages` est utilisé par défaut
- Si le groupe n'existe pas, tous les blocs disponibles sont affichés dans l'ordre de découverte
- Les blocs désactivés globalement sont automatiquement exclus de tous les groupes

## Facade Blocks

Pour faciliter l'accès au `BlockRegistry`, une Facade `Blocks` est disponible :

```php
use Xavcha\PageContentManager\Facades\Blocks;

// Récupérer un bloc par son type
$blockClass = Blocks::get('hero');

// Récupérer tous les blocs
$allBlocks = Blocks::all();

// Vérifier si un bloc existe
if (Blocks::has('text')) {
    // ...
}

// Enregistrer un bloc manuellement
Blocks::register('custom_block', \App\Blocks\Custom\MyBlock::class);

// Nettoyer le cache
Blocks::clearCache();
```

**Méthodes disponibles** :
- `get(string $type): ?string` - Récupère la classe d'un bloc par son type
- `all(): array` - Récupère tous les blocs enregistrés
- `has(string $type): bool` - Vérifie si un bloc est enregistré
- `register(string $type, string $blockClass): void` - Enregistre un bloc manuellement
- `clearCache(): void` - Invalide le cache des blocs

**Alternative** : Si vous préférez l'injection de dépendances, vous pouvez toujours utiliser :
```php
use Xavcha\PageContentManager\Blocks\BlockRegistry;

public function __construct(BlockRegistry $registry)
{
    $this->registry = $registry;
}
```

## Validation des Blocs

### Validation au démarrage

Pour détecter les erreurs dans vos blocs dès le démarrage de l'application, vous pouvez activer la validation automatique.

**Configuration** :

Dans votre fichier `.env` :
```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW=false
```

Ou dans `config/page-content-manager.php` :
```php
'validate_blocks_on_boot' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT', false),
'validate_blocks_on_boot_throw' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW', false),
```

**Comportement** :
- **Désactivée par défaut** : Pour ne pas impacter les performances en production
- **Recommandée en développement** : Pour détecter les erreurs tôt
- **Logging** : Les erreurs et avertissements sont loggés par défaut
- **Exception optionnelle** : Activez `validate_blocks_on_boot_throw` pour lancer une exception en cas d'erreur

**Ce qui est validé** :
- ✅ Existence de la classe
- ✅ Implémentation de `BlockInterface`
- ✅ Présence des méthodes requises (`getType`, `make`, `transform`)
- ✅ Les méthodes sont statiques
- ✅ `getType()` retourne le bon type
- ✅ `make()` retourne une instance valide de Block
- ✅ `transform()` retourne un array avec la clé 'type'

### Validation manuelle via CLI

Vous pouvez aussi valider vos blocs manuellement à tout moment :

```bash
# Validation interactive
php artisan page-content-manager:blocks:validate

# Validation avec sortie JSON
php artisan page-content-manager:blocks:validate --json
```

Voir [README.md](../README.md#cli-interactif-pour-la-gestion-des-blocs) pour plus de détails sur les commandes CLI.



