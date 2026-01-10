# Améliorations Proposées

## 🚀 Priorité Haute

### 1. Validation des blocs au démarrage ✅ **IMPLÉMENTÉ**
**Problème** : Les erreurs dans les blocs ne sont découvertes qu'à l'utilisation. Pas de validation que les blocs respectent `BlockInterface` au démarrage.

**Solution** : Valider les blocs au boot du service provider avec option de configuration.

**Statut** : ✅ Implémenté. Voir la configuration dans `config/page-content-manager.php` pour activer la validation.

**Configuration** :
```php
// config/page-content-manager.php
'validate_blocks_on_boot' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT', false),
'validate_blocks_on_boot_throw' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW', false),
```

**Utilisation** :
- Désactivée par défaut pour ne pas impacter les performances en production
- Activez avec `PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true` en développement
- Les erreurs sont loggées par défaut
- Activez `validate_blocks_on_boot_throw=true` pour lancer une exception en cas d'erreur

**Bénéfice** : Détection précoce des erreurs, validation optionnelle pour ne pas impacter les performances en production.

---

### 3. Ordre et groupes des blocs dans le Builder ✅ **IMPLÉMENTÉ**
**Problème** : Les blocs apparaissent dans un ordre aléatoire dans le Builder Filament, ce qui rend difficile la navigation et la sélection des blocs. De plus, tous les blocs sont mélangés sans organisation logique. Quand on utilise le système pour plusieurs ressources (Pages, Articles, etc.), on a besoin de groupes différents avec des blocs et des ordres spécifiques à chaque contexte.

**Solution** : Créer un système de configuration par groupes qui permet de définir l'ordre et la sélection des blocs pour chaque contexte d'utilisation.

**Statut** : ✅ Implémenté dans la version 0.2.3. Voir la documentation dans `README.md` et `docs/blocks-architecture.md`.

**Approche implémentée** : Fichier de configuration centralisé avec groupes nommés, où chaque groupe définit la liste des blocs dans l'ordre souhaité.

**Implémentation** :

1. **Publier la configuration** (une seule fois) :
```bash
php artisan vendor:publish --tag=page-content-manager-config
```

Cela crée le fichier `config/page-content-manager.php` dans votre projet avec la configuration par défaut.

2. **Structure de configuration dans `config/page-content-manager.php`** :

Le fichier de configuration est facilement accessible et modifiable dans votre projet :

```php
'block_groups' => [
    // Groupe par défaut pour les Pages
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\GalleryBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\CtaBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\FaqBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\ContactFormBlock::class,
            // Blocs personnalisés
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
    
    // Groupe minimal pour les landing pages
    'landing' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\CtaBlock::class,
            \App\Blocks\Custom\VideoBlock::class,
        ],
    ],
],
```

**Note** : La configuration par défaut dans le package inclura un groupe `pages` avec tous les blocs core dans un ordre logique. Vous pouvez ensuite personnaliser cette configuration dans votre projet sans modifier le package.

3. **Modifier `ContentTab` pour accepter un groupe** :
```php
class ContentTab
{
    /**
     * Crée un onglet Content avec les blocs d'un groupe spécifique.
     *
     * @param string $group Nom du groupe (défaut: 'pages')
     * @return Components\Tabs\Tab
     */
    public static function make(string $group = 'pages'): Components\Tabs\Tab
    {
        $blocks = self::getBlocksForGroup($group);

        return Components\Tabs\Tab::make('content')
            ->label('Contenu')
            ->schema([
                Forms\Components\Builder::make('content.sections')
                    ->label('Sections')
                    ->blocks($blocks)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Récupère les blocs pour un groupe spécifique.
     *
     * @param string $group
     * @return array
     */
    protected static function getBlocksForGroup(string $group): array
    {
        $config = config('page-content-manager.block_groups', []);
        
        // Si le groupe existe dans la config, utiliser l'ordre défini
        if (isset($config[$group]['blocks']) && is_array($config[$group]['blocks'])) {
            $blocks = [];
            foreach ($config[$group]['blocks'] as $blockClass) {
                if (class_exists($blockClass) && method_exists($blockClass, 'make')) {
                    // Vérifier que le bloc n'est pas désactivé
                    $type = $blockClass::getType();
                    $disabledBlocks = config('page-content-manager.disabled_blocks', []);
                    
                    if (!in_array($type, $disabledBlocks, true)) {
                        $blocks[] = $blockClass::make();
                    }
                }
            }
            return $blocks;
        }
        
        // Fallback : utiliser tous les blocs disponibles (comportement actuel)
        return self::getAllBlocks();
    }
}
```

