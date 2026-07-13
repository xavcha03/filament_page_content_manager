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
        $this->assertEquals('<p>Answer 1</p>', $result['faqs'][0]['answer']);
    }

    public function test_transform_preserves_rich_text_answers(): void
    {
        $data = [
            'titre' => 'FAQ Title',
            'faqs' => [
                [
                    'question' => 'Question 1?',
                    'answer' => '<p>Reponse <strong>formatee</strong>.</p>',
                ],
            ],
        ];

        $result = FAQBlock::transform($data);

        $this->assertSame('<p>Reponse <strong>formatee</strong>.</p>', $result['faqs'][0]['answer']);
    }

    public function test_transform_wraps_legacy_plain_text_answers_in_paragraphs(): void
    {
        $data = [
            'titre' => 'FAQ Title',
            'faqs' => [
                [
                    'question' => 'Question 1?',
                    'answer' => "Ligne 1\n\nLigne 2",
                ],
            ],
        ];

        $result = FAQBlock::transform($data);

        $this->assertSame('<p>Ligne 1</p><p>Ligne 2</p>', $result['faqs'][0]['answer']);
    }
}




