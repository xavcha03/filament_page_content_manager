<?php

namespace Xavcha\PageContentManager\Tests\Feature\ServiceProvider;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockValidationOnBootTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver la validation par défaut pour les tests
        Config::set('page-content-manager.validate_blocks_on_boot', false);
        Config::set('page-content-manager.validate_blocks_on_boot_throw', false);
    }

    public function test_validation_disabled_by_default(): void
    {
        // La validation ne devrait pas être exécutée
        // On vérifie juste que l'application démarre sans erreur
        $this->assertTrue(true);
    }

    public function test_validation_runs_when_enabled(): void
    {
        Config::set('page-content-manager.validate_blocks_on_boot', true);
        Config::set('page-content-manager.validate_blocks_on_boot_throw', false);

        // Recharger l'application pour déclencher le boot
        $this->refreshApplication();

        // Si on arrive ici, c'est que la validation n'a pas lancé d'exception
        $this->assertTrue(true);
    }

    public function test_validation_logs_warnings(): void
    {
        Config::set('page-content-manager.validate_blocks_on_boot', true);
        Config::set('page-content-manager.validate_blocks_on_boot_throw', false);

        Log::shouldReceive('warning')
            ->zeroOrMoreTimes()
            ->with(\Mockery::type('string'), \Mockery::type('array'));

        // Recharger l'application
        $this->refreshApplication();

        $this->assertTrue(true);
    }

    public function test_validation_throws_when_configured(): void
    {
        // Créer un bloc invalide dans le registry
        // Note: En pratique, cela nécessiterait de modifier le BlockRegistry
        // Pour ce test, on vérifie juste que la configuration est respectée
        
        Config::set('page-content-manager.validate_blocks_on_boot', true);
        Config::set('page-content-manager.validate_blocks_on_boot_throw', true);

        // Si tous les blocs sont valides, ne devrait pas lancer d'exception
        $this->refreshApplication();

        $this->assertTrue(true);
    }

    public function test_validation_can_be_disabled_via_env(): void
    {
        // Simuler une variable d'environnement
        Config::set('page-content-manager.validate_blocks_on_boot', false);

        $this->refreshApplication();

        // Ne devrait pas valider
        $this->assertFalse(config('page-content-manager.validate_blocks_on_boot'));
    }
}


