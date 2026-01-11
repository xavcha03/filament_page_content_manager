<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\TestimonialsBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class TestimonialsBlockTest extends TestCase
{
    public function test_get_type_returns_testimonials(): void
    {
        $this->assertEquals('testimonials', TestimonialsBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = TestimonialsBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = TestimonialsBlock::make();

        $this->assertEquals('testimonials', $block->getName());
        $this->assertEquals('Témoignages', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'description' => 'Test Description',
            'temoignages' => [
                [
                    'avis' => 'Great service!',
                    'auteur' => 'John Doe',
                    'fonction' => 'CEO',
                    'entreprise' => 'Company',
                    'note' => '5',
                ],
            ],
        ];

        $result = TestimonialsBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('testimonials', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertIsArray($result['temoignages']);
        $this->assertCount(1, $result['temoignages']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = TestimonialsBlock::transform($data);

        $this->assertEquals('testimonials', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['description']);
        $this->assertIsArray($result['temoignages']);
    }

    public function test_transform_handles_note(): void
    {
        $data = [
            'temoignages' => [
                [
                    'avis' => 'Great!',
                    'auteur' => 'John',
                    'note' => '4',
                ],
            ],
        ];

        $result = TestimonialsBlock::transform($data);

        $this->assertEquals(4, $result['temoignages'][0]['note']);
    }

    public function test_transform_defaults_note_to_5(): void
    {
        $data = [
            'temoignages' => [
                [
                    'avis' => 'Great!',
                    'auteur' => 'John',
                ],
            ],
        ];

        $result = TestimonialsBlock::transform($data);

        $this->assertEquals(5, $result['temoignages'][0]['note']);
    }
}