4. **Utilisation dans les ressources Filament** :
```php
// Pour les Pages (groupe par défaut)
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

ContentTab::make() // Utilise le groupe 'pages' par défaut

// Pour une autre ressource avec un groupe spécifique
ContentTab::make('articles') // Utilise uniquement les blocs du groupe 'articles'

// Pour une landing page
ContentTab::make('landing') // Utilise uniquement les blocs du groupe 'landing'
```

5. **Gestion automatique des blocs non listés** :
- Si un bloc n'est pas dans la liste du groupe, il n'apparaît pas
- Permet de créer des groupes très spécifiques avec seulement les blocs nécessaires
- Les blocs désactivés globalement sont automatiquement exclus

**Avantages de cette approche** :
- ✅ **Flexibilité maximale** : Même bloc peut avoir des ordres différents selon le contexte
- ✅ **Simplicité** : Pas besoin de modifier chaque classe de bloc
- ✅ **Configuration centralisée** : Tout est dans un seul fichier de config
- ✅ **Réutilisabilité** : Créer facilement de nouveaux groupes pour de nouvelles ressources
- ✅ **Sélectivité** : Chaque groupe peut n'inclure que les blocs pertinents
- ✅ **Maintenabilité** : Facile de réorganiser l'ordre sans toucher au code
- ✅ **Contextuel** : Chaque ressource peut avoir son propre ensemble de blocs optimisé

**Exemple de cas d'usage** :
- **Pages** : Tous les blocs dans un ordre logique (Hero → Text → Image → CTA → Form)
- **Articles** : Seulement Text, Image, Author, Related (pas de Hero ni Form)
- **Landing Pages** : Seulement Hero, CTA, Video (focus sur la conversion)
- **Produits** : Seulement Image, Gallery, CTA, FAQ (focus sur la présentation produit)

**Gestion de la configuration dans un package** :

- **Configuration par défaut** : Le package fournit une configuration par défaut dans `config/page-content-manager.php` avec un groupe `pages` contenant tous les blocs core dans un ordre logique
- **Publication facile** : La commande `vendor:publish` copie la config dans votre projet où vous pouvez la modifier librement
- **Personnalisation sans modifier le package** : Toute la personnalisation se fait dans `config/page-content-manager.php` de votre projet, le package reste intact
- **Versioning** : Vous pouvez versionner votre configuration personnalisée dans Git
- **Accès direct** : Le fichier est dans `config/` de votre projet, facilement accessible et modifiable

**Exemple de personnalisation dans votre projet** :

```php
// config/page-content-manager.php (dans votre projet Laravel)
'block_groups' => [
    'pages' => [
        'blocks' => [
            // Réorganiser l'ordre selon vos besoins
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \App\Blocks\Custom\VideoBlock::class, // Bloc custom en deuxième position
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            // ... autres blocs dans l'ordre souhaité
        ],
    ],
    
    // Ajouter un nouveau groupe pour votre ressource
    'products' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\GalleryBlock::class,
            \App\Blocks\Custom\ProductSpecsBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\FaqBlock::class,
        ],
    ],
],
```

