<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Blocks\SectionTransformer;
use Xavcha\PageContentManager\Events\BlockTransformed;
use Xavcha\PageContentManager\Events\BlockTransforming;
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

    public function test_handles_missing_block_class_filters_by_default(): void
    {
        // Par défaut, les blocs manquants sont filtrés
        config(['page-content-manager.api.filter_missing_blocks' => true]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Section avec bloc inexistant filtrée', \Mockery::type('array'));

        $sections = [
            [
                'type' => 'non_existent_block',
                'data' => ['test' => 'data'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        // La section devrait être filtrée (non retournée)
        $this->assertCount(0, $result);
    }

    public function test_handles_missing_block_class_returns_raw_data_when_filter_disabled(): void
    {
        // Mode rétrocompatibilité : retourner les données brutes
        config(['page-content-manager.api.filter_missing_blocks' => false]);

        $sections = [
            [
                'type' => 'non_existent_block',
                'data' => ['test' => 'data'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        // Devrait retourner les données brutes en fallback
        $this->assertCount(1, $result);
        $this->assertEquals('non_existent_block', $result[0]['type']);
        $this->assertEquals(['test' => 'data'], $result[0]['data']);
    }

    public function test_handles_block_transform_exception_filters_by_default(): void
    {
        // Par défaut, les erreurs filtrent la section
        config(['page-content-manager.api.filter_missing_blocks' => true]);

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

        // La section devrait être filtrée en cas d'erreur
        $this->assertCount(0, $result);
    }

    public function test_handles_block_transform_exception_returns_raw_data_when_filter_disabled(): void
    {
        // Mode rétrocompatibilité : retourner les données brutes en cas d'erreur
        config(['page-content-manager.api.filter_missing_blocks' => false]);

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

        // Devrait retourner les données brutes en fallback
        $this->assertCount(1, $result);
        $this->assertEquals('error_block', $result[0]['type']);
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

    public function test_dispatches_block_transforming_event(): void
    {
        Event::fake();

        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test Title'],
            ],
        ];

        $this->transformer->transform($sections);

        Event::assertDispatched(BlockTransforming::class, function ($event) {
            return $event->blockType === 'text'
                && $event->getData()['titre'] === 'Test Title';
        });
    }

    public function test_dispatches_block_transformed_event(): void
    {
        Event::fake();

        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test Title', 'content' => 'Test Content'],
            ],
        ];

        $this->transformer->transform($sections);

        Event::assertDispatched(BlockTransformed::class, function ($event) {
            return $event->blockType === 'text'
                && isset($event->getTransformedData()['type'])
                && $event->getTransformedData()['type'] === 'text';
        });
    }

    public function test_block_transforming_event_allows_data_modification(): void
    {
        Event::listen(BlockTransforming::class, function (BlockTransforming $event) {
            if ($event->blockType === 'text') {
                $data = $event->getData();
                $data['titre'] = 'Modified Title';
                $data['content'] = 'Modified Content';
                $event->setData($data);
            }
        });

        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Original Title', 'content' => 'Original Content'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        // Vérifier que les données modifiées sont utilisées dans la transformation
        $this->assertCount(1, $result);
        $this->assertEquals('text', $result[0]['type']);
        // La transformation devrait utiliser le titre modifié
        $this->assertEquals('Modified Title', $result[0]['data']['titre']);
        // Le contenu devrait également être modifié
        $this->assertEquals('Modified Content', $result[0]['data']['content']);
    }

    public function test_block_transformed_event_allows_data_modification(): void
    {
        Event::listen(BlockTransformed::class, function (BlockTransformed $event) {
            if ($event->blockType === 'text') {
                $transformedData = $event->getTransformedData();
                $transformedData['metadata'] = [
                    'transformed_at' => '2024-01-01T00:00:00Z',
                    'custom' => true,
                ];
                $event->setTransformedData($transformedData);
            }
        });

        $sections = [
            [
                'type' => 'text',
                'data' => ['titre' => 'Test Title', 'content' => 'Test Content'],
            ],
        ];

        $result = $this->transformer->transform($sections);

        // Vérifier que les données transformées modifiées sont retournées
        $this->assertCount(1, $result);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertArrayHasKey('metadata', $result[0]['data']);
        $this->assertArrayHasKey('transformed_at', $result[0]['data']['metadata']);
        $this->assertArrayHasKey('custom', $result[0]['data']['metadata']);
        $this->assertTrue($result[0]['data']['metadata']['custom']);
    }

    public function test_events_dispatched_for_missing_block_when_filter_disabled(): void
    {
        Event::fake();
        config(['page-content-manager.api.filter_missing_blocks' => false]);

        $sections = [
            [
                'type' => 'non_existent_block',
                'data' => ['test' => 'data'],
            ],
        ];

        $this->transformer->transform($sections);

        // Les événements devraient être déclenchés même pour les blocs manquants
        Event::assertDispatched(BlockTransforming::class, function ($event) {
            return $event->blockType === 'non_existent_block';
        });

        Event::assertDispatched(BlockTransformed::class, function ($event) {
            return $event->blockType === 'non_existent_block';
        });
    }

    public function test_events_work_with_multiple_sections(): void
    {
        Event::fake();

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

        $this->transformer->transform($sections);

        // Vérifier que les événements sont déclenchés pour chaque section
        Event::assertDispatchedTimes(BlockTransforming::class, 2);
        Event::assertDispatchedTimes(BlockTransformed::class, 2);
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

