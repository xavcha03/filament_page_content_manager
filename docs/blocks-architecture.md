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

1. **Option 1** : Le retirer de la configuration dans `config/page-content-manager.php`
2. **Option 2** : Créer votre propre bloc avec le même type dans `app/Blocks/Custom/` (il remplacera le bloc core)

