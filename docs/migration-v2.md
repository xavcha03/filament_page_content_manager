# Migration vers la Version 2.0

## Changements Principaux

La version 2.0 simplifie l'architecture des blocs en combinant le formulaire et la transformation dans un seul fichier.

## Avant (v1.x)

```
app/Filament/Forms/Components/Blocks/Custom/
└── MonBloc.php                    # Formulaire Filament

app/Services/Blocks/Transformers/Custom/
└── MonBlocTransformer.php         # Transformation API
```

## Après (v2.0)

```
app/Blocks/Custom/
└── MonBloc.php                    # Formulaire + Transformation
```

## Guide de Migration

### Étape 1 : Créer le nouveau bloc

Créez `app/Blocks/Custom/MonBloc.php` :

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    public static function getType(): string
    {
        return 'mon_bloc';
    }

    public static function make(): Block
    {
        // Copiez le code de votre ancien MonBloc::make()
        return Block::make('mon_bloc')
            ->label('Mon Bloc')
            ->schema([
                // ... votre schéma
            ]);
    }

    public static function transform(array $data): array
    {
        // Copiez le code de votre ancien MonBlocTransformer::transform()
        return [
            'type' => 'mon_bloc',
            // ... vos données transformées
        ];
    }
}
```

### Étape 2 : Supprimer les anciens fichiers

Une fois le nouveau bloc créé et testé, supprimez :
- `app/Filament/Forms/Components/Blocks/Custom/MonBloc.php`
- `app/Services/Blocks/Transformers/Custom/MonBlocTransformer.php`

### Étape 3 : Mettre à jour la configuration (optionnel)

La configuration dans `config/page-content-manager.php` n'est plus nécessaire pour les nouveaux blocs. Ils sont auto-découverts.

## Rétrocompatibilité

L'ancien système reste fonctionnel pour assurer une migration en douceur. Vous pouvez migrer progressivement vos blocs.

## Blocs Core

Les blocs core du package ont été migrés vers la nouvelle architecture dans `src/Blocks/Core/`. Ils fonctionnent automatiquement sans configuration.




