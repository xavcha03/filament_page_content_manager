<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\ServicesBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class ServicesBlockTest extends TestCase
{
    public function test_get_type_returns_services(): void
    {
        $this->assertEquals('services', ServicesBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = ServicesBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = ServicesBlock::make();

        $this->assertEquals('services', $block->getName());
        $this->assertEquals('Services / Offres', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'description' => 'Test Description',
            'services' => [
                [
                    'titre' => 'Service 1',
                    'description' => 'Description 1',
                    'lien' => '/service-1',
                    'bouton_texte' => 'En savoir plus',
                ],
            ],
        ];

        $result = ServicesBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('services', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertIsArray($result['services']);
        $this->assertCount(1, $result['services']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = ServicesBlock::transform($data);

        $this->assertEquals('services', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['description']);
        $this->assertIsArray($result['services']);
    }

    public function test_transform_handles_multiple_services(): void
    {
        $data = [
            'services' => [
                [
                    'titre' => 'Service 1',
                    'description' => 'Description 1',
                ],
                [
                    'titre' => 'Service 2',
                    'description' => 'Description 2',
                ],
            ],
        ];

        $result = ServicesBlock::transform($data);

        $this->assertCount(2, $result['services']);
        $this->assertEquals('Service 1', $result['services'][0]['titre']);
        $this->assertEquals('Service 2', $result['services'][1]['titre']);
    }
}

