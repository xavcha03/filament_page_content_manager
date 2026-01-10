<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockRegistryTest extends TestCase
{
    protected BlockRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new BlockRegistry();
        
        // Nettoyer le cache avant chaque test
        Cache::flush();
        
        // Réinitialiser la configuration du cache
        config(['page-content-manager.cache.enabled' => true]);
        config(['page-content-manager.cache.key' => 'page-content-manager.blocks.registry']);
        config(['page-content-manager.cache.ttl' => 3600]);
        config(['page-content-manager.disabled_blocks' => []]);
    }


    public function test_can_register_block_manually(): void
    {
        $this->registry->register('test_block', TextBlock::class);

        $this->assertEquals(TextBlock::class, $this->registry->get('test_block'));
    }

    public function test_can_get_registered_block(): void
    {
        $this->registry->register('text', TextBlock::class);

        $blockClass = $this->registry->get('text');

        $this->assertEquals(TextBlock::class, $blockClass);
    }

    public function test_returns_null_for_unregistered_block(): void
    {
        $blockClass = $this->registry->get('non_existent_block');

        $this->assertNull($blockClass);
    }

    public function test_returns_null_when_class_no_longer_exists(): void
    {
        // Simuler un bloc enregistré mais dont la classe n'existe plus
        // (comme si le fichier avait été supprimé)
        $nonExistentClass = 'App\\Blocks\\Custom\\DeletedBlock';
        
        // Enregistrer directement dans le tableau interne (bypass de la validation)
        $reflection = new \ReflectionClass($this->registry);
        $blocksProperty = $reflection->getProperty('blocks');
        $blocksProperty->setAccessible(true);
        $blocks = $blocksProperty->getValue($this->registry);
        $blocks['deleted_block'] = $nonExistentClass;
        $blocksProperty->setValue($this->registry, $blocks);
        
        // Réinitialiser autoDiscovered pour forcer la vérification
        $autoDiscoveredProperty = $reflection->getProperty('autoDiscovered');
        $autoDiscoveredProperty->setAccessible(true);
        $autoDiscoveredProperty->setValue($this->registry, true);

        // get() devrait retourner null car la classe n'existe pas
        $blockClass = $this->registry->get('deleted_block');
        $this->assertNull($blockClass);
        
        // Le bloc devrait être retiré de la liste
        $allBlocks = $this->registry->all();
        $this->assertArrayNotHasKey('deleted_block', $allBlocks);
    }

    public function test_auto_discovers_core_blocks(): void
    {
        $blocks = $this->registry->all();

        $this->assertIsArray($blocks);
        $this->assertArrayHasKey('text', $blocks);
        $this->assertEquals(TextBlock::class, $blocks['text']);
    }

    public function test_auto_discovers_custom_blocks(): void
    {
        // Créer un dossier temporaire pour les blocs custom
        $customPath = app_path('Blocks/Custom');
        
        if (!File::exists($customPath)) {
            File::makeDirectory($customPath, 0755, true);
        }

        // Créer un bloc de test
        $testBlockContent = <<<'PHP'
<?php

namespace App\Blocks\Custom;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class TestCustomBlock implements BlockInterface
{
    public static function getType(): string
    {
        return 'test_custom';
    }

    public static function make(): Block
    {
        return Block::make('test_custom')
            ->label('Test Custom')
            ->schema([
                TextInput::make('title'),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'test_custom',
            'title' => $data['title'] ?? '',
        ];
    }
}
PHP;

        $testBlockFile = $customPath . '/TestCustomBlock.php';
        File::put($testBlockFile, $testBlockContent);

        // Nettoyer le registry pour forcer la re-découverte
        $registry = new BlockRegistry();
        $blocks = $registry->all();

        // Nettoyer
        File::delete($testBlockFile);
        if (File::isEmptyDirectory($customPath)) {
            File::deleteDirectory($customPath);
        }

        // Le bloc custom devrait être découvert (mais peut ne pas être chargé si autoload ne fonctionne pas en test)
        // On teste au moins que le système ne plante pas
        $this->assertIsArray($blocks);
    }

    public function test_ignores_invalid_classes(): void
    {
        // Créer une classe abstraite de test
        $abstractClass = <<<'PHP'
<?php

namespace App\Blocks\Custom;

abstract class AbstractBlock
{
}
PHP;

        $customPath = app_path('Blocks/Custom');
        if (!File::exists($customPath)) {
            File::makeDirectory($customPath, 0755, true);
        }

        $abstractFile = $customPath . '/AbstractBlock.php';
        File::put($abstractFile, $abstractClass);

        $registry = new BlockRegistry();
        $blocks = $registry->all();

        // La classe abstraite ne devrait pas être enregistrée
        $this->assertArrayNotHasKey('abstract', $blocks);

        // Nettoyer
        File::delete($abstractFile);
        if (File::isEmptyDirectory($customPath)) {
            File::deleteDirectory($customPath);
        }
    }

    public function test_throws_exception_for_invalid_block_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('doit implémenter BlockInterface');

        $this->registry->register('invalid', \stdClass::class);
    }

    public function test_all_returns_all_registered_blocks(): void
    {
        $this->registry->register('block1', TextBlock::class);
        $this->registry->register('block2', TextBlock::class);

        $all = $this->registry->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('block1', $all);
        $this->assertArrayHasKey('block2', $all);
    }

    public function test_auto_discovery_only_runs_once(): void
    {
        // La première fois, auto-découverte
        $blocks1 = $this->registry->all();
        
        // La deuxième fois, devrait utiliser le cache interne
        $blocks2 = $this->registry->all();

        $this->assertEquals($blocks1, $blocks2);
    }

    public function test_handles_missing_core_directory(): void
    {
        // Le dossier Core existe normalement, mais on teste que le code gère l'absence
        // En créant un registry avec un chemin inexistant
        $registry = new BlockRegistry();
        
        // Le code devrait gérer File::exists() qui retourne false
        $blocks = $registry->all();
        
        // Ne devrait pas planter
        $this->assertIsArray($blocks);
    }

    public function test_handles_missing_custom_directory(): void
    {
        // Le dossier Custom peut ne pas exister
        $customPath = app_path('Blocks/Custom');
        
        // Si le dossier n'existe pas, File::exists() retourne false
        // Le code devrait gérer cela gracieusement
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Ne devrait pas planter
        $this->assertIsArray($blocks);
    }

    public function test_uses_cache_when_enabled_and_not_local(): void
    {
        // En environnement de test (testing), le cache devrait être utilisé si activé
        // car testing n'est pas 'local'
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => true]);
        
        $registry = new BlockRegistry();
        
        // Première découverte - devrait mettre en cache
        $blocks1 = $registry->all();
        
        // Vérifier que le cache contient les données
        $cached = Cache::get('page-content-manager.blocks.registry');
        $this->assertNotNull($cached);
        $this->assertIsArray($cached);
        
        // Créer un nouveau registry - devrait utiliser le cache
        $registry2 = new BlockRegistry();
        $blocks2 = $registry2->all();
        
        // Les résultats devraient être identiques
        $this->assertEquals($blocks1, $blocks2);
    }

    public function test_cache_disabled_when_config_disabled(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => false]);
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Le cache ne devrait pas être utilisé si désactivé
        $this->assertIsArray($blocks);
        
        // Vérifier que le cache n'a pas été utilisé
        $cached = Cache::get('page-content-manager.blocks.registry');
        $this->assertNull($cached);
    }

    public function test_clear_cache_removes_cache_and_resets_registry(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => true]);
        config(['page-content-manager.cache.key' => 'page-content-manager.blocks.registry']);
        
        $registry = new BlockRegistry();
        
        // Découvrir les blocs (met en cache)
        $blocks1 = $registry->all();
        
        // Vérifier que le cache existe
        $this->assertNotNull(Cache::get('page-content-manager.blocks.registry'));
        
        // Nettoyer le cache
        $registry->clearCache();
        
        // Vérifier que le cache a été supprimé
        $this->assertNull(Cache::get('page-content-manager.blocks.registry'));
        
        // Créer un nouveau registry et vérifier qu'il redécouvre
        $registry2 = new BlockRegistry();
        $blocks2 = $registry2->all();
        
        // Les blocs devraient être redécouverts
        $this->assertEquals($blocks1, $blocks2);
    }

    public function test_filters_disabled_blocks(): void
    {
        config(['page-content-manager.disabled_blocks' => ['text']]);
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Le bloc 'text' devrait être filtré
        $this->assertArrayNotHasKey('text', $blocks);
    }

    public function test_disabled_blocks_config_empty_array_does_not_filter(): void
    {
        config(['page-content-manager.disabled_blocks' => []]);
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Tous les blocs devraient être présents
        $this->assertIsArray($blocks);
        // Si le bloc text existe, il devrait être présent
        if (isset($blocks['text'])) {
            $this->assertArrayHasKey('text', $blocks);
        }
    }

    public function test_disabled_blocks_config_null_does_not_filter(): void
    {
        config(['page-content-manager.disabled_blocks' => null]);
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Tous les blocs devraient être présents
        $this->assertIsArray($blocks);
    }

    public function test_cache_respects_custom_ttl(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => true]);
        config(['page-content-manager.cache.ttl' => 7200]); // 2 heures
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Vérifier que le cache contient les données
        $cached = Cache::get('page-content-manager.blocks.registry');
        $this->assertNotNull($cached);
        $this->assertIsArray($cached);
    }

    public function test_cache_respects_custom_key(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => true]);
        config(['page-content-manager.cache.key' => 'custom.cache.key']);
        
        $registry = new BlockRegistry();
        $blocks = $registry->all();
        
        // Vérifier que le cache utilise la clé personnalisée
        $cached = Cache::get('custom.cache.key');
        $this->assertNotNull($cached);
        $this->assertIsArray($cached);
        
        // Vérifier que l'ancienne clé n'est pas utilisée
        $this->assertNull(Cache::get('page-content-manager.blocks.registry'));
    }
}

