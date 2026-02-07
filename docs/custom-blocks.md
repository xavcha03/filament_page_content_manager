# Creer un bloc custom

## Methode rapide (CLI)

```bash
php artisan page-content-manager:make-block video \
  --group=content \
  --order=50 \
  --force
```

Puis editez le fichier genere dans `app/Blocks/Custom/`.

## Methode manuelle

```php
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'mon_bloc';
    }

    public static function make(): Block
    {
        return Block::make('mon_bloc')
            ->label('Mon Bloc')
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

    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Exemple de titre',
        ];
    }
}
```

## Bloc avec medias

Utiliser `HasMediaTransformation` et des IDs MediaFile.
Ne jamais utiliser d'URL directes en MCP.

## Ordre et groupes

Si vous voulez un ordre specifique, ajoutez la classe dans `block_groups`.

## Tests minimum

```bash
php artisan page-content-manager:blocks:validate
```
