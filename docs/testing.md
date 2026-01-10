# Guide de test du package

## Méthodes de test

Il existe deux méthodes principales pour tester le package :

1. **Workbench avec Filament** : Pour tester visuellement dans un environnement Laravel complet
2. **Orchestra Testbench** : Pour les tests automatisés unitaires et fonctionnels

## 1. Tester avec Workbench (Tests visuels)

Workbench est un environnement Laravel complet inclus dans le package pour tester visuellement.

### Installation

1. Installer les dépendances dans workbench :

```bash
cd workbench
composer install
```

2. Créer le fichier `.env` si nécessaire :

```bash
cp .env.example .env
php artisan key:generate
```

3. Configurer la base de données dans `.env` :

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

4. Créer la base de données SQLite :

```bash
touch database/database.sqlite
```

5. Exécuter les migrations :

```bash
php artisan migrate
```

6. Créer un utilisateur admin pour Filament :

```bash
php artisan make:filament-user
```

### Lancer le serveur

```bash
cd workbench
php artisan serve
```

Accédez à `http://localhost:8000/admin` et connectez-vous.

Vous devriez voir la ressource **Pages** dans le menu de navigation.

### Avantages

- Test visuel complet de l'interface Filament
- Test de toutes les fonctionnalités interactives
- Débogage facile avec les outils Laravel
- Test des blocs de contenu en conditions réelles

## 2. Tests automatisés avec Orchestra Testbench

Les tests automatisés utilisent Orchestra Testbench pour créer un environnement Laravel isolé.

### Structure des tests

Les tests se trouvent dans le dossier `tests/` :

- `TestCase.php` : Classe de base pour tous les tests
- `Feature/` : Tests fonctionnels
- `Unit/` : Tests unitaires

### Exécuter les tests

```bash
composer test
```

ou

```bash
vendor/bin/phpunit
```

### Exemple de test

```php
<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class PageModelTest extends TestCase
{
    public function test_can_create_page(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'test-page',
            'title' => 'Test Page',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'test-page',
        ]);
    }
}
```

### Configuration des tests

Le `TestCase` configure automatiquement :
- Base de données SQLite en mémoire
- Migrations du package
- Service providers nécessaires
- Configuration Filament de base

## 3. Tests recommandés

### Tests à créer

1. **Modèle Page**
   - Création de page
   - Validation des règles (une seule Home, etc.)
   - Normalisation du contenu
   - Scopes (published, etc.)

2. **API**
   - Liste des pages
   - Récupération par slug
   - Transformation des sections

3. **Transformers**
   - Transformation de chaque type de bloc
   - Gestion des erreurs

4. **Service Provider**
   - Enregistrement des routes
   - Chargement des migrations
   - Configuration

## 4. Workflow de développement recommandé

1. **Développement** : Utiliser workbench pour tester visuellement
2. **Tests automatisés** : Écrire des tests pour chaque fonctionnalité
3. **CI/CD** : Les tests automatisés s'exécutent automatiquement

## 5. Dépannage

### Workbench ne démarre pas

- Vérifier que Filament est installé : `cd workbench && composer install`
- Vérifier la configuration de la base de données dans `.env`
- Vérifier que les migrations sont exécutées

### Tests échouent

- Vérifier que la base de données est bien configurée (SQLite en mémoire)
- Vérifier que les migrations sont chargées
- Vérifier les dépendances dans `composer.json`

### Filament ne découvre pas la ressource

- Vérifier que le PanelProvider est bien enregistré
- Vérifier que la ressource est dans la liste des resources du panel
- Vérifier les logs : `php artisan config:clear`




