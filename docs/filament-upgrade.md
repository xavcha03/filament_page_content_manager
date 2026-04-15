# Upgrade Filament (projets consommateurs)

Ce document sert de checklist pour éviter les erreurs du type :

- `Toolbar button [xxx] cannot be found`

Ces erreurs apparaissent typiquement quand les **packages Filament** ont été mis à jour via Composer, mais que les **assets Filament publiés** (JS/CSS dans `public/`) ne correspondent plus.

## Quand lancer ces commandes ?

- Après un `composer update` qui touche `filament/*`
- Après un `composer require` / bump de version Filament
- Quand l’UI Filament “a l’air cassée” (toolbar bizarre, composants qui ne matchent plus, erreurs RichEditor)

## Checklist (Laravel “nu”)

À lancer à la racine du projet Laravel :

```bash
php artisan optimize:clear
php artisan filament:upgrade
php artisan filament:optimize-clear
php artisan filament:assets
```

Puis faire un **hard refresh** navigateur (Ctrl+Shift+R).

## Checklist (DDEV)

```bash
ddev exec php artisan optimize:clear
ddev exec php artisan filament:upgrade
ddev exec php artisan filament:optimize-clear
ddev exec php artisan filament:assets
```

Puis faire un **hard refresh** navigateur.

## Variante “juste republier les assets”

Si tu n’as pas réellement “upgradé” Filament (tu veux juste resynchroniser les assets) :

```bash
php artisan optimize:clear
php artisan filament:assets
```

## Notes

- `filament:assets` republie notamment `public/js/filament/forms/components/rich-editor.js` et `public/css/filament/filament/app.css`.
- `filament:upgrade` applique les étapes de migration recommandées par Filament entre versions.
- `filament:optimize-clear` nettoie les caches Filament (components / blade-icons) qui peuvent garder un état incompatible.