**Rétrocompatibilité** : 
- Si aucun groupe n'est spécifié ou si le groupe n'existe pas, utiliser le comportement actuel (tous les blocs disponibles)
- Si la configuration `block_groups` n'existe pas dans votre projet, tous les blocs sont affichés dans l'ordre de découverte
- La configuration est optionnelle : si vous ne la publiez pas, le système fonctionne comme actuellement

---

## 📊 Priorité Moyenne

### 4. Facade pour faciliter l'utilisation
**Problème** : Accès au registry nécessite `app(BlockRegistry::class)`.

**Solution** : Créer une Facade.

```php
use Xavcha\PageContentManager\Facades\Blocks;

Blocks::get('hero');
Blocks::all();
Blocks::has('text');
```

**Bénéfice** : API plus propre et intuitive.

---

### 5. Events/Hooks pour personnalisation
**Problème** : Pas de moyen de personnaliser le comportement.

**Solution** : Ajouter des événements.

```php
// Avant transformation
event(new BlockTransforming($blockType, $data));

// Après transformation
event(new BlockTransformed($blockType, $transformedData));
```

**Bénéfice** : Extensibilité accrue.

---

### 6. Validation stricte des données de blocs
**Problème** : Pas de validation que les données correspondent au schéma.

**Solution** : Ajouter une méthode `validate()` dans BlockInterface (optionnelle pour rétrocompatibilité).

```php
interface BlockInterface
{
    // ... méthodes existantes
    
    /**
     * Valide les données du bloc (optionnel).
     * 
     * @param array $data Les données à valider
     * @return array Tableau vide si valide, sinon tableau d'erreurs
     */
    public static function validate(array $data): array;
}

// Implémentation par défaut dans un trait
trait ValidatesBlockData
{
    public static function validate(array $data): array
    {
        $errors = [];
        
        // Validation basique basée sur le schéma Filament
        $block = static::make();
        $schema = $block->getSchema();
        
        foreach ($schema as $field) {
            if ($field->isRequired() && empty($data[$field->getName()])) {
                $errors[] = "Le champ {$field->getName()} est requis";
            }
        }
        
        return $errors;
    }
}
```

**Bénéfice** : Données plus fiables, validation optionnelle pour ne pas casser la compatibilité.

---

### 6.1. Gestion d'erreurs améliorée pour SectionTransformer
**Problème** : Dans `SectionTransformer`, les erreurs sont loggées mais les données brutes sont retournées silencieusement. Pas de moyen de savoir qu'une transformation a échoué.

**Solution** : Ajouter une option de configuration pour choisir le comportement (fail-safe vs strict).

```php
// config/page-content-manager.php
'transformer' => [
    'error_handling' => 'fail-safe', // 'fail-safe' ou 'strict'
    'log_errors' => true,
    'include_errors_in_response' => false, // Pour le debug
],

// Dans SectionTransformer
public function transform(array $sections): array
{
    // ...
    
    try {
        $blockClass = $this->registry->get($type);
        
        if ($blockClass && method_exists($blockClass, 'transform')) {
            $transformedData = $blockClass::transform($data);
        } else {
            if (config('page-content-manager.transformer.error_handling') === 'strict') {
                throw new \RuntimeException("Bloc {$type} ne peut pas être transformé");
            }
            $transformedData = $data;
        }
        
        $transformed[] = [
            'type' => $type,
            'data' => $transformedData,
        ];
    } catch (\Throwable $e) {
        $errorHandling = config('page-content-manager.transformer.error_handling', 'fail-safe');
        
        if (config('page-content-manager.transformer.log_errors', true)) {
            Log::error('Erreur lors de la transformation d\'une section', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
        
        if ($errorHandling === 'strict') {
            throw $e;
        }
        
        // Mode fail-safe : retourner les données brutes avec un flag d'erreur
        $transformed[] = [
            'type' => $type,
            'data' => $data,
            '_error' => config('page-content-manager.transformer.include_errors_in_response', false) 
                ? $e->getMessage() 
                : null,
        ];
    }
}
```

**Bénéfice** : Plus de contrôle sur la gestion d'erreurs, meilleur debugging, option strict pour la production.

