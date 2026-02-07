# Dependances

## Media library

Ce package depend de `xavcha/fillament-xavcha-media-library`.
Si Composer ne la trouve pas, ajoutez le repository VCS dans `composer.json` :

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

Puis :

```bash
composer require xavcha/page-content-manager
```

## Versioning

Voir `composer.json` pour les versions exactes.
