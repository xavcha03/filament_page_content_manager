<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Feature\Api;

use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\Fixtures\DemoExperience;
use Xavcha\PageContentManager\Tests\TestCase;

class ExperienceApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $registry = app(ExperienceRegistry::class);
        $registry->clearCache();
        $registry->register(DemoExperience::getKey(), DemoExperience::class);
    }

    public function test_blocks_page_exposes_content_mode_and_null_experience(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'blocks-api',
            'title' => 'Blocks API',
            'content' => [
                'sections' => [
                    ['type' => 'text', 'data' => ['titre' => 'Hello']],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/blocks-api');

        $response->assertOk();
        $response->assertJsonPath('content_mode', 'blocks');
        $response->assertJsonPath('experience', null);
        $this->assertIsArray($response->json('sections'));
    }

    public function test_experience_page_exposes_transformed_experience_payload(): void
    {
        Page::create([
            'type' => 'standard',
            'slug' => 'experience-api',
            'title' => 'Experience API',
            'content' => [
                'sections' => [
                    ['type' => 'text', 'data' => ['titre' => 'Ignored by FE']],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'content_mode' => Page::CONTENT_MODE_EXPERIENCE,
            'experience_key' => DemoExperience::getKey(),
            'experience_content' => [
                DemoExperience::getKey() => ['hero_title' => 'Immersive'],
            ],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/pages/experience-api');

        $response->assertOk();
        $response->assertJsonPath('content_mode', 'experience');
        $response->assertJsonPath('experience.key', DemoExperience::getKey());
        $response->assertJsonPath('experience.content.hero_title', 'Immersive');
        // sections still present for BC
        $this->assertNotEmpty($response->json('sections'));
    }
}
