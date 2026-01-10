<?php

namespace Xavcha\PageContentManager\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Cache;
use Xavcha\PageContentManager\Tests\TestCase;

class ClearBlocksCacheCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Nettoyer le cache avant chaque test
        Cache::flush();
        
        // Configuration par défaut
        config(['page-content-manager.cache.key' => 'page-content-manager.blocks.registry']);
    }

    public function test_command_clears_cache(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.key' => 'page-content-manager.blocks.registry']);
        
        // Mettre quelque chose en cache
        Cache::put('page-content-manager.blocks.registry', ['test' => 'data'], 3600);
        
        // Vérifier que le cache existe avant
        $this->assertNotNull(Cache::get('page-content-manager.blocks.registry'));
        
        // Exécuter la commande
        $this->artisan('page-content-manager:blocks:clear-cache')
            ->assertSuccessful();
        
        // Vérifier que le cache a été supprimé
        $this->assertNull(Cache::get('page-content-manager.blocks.registry'));
    }

    public function test_command_uses_custom_cache_key(): void
    {
        Cache::flush();
        
        $customKey = 'custom.cache.key';
        config(['page-content-manager.cache.key' => $customKey]);
        
        // Mettre quelque chose en cache
        Cache::put($customKey, ['test' => 'data'], 3600);
        
        // Vérifier que le cache existe avant
        $this->assertNotNull(Cache::get($customKey));
        
        // Exécuter la commande
        $this->artisan('page-content-manager:blocks:clear-cache')
            ->assertSuccessful();
        
        // Vérifier que la clé personnalisée a été supprimée
        $this->assertNull(Cache::get($customKey));
    }

    public function test_command_returns_success(): void
    {
        Cache::flush();
        
        // Exécuter la commande
        $this->artisan('page-content-manager:blocks:clear-cache')
            ->assertSuccessful();
    }

    public function test_command_outputs_success_message(): void
    {
        Cache::flush();
        
        // Exécuter la commande et vérifier le message
        $this->artisan('page-content-manager:blocks:clear-cache')
            ->expectsOutput('✅ Cache des blocs invalidé avec succès !')
            ->assertSuccessful();
    }

    public function test_command_resets_registry_state(): void
    {
        Cache::flush();
        
        config(['page-content-manager.cache.enabled' => true]);
        
        // Découvrir les blocs (met en cache)
        $registry = app(\Xavcha\PageContentManager\Blocks\BlockRegistry::class);
        $blocks1 = $registry->all();
        $this->assertIsArray($blocks1);
        
        // Vérifier que le cache existe
        $this->assertNotNull(Cache::get('page-content-manager.blocks.registry'));
        
        // Nettoyer le cache via la commande
        $this->artisan('page-content-manager:blocks:clear-cache')
            ->assertSuccessful();
        
        // Vérifier que le cache a été supprimé
        $this->assertNull(Cache::get('page-content-manager.blocks.registry'));
        
        // Les blocs devraient toujours être disponibles après clearCache
        $registry2 = app(\Xavcha\PageContentManager\Blocks\BlockRegistry::class);
        $blocks2 = $registry2->all();
        $this->assertIsArray($blocks2);
    }
}

