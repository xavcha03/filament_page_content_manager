# Repository Guidelines

## Project Structure & Module Organization

- `src/` : package source (blocks, MCP, Filament, models, HTTP, console commands).
- `config/` : package config (`page-content-manager.php`).
- `routes/` : API routes for pages.
- `database/` : migrations.
- `tests/` : PHPUnit tests (unit/feature).
- `docs/` : documentation (start with `docs/index.md`).
- `app/` and `workbench/` : local dev scaffolding.

## Build, Test, and Development Commands

- `composer test` : runs PHPUnit test suite.
- `ddev exec vendor/bin/phpunit` : run tests inside DDEV.
- `php artisan page-content-manager:block:list` : list discovered blocks.
- `php artisan page-content-manager:blocks:validate` : validate block definitions.
- `php artisan vendor:publish --tag=page-content-manager-config` : publish config.

## Coding Style & Naming Conventions

- PHP: PSR-12, `declare(strict_types=1);` where applicable.
- Blocks: one class per file, implements `BlockInterface` with `getType`, `make`, `transform`.
- Block types: lowercase snake_case (e.g. `contact_form`).
- Keep MCP metadata in blocks via `HasMcpMetadata` (fields + example).

## Testing Guidelines

- Framework: PHPUnit.
- Tests live in `tests/Unit/` and `tests/Feature/`.
- Name tests by class/function intent (e.g. `HeroBlockTest`).
- Run `php artisan page-content-manager:blocks:validate` after block changes.

## Commit & Pull Request Guidelines

- Commit style in history: short imperative sentence (e.g. “Refactor README …”, “Add MCP …”).
- PRs should include: brief summary, test status, and doc updates if behavior changes.

## Security & Configuration Tips

- MCP should be protected in production via token and/or auth middleware.
- Disable unwanted blocks with `disabled_blocks` or by omitting them from `block_groups`.
- Don’t hardcode media URLs in MCP; use MediaFile IDs.
