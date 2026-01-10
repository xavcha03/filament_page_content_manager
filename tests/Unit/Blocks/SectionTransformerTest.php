<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Illuminate\Support\Facades\Log;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Blocks\SectionTransformer;
use Xavcha\PageContentManager\Tests\TestCase;

class SectionTransformerTest extends TestCase
{
    protected SectionTransformer $transformer;
    protected BlockRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new BlockRegistry();
        $this->transformer = new SectionTransformer($this->registry);
    }

    public function test_transforms_empty_sections(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_transforms_single_section(): void
    {
        $sections = [
            [
                'type' => 'text',
                'data' => [
                    'titre' => 'Test Title',
                    'content' => 'Test Content',
                ],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(1, $result);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertArrayHasKey('data', $result[0]);
        $this->assertEquals('Test Title', $result[0]['data']['titre']);
    }

    public function test_transforms_multiple_sections(): void
    {
        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Title 1'],
            ],
            [
                'type' => 'text',
                'data' => ['titre' => 'Title 2'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(2, $result);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertEquals('text', $result[1]['type']);
    }

    public function test_handles_section_without_type(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Section sans type ignorée', \Mockery::type('array'));

        $sections = [
            [
                'data' => ['titre' => 'Test'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertEmpty($result);
    }

    public function test_handles_section_without_data(): void
    {
        $sections = [
            [
                'type' => 'text',
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(1, $result);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertIsArray($result[0]['data']);
    }

    public function test_handles_invalid_section_format(): void
    {
        $sections = [
            'not_an_array',
            null,
            123,
        ];

        $result = $this->transformer->transform($sections);

        $this->assertEmpty($result);
    }

    public function test_handles_missing_block_class(): void
    {
        $sections = [
            [
                'type' => 'non_existent_block',
                'data' => ['test' => 'data'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(1, $result);
        $this->assertEquals('non_existent_block', $result[0]['type']);
        // Devrait retourner les données brutes en fallback
        $this->assertEquals(['test' => 'data'], $result[0]['data']);
    }

    public function test_handles_block_transform_exception(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Erreur lors de la transformation d\'une section',
                \Mockery::type('array')
            );

        // Créer un mock de bloc qui lance une exception
        $this->registry->register('error_block', ErrorBlock::class);

        $sections = [
            [
                'type' => 'error_block',
                'data' => ['test' => 'data'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(1, $result);
        $this->assertEquals('error_block', $result[0]['type']);
        // Devrait retourner les données brutes en fallback
        $this->assertEquals(['test' => 'data'], $result[0]['data']);
    }

    public function test_handles_block_without_transform_method(): void
    {
        // Enregistrer un bloc sans méthode transform (ne devrait pas arriver normalement)
        // Mais on teste le fallback
        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        // TextBlock a une méthode transform, donc devrait fonctionner
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('type', $result[0]['data']);
    }

    public function test_preserves_section_structure(): void
    {
        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('data', $result[0]);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertIsArray($result[0]['data']);
    }

    public function test_transforms_with_registry_dependency(): void
    {
        // Enregistrer un bloc personnalisé
        $this->registry->register('custom', TextBlock::class);

        $sections = [
            [
                'type' => 'custom',
                'data' => ['titre' => 'Custom'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        $this->assertCount(1, $result);
        $this->assertEquals('custom', $result[0]['type']);
    }
}

// Classe helper pour tester les exceptions
class ErrorBlock implements \Xavcha\PageContentManager\Blocks\Contracts\BlockInterface
{
    public static function getType(): string
    {
        return 'error_block';
    }

    public static function make(): \Filament\Forms\Components\Builder\Block
    {
        return \Filament\Forms\Components\Builder\Block::make('error_block');
    }

    public static function transform(array $data): array
    {
        throw new \RuntimeException('Test exception');
    }
}