---

## 🔧 Priorité Basse

### 8. Logging amélioré
**Problème** : Erreurs silencieusement ignorées dans BlockRegistry. Pas de visibilité sur ce qui se passe.

**Solution** : Ajouter des logs détaillés avec niveaux configurables.

```php
// config/page-content-manager.php
'logging' => [
    'enabled' => env('PAGE_CONTENT_MANAGER_LOGGING', false),
    'level' => 'debug', // debug, info, warning, error
],

// Dans BlockRegistry
protected function registerBlockIfValid(string $className): void
{
    if (!class_exists($className)) {
        if (config('page-content-manager.logging.enabled', false)) {
            Log::debug("Classe de bloc non trouvée", ['class' => $className]);
        }
        return;
    }

    $reflection = new \ReflectionClass($className);
    
    if ($reflection->isAbstract() || $reflection->isInterface()) {
        if (config('page-content-manager.logging.enabled', false)) {
            Log::debug("Classe de bloc ignorée (abstraite ou interface)", ['class' => $className]);
        }
        return;
    }
    
    if (!$reflection->implementsInterface(BlockInterface::class)) {
        if (config('page-content-manager.logging.enabled', false)) {
            Log::warning("Classe ne respecte pas BlockInterface", ['class' => $className]);
        }
        return;
    }

    try {
        $type = $className::getType();
        $this->register($type, $className);
        
        if (config('page-content-manager.logging.enabled', false)) {
            Log::info("Bloc découvert et enregistré", [
                'type' => $type,
                'class' => $className,
            ]);
        }
    } catch (\Throwable $e) {
        if (config('page-content-manager.logging.enabled', false)) {
            Log::error("Erreur lors de l'enregistrement du bloc", [
                'class' => $className,
                'error' => $e->getMessage(),
            ]);
        }
        return;
    }
}
```

**Bénéfice** : Meilleur debugging, visibilité sur le processus de découverte, désactivable en production.

---

### 9. Tests unitaires pour les blocs
**Problème** : Pas de tests pour valider les blocs.

**Solution** : Créer des tests pour chaque bloc core.

```php
class HeroBlockTest extends TestCase
{
    public function test_make_returns_block()
    {
        $block = HeroBlock::make();
        $this->assertInstanceOf(Block::class, $block);
    }
    
    public function test_transform_returns_correct_structure()
    {
        $data = ['titre' => 'Test'];
        $transformed = HeroBlock::transform($data);
        $this->assertArrayHasKey('type', $transformed);
    }
}
```

**Bénéfice** : Fiabilité accrue.

---

### 10. Documentation avec exemples visuels
**Problème** : Documentation textuelle uniquement. Manque d'exemples concrets et complets.

**Solution** : Ajouter des screenshots/exemples dans la doc avec exemples de code complets.

**Améliorations à ajouter** :

1. **Exemples de réponses API complètes** :
   - Réponses avec tous les types de blocs
   - Cas d'erreur avec exemples de réponses
   - Exemples avec pagination

2. **Exemples de blocs personnalisés complexes** :
   - Bloc avec relations
   - Bloc avec validation conditionnelle
   - Bloc avec transformation de médias multiples

3. **Guide de migration depuis l'ancien système** :
   - Étapes détaillées
   - Exemples avant/après
   - Script de migration automatique

4. **Screenshots de l'interface Filament** :
   - Vue d'ensemble de la ressource Page
   - Exemple de formulaire avec blocs
   - Interface de gestion des blocs

5. **Diagrammes d'architecture** :
   - Flux de transformation des blocs
   - Architecture du système de découverte
   - Relations entre les composants

**Bénéfice** : Meilleure compréhension, onboarding plus rapide, moins de questions de support.

---

### 10.1. Type safety amélioré
**Problème** : L'interface `BlockInterface` est claire mais manque de type hints stricts. Pas de validation du retour de `transform()`.

