<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Unit\Experiences;

use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\Fixtures\DemoExperience;
use Xavcha\PageContentManager\Tests\TestCase;

class PageExperienceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $registry = app(ExperienceRegistry::class);
        $registry->clearCache();
        $registry->register(DemoExperience::getKey(), DemoExperience::class);
    }

    public function test_default_content_mode_is_blocks(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'default-mode',
            'title' => 'Default',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $this->assertTrue($page->isBlocksMode());
        $this->assertFalse($page->isExperienceMode());
        $this->assertSame(Page::CONTENT_MODE_BLOCKS, $page->content_mode);
    }

    public function test_switching_mode_preserves_both_payloads(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'switch-mode',
            'title' => 'Switch',
            'content' => [
                'sections' => [
                    ['type' => 'text', 'data' => ['titre' => 'Bloc']],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);

        $page->content_mode = Page::CONTENT_MODE_EXPERIENCE;
        $page->experience_key = DemoExperience::getKey();
        $page->experience_content = [
            DemoExperience::getKey() => ['hero_title' => 'Exp'],
        ];
        $page->save();
        $page->refresh();

        $this->assertTrue($page->isExperienceMode());
        $this->assertSame('Exp', $page->getActiveExperienceContent()['hero_title']);
        $this->assertSame('Bloc', $page->content['sections'][0]['data']['titre']);

        $page->content_mode = Page::CONTENT_MODE_BLOCKS;
        $page->save();
        $page->refresh();

        $this->assertTrue($page->isBlocksMode());
        $this->assertSame('Exp', $page->experience_content[DemoExperience::getKey()]['hero_title']);
        $this->assertSame('Bloc', $page->content['sections'][0]['data']['titre']);
    }

    public function test_experience_content_is_keyed_per_experience(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'keyed-bag',
            'title' => 'Keyed',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'content_mode' => Page::CONTENT_MODE_EXPERIENCE,
            'experience_key' => DemoExperience::getKey(),
            'experience_content' => [
                DemoExperience::getKey() => ['hero_title' => 'A'],
                'other-key' => ['title' => 'B'],
            ],
            'status' => 'draft',
        ]);

        $this->assertSame('A', $page->getActiveExperienceContent()['hero_title']);
        $this->assertSame('B', $page->experience_content['other-key']['title']);

        $page->mergeActiveExperienceContent(['hero_title' => 'A2']);
        $page->save();
        $page->refresh();

        $this->assertSame('A2', $page->getActiveExperienceContent()['hero_title']);
        $this->assertSame('B', $page->experience_content['other-key']['title']);
    }

    public function test_experience_mode_requires_known_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Page::create([
            'type' => 'standard',
            'slug' => 'bad-key',
            'title' => 'Bad',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'content_mode' => Page::CONTENT_MODE_EXPERIENCE,
            'experience_key' => 'unknown-key',
            'status' => 'draft',
        ]);
    }
}
