<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Xavcha\PageContentManager\Blocks\Core\FAQBlock;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Blocks\Helpers\BlockSchemaExtractor;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockSchemaExtractorTest extends TestCase
{
    public function test_extracts_hero_block_components(): void
    {
        $block = HeroBlock::make();
        $components = BlockSchemaExtractor::getComponents($block);

        $this->assertNotEmpty($components);
        $this->assertSame('titre', $components[0]->getName());
    }

    public function test_serializes_nested_repeater_fields(): void
    {
        $block = FAQBlock::make();
        $schema = BlockSchemaExtractor::serializeComponents(
            BlockSchemaExtractor::getComponents($block)
        );

        $this->assertNotEmpty($schema);

        $faqsField = collect($schema)->firstWhere('name', 'faqs');

        $this->assertNotNull($faqsField);
        $this->assertSame('Repeater', $faqsField['type']);
        $this->assertArrayHasKey('fields', $faqsField);
        $this->assertCount(2, $faqsField['fields']);
        $this->assertSame('question', $faqsField['fields'][0]['name']);
        $this->assertSame('answer', $faqsField['fields'][1]['name']);
        $this->assertSame('RichEditor', $faqsField['fields'][1]['type']);
    }
}