**Solution** : Ajouter des PHPDoc plus stricts et utiliser des attributes PHP 8 si disponible.

```php
interface BlockInterface
{
    /**
     * Retourne le type unique du bloc (ex: 'hero', 'text').
     *
     * @return non-empty-string
     */
    public static function getType(): string;

    /**
     * Crée le schéma Filament pour le formulaire du bloc.
     *
     * @return Block
     */
    public static function make(): Block;

    /**
     * Transforme les données du bloc pour l'API.
     *
     * @param array<string, mixed> $data Les données brutes du bloc
     * @return array{type: string, ...} Les données transformées pour l'API (doit contenir 'type')
     */
    public static function transform(array $data): array;
}

// Validation du retour dans SectionTransformer
if (!isset($transformedData['type'])) {
    throw new \RuntimeException("La méthode transform() doit retourner un array avec la clé 'type'");
}

if ($transformedData['type'] !== $type) {
    Log::warning("Type mismatch dans transform()", [
        'expected' => $type,
        'got' => $transformedData['type'],
    ]);
}
```

**Bénéfice** : Meilleure détection d'erreurs par les IDE, validation à l'exécution, code plus robuste.

---

### 11. Support des traductions
**Problème** : Labels des blocs en dur.

**Solution** : Utiliser les traductions Laravel.

```php
->label(__('page-content-manager::blocks.hero.label'))
```

**Bénéfice** : Internationalisation.

---

### 12. API versioning
**Problème** : Pas de versioning pour l'API.

**Solution** : Ajouter un préfixe de version configurable.

```php
// config/page-content-manager.php
'api' => [
    'version' => 'v1',
    'versioning_enabled' => false, // Activé progressivement
],

// routes/api.php
Route::group([
    'prefix' => config('page-content-manager.api.versioning_enabled') 
        ? config('page-content-manager.api.version', 'v1')
        : '',
    // ...
], function () {
    // Routes
});
```

**Bénéfice** : Compatibilité future, migration progressive possible.

---

### 12.1. Pagination pour l'API
**Problème** : L'endpoint `GET /api/pages` retourne toutes les pages sans pagination. Peut être problématique avec beaucoup de pages.

**Solution** : Ajouter la pagination optionnelle via paramètre de requête.

```php
// PageController
public function index(Request $request): JsonResponse
{
    $query = Page::published()
        ->select('id', 'title', 'slug', 'type')
        ->orderByRaw("CASE WHEN type = 'home' THEN 0 ELSE 1 END")
        ->orderBy('title');
    
    // Pagination optionnelle
    if ($request->boolean('paginate', false)) {
        $pages = $query->paginate($request->integer('per_page', 15));
        
        return response()->json([
            'pages' => $pages->items(),
            'pagination' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }
    
    // Comportement actuel par défaut (rétrocompatibilité)
    $pages = $query->get();
    
    return response()->json([
        'pages' => $pages->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug ?: 'home',
                'type' => $page->type,
            ];
        }),
    ]);
}
```

**Bénéfice** : Scalabilité améliorée, rétrocompatibilité préservée.

---

### 12.2. Rate limiting pour l'API
**Problème** : API publique sans protection contre l'abus. Déjà mentionné mais à améliorer.

**Solution** : Ajouter du rate limiting configurable avec différents niveaux.

```php
// config/page-content-manager.php
'api' => [
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'by_ip' => true, // Limiter par IP
        'by_user' => false, // Limiter par utilisateur (si authentifié)
    ],
],

// routes/api.php
$middleware = config('page-content-manager.route_middleware', ['api']);

if (config('page-content-manager.api.rate_limit.enabled', true)) {
    $maxAttempts = config('page-content-manager.api.rate_limit.max_attempts', 60);
    $decayMinutes = config('page-content-manager.api.rate_limit.decay_minutes', 1);
    $middleware[] = "throttle:{$maxAttempts},{$decayMinutes}";
}

Route::group([
    'prefix' => config('page-content-manager.route_prefix', 'api'),
    'middleware' => $middleware,
], function () {
    // Routes
});
```

