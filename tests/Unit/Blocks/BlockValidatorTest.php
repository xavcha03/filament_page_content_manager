<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\BlockValidator;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockValidatorTest extends TestCase
{
    protected BlockRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new BlockRegistry();
    }

    public function test_validates_valid_block(): void
    {
        $result = BlockValidator::validate('text', TextBlock::class);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_detects_missing_class(): void
    {
        $result = BlockValidator::validate('non_existent', 'App\\Blocks\\NonExistentBlock');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString("n'existe pas", $result['errors'][0]);
    }

    public function test_detects_missing_method(): void
    {
        // Pour tester une méthode manquante, on utilise une classe qui existe mais n'a pas la méthode
        // On ne peut pas créer une classe anonyme sans toutes les méthodes de l'interface
        // Donc on teste avec une classe qui existe vraiment mais qui n'est pas un bloc valide
        $result = BlockValidator::validate('invalid_block', \stdClass::class);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_detects_type_mismatch(): void
    {
        // Créer une classe de test avec type mismatch
        $testBlock = new class implements \Xavcha\PageContentManager\Blocks\Contracts\BlockInterface {
            public static function getType(): string
            {
                return 'different_type';
            }

            public static function make(): \Filament\Forms\Components\Builder\Block
            {
                return \Filament\Forms\Components\Builder\Block::make('test');
            }

            public static function transform(array $data): array
            {
                return ['type' => 'different_type'];
            }
        };

        $className = get_class($testBlock);
        $result = BlockValidator::validate('test', $className);

        $this->assertTrue($result['valid']); // Pas d'erreur, juste un warning
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Type mismatch', $result['warnings'][0]);
    }

    public function test_validates_all_blocks(): void
    {
        $result = BlockValidator::validateAll($this->registry, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertIsArray($result['results']);
    }

    public function test_throws_exception_when_throw_on_error_enabled(): void
    {
        // Créer un registry avec un bloc invalide
        $registry = new BlockRegistry();
        
        // Enregistrer une classe qui n'existe pas (bloc invalide)
        $reflection = new \ReflectionClass($registry);
        $blocksProperty = $reflection->getProperty('blocks');
        $blocksProperty->setAccessible(true);
        $blocks = $blocksProperty->getValue($registry);
        $blocks['invalid'] = 'App\\Blocks\\NonExistentBlock';
        $blocksProperty->setValue($registry, $blocks);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bloc invalid');

        BlockValidator::validateAll($registry, true);
    }

    public function test_does_not_throw_when_throw_on_error_disabled(): void
    {
        // Créer un registry avec un bloc invalide
        $registry = new BlockRegistry();
        
        // Enregistrer une classe qui n'existe pas (bloc invalide)
        $reflection = new \ReflectionClass($registry);
        $blocksProperty = $reflection->getProperty('blocks');
        $blocksProperty->setAccessible(true);
        $blocks = $blocksProperty->getValue($registry);
        $blocks['invalid'] = 'App\\Blocks\\NonExistentBlock';
        $blocksProperty->setValue($registry, $blocks);

        // Ne devrait pas lancer d'exception
        $result = BlockValidator::validateAll($registry, false);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['errors']);
    }
}

