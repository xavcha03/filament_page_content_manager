<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\ContactFormBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class ContactFormBlockTest extends TestCase
{
    public function test_get_type_returns_contact_form(): void
    {
        $this->assertEquals('contact_form', ContactFormBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = ContactFormBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Contact Us',
            'description' => 'Get in touch',
            'success_message' => 'Thank you!',
        ];

        $result = ContactFormBlock::transform($data);

        $this->assertEquals('contact_form', $result['type']);
        $this->assertEquals('Contact Us', $result['titre']);
        $this->assertEquals('Get in touch', $result['description']);
        $this->assertEquals('Thank you!', $result['success_message']);
    }
}



