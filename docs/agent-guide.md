# Guide Agent IA (Backend)

Ce guide est la source de verite pour utiliser ce package via MCP/CLI et pour ajouter/modifier des blocs.

## Regles obligatoires

1. Ne jamais supposer la liste des blocs.
   - Toujours appeler `list_blocks`.
2. Ne jamais inventer les champs d'un bloc.
   - Toujours appeler `get_block_schema`.
3. Ne jamais ecraser un bloc pour une petite modif.
   - Utiliser `update_block_fields`.
4. Respecter les blocs desactives et les groupes.
5. Les medias doivent etre uploadees via Filament (utiliser leurs IDs).

## Workflow IA recommande

### Generer une page complete

1. `list_blocks`
2. `get_block_schema` pour chaque bloc choisi
3. `create_page_with_blocks`
4. `get_page_content` pour verifier

### Modifier un champ d'un bloc existant

1. `get_page_content` (pour indices)
2. `update_block_fields` (modifier uniquement les champs necessaires)

### Ajouter un bloc

1. `list_blocks` + `get_block_schema`
2. `add_blocks_to_page`

### Reordonner des blocs

1. `get_page_content`
2. `reorder_blocks`

## Creer un nouveau bloc (custom)

1. Creer un fichier dans `app/Blocks/Custom/MonBloc.php`
2. Implementer `BlockInterface` (`getType`, `make`, `transform`)
3. Ajouter `HasMcpMetadata` pour exposer schema + exemples
4. (Optionnel) Ajouter `HasMediaTransformation` si medias
5. Si besoin d'ordre, ajouter le bloc dans `block_groups`

## Desactiver un bloc core

Options :
- CLI : `php artisan page-content-manager:block:disable hero --force`
- Config : `disabled_blocks` dans `config/page-content-manager.php`
- Groups : ne pas inclure le bloc dans le groupe utilise

## Points d'attention

- La liste des blocs change selon le projet (base_site ou un autre).
- Un bloc custom peut remplacer un bloc core si `getType()` identique.
- Ne jamais ecrire d'URLs d'image en MCP. Utiliser les IDs media.

## Tests minimum

- `php artisan page-content-manager:blocks:validate`
- Verification API `/api/pages/{slug}`

