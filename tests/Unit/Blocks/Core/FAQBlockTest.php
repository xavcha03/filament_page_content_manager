<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\FAQBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class FAQBlockTest extends TestCase
{
    public function test_get_type_returns_faq(): void
    {
        $this->assertEquals('faq', FAQBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = FAQBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_handles_questions_array(): void
    {
        $data = [
            'titre' => 'FAQ Title',
            'faqs' => [
                [
                    'question' => 'Question 1?',
                    'answer' => 'Answer 1',
                ],
                [
                    'question' => 'Question 2?',
                    'answer' => 'Answer 2',
                ],
            ],
        ];

        $result = FAQBlock::transform($data);

        $this->assertEquals('faq', $result['type']);
        $this->assertEquals('FAQ Title', $result['titre']);
        $this->assertArrayHasKey('faqs', $result);
        $this->assertIsArray($result['faqs']);
        $this->assertCount(2, $result['faqs']);
        $this->assertEquals('Question 1?', $result['faqs'][0]['question']);
        $this->assertEquals('Answer 1', $result['faqs'][0]['answer']);
    }
}