**Bénéfice** : Protection contre l'abus, configuration flexible.

---

### 13. Optimisation de la normalisation du contenu
**Problème** : La méthode `normalizeContent()` est appelée à chaque `saving()`, même si le contenu est déjà normalisé. Peut être coûteux avec beaucoup de pages.

**Solution** : Vérifier si le contenu a changé avant de normaliser.

```php
// Dans HasContentBlocks trait
protected function normalizeContent(): void
{
    $content = $this->content;
    
    // Vérifier si le contenu a déjà la structure attendue
    if (is_array($content) 
        && isset($content['sections']) 
        && is_array($content['sections'])
        && isset($content['metadata']) 
        && is_array($content['metadata'])
        && isset($content['metadata']['schema_version'])
        && is_int($content['metadata']['schema_version'])
        && $content['metadata']['schema_version'] >= 1
    ) {
        // Contenu déjà normalisé, pas besoin de le refaire
        return;
    }
    
    // Normalisation nécessaire
    // ... logique existante
}
```

**Bénéfice** : Performance améliorée, moins de traitements inutiles.

---

### 14. Amélioration du ServiceProvider
**Problème** : L'enregistrement automatique de la ressource Filament ne fonctionne pas bien. C'est documenté mais pourrait être amélioré.

**Solution** : Améliorer le système d'enregistrement avec meilleure détection et fallback.

```php
// Dans ServiceProvider
public function boot(): void
{
    // ...
    
    // Enregistrement amélioré de la ressource Filament
    if (config('page-content-manager.register_filament_resource', false)) {
        // Essayer plusieurs méthodes selon la version de Filament
        $this->registerFilamentResource();
    }
}

protected function registerFilamentResource(): void
{
    // Méthode 1 : Via Filament::serving() (Filament 3.x)
    if (method_exists(Filament::class, 'serving')) {
        Filament::serving(function () {
            foreach (Filament::getPanels() as $panel) {
                $panel->resources([
                    \Xavcha\PageContentManager\Filament\Resources\Pages\PageResource::class,
                ]);
            }
        });
        return;
    }
    
    // Méthode 2 : Via PanelProvider directement (Filament 4.x)
    // Cette méthode nécessite que l'utilisateur enregistre manuellement
    // mais on peut fournir un helper
    if ($this->app->bound('filament')) {
        // Log pour informer l'utilisateur
        Log::info('Enregistrement automatique non disponible. Veuillez enregistrer manuellement PageResource dans votre PanelProvider.');
    }
}
```

**Bénéfice** : Meilleure compatibilité avec différentes versions de Filament, messages plus clairs.

---

## 🎯 Recommandations Immédiates

Pour une version future, je recommande d'implémenter :

1. ✅ **Ordre et groupes des blocs** (UX) - **IMPLÉMENTÉ (v0.2.3)**
2. **Facade** (DX) - Impact moyen, effort faible
3. **Optimisation normalisation contenu** (Performance) - Impact moyen, effort faible
4. **Gestion d'erreurs SectionTransformer** (Robustesse) - Impact moyen, effort moyen

Ces améliorations apportent le plus de valeur avec un effort raisonnable et **ne cassent pas la compatibilité**.

## 📋 Améliorations Complémentaires (Version 2.2+)

Pour une version future, considérer :

1. **Pagination API** (Scalabilité) - Impact élevé, effort moyen
2. **Rate limiting API** (Sécurité) - Impact élevé, effort faible
3. **Tests unitaires blocs core** (Fiabilité) - Impact élevé, effort élevé
4. **Type safety amélioré** (Qualité code) - Impact moyen, effort moyen
5. **Documentation avec exemples** (DX) - Impact élevé, effort moyen
6. **Validation blocs au démarrage** (Robustesse) - Impact moyen, effort moyen
7. **API versioning** (Compatibilité future) - Impact moyen, effort moyen


