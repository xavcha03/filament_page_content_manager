<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Feature\Mcp;

use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Mcp\Tools\GetExperienceSchemaTool;
use Xavcha\PageContentManager\Mcp\Tools\ListExperiencesTool;
use Xavcha\PageContentManager\Mcp\Tools\SetPageContentModeTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdateExperienceFieldsTool;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\Fixtures\DemoExperience;
use Xavcha\PageContentManager\Tests\TestCase;
use Laravel\Mcp\Request;

class ExperienceMcpToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $registry = app(ExperienceRegistry::class);
        $registry->clearCache();
        $registry->register(DemoExperience::getKey(), DemoExperience::class);
    }

    public function test_list_experiences_includes_demo(): void
    {
        $response = app(ListExperiencesTool::class)->handle(new Request([]));
        $payload = $this->responsePayload($response);

        $this->assertTrue($payload['success'] ?? false);
        $keys = array_column($payload['experiences'] ?? [], 'key');
        $this->assertContains(DemoExperience::getKey(), $keys);
    }

    public function test_get_experience_schema(): void
    {
        $response = app(GetExperienceSchemaTool::class)->handle(new Request([
            'key' => DemoExperience::getKey(),
        ]));
        $payload = $this->responsePayload($response);

        $this->assertTrue($payload['success'] ?? false);
        $this->assertFalse($payload['experience']['structure_editable'] ?? true);
        $this->assertSame('hero_title', $payload['experience']['fields'][0]['name'] ?? null);
    }

    public function test_set_mode_and_update_experience_fields(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'mcp-exp',
            'title' => 'MCP Exp',
            'content' => [
                'sections' => [
                    ['type' => 'text', 'data' => ['titre' => 'Keep']],
                ],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ]);

        app(SetPageContentModeTool::class)->handle(new Request([
            'page_id' => (string) $page->id,
            'content_mode' => 'experience',
            'experience_key' => DemoExperience::getKey(),
        ]));

        $page->refresh();
        $this->assertTrue($page->isExperienceMode());
        $this->assertSame(DemoExperience::getKey(), $page->experience_key);

        app(UpdateExperienceFieldsTool::class)->handle(new Request([
            'page_id' => (string) $page->id,
            'data' => ['hero_title' => 'From MCP'],
        ]));

        $page->refresh();
        $this->assertSame('From MCP', $page->getActiveExperienceContent()['hero_title']);
        $this->assertSame('Keep', $page->content['sections'][0]['data']['titre']);
    }

    public function test_update_experience_fields_rejects_blocks_mode(): void
    {
        $page = Page::create([
            'type' => 'standard',
            'slug' => 'mcp-blocks',
            'title' => 'MCP Blocks',
            'content' => ['sections' => [], 'metadata' => ['schema_version' => 1]],
            'status' => 'draft',
        ]);

        $response = app(UpdateExperienceFieldsTool::class)->handle(new Request([
            'page_id' => (string) $page->id,
            'data' => ['hero_title' => 'Nope'],
        ]));
        $payload = $this->responsePayload($response);

        $page->refresh();
        $this->assertSame([], $page->experience_content ?? []);
        $this->assertFalse($payload['success'] ?? true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function responsePayload(mixed $response): array
    {
        if (! is_object($response)) {
            return [];
        }

        if (method_exists($response, 'isError') && $response->isError()) {
            $text = $this->responseText($response);

            return [
                'success' => false,
                'error' => $text,
            ];
        }

        $text = $this->responseText($response);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : ['raw' => $text];
    }

    protected function responseText(mixed $response): string
    {
        $ref = new \ReflectionObject($response);
        if (! $ref->hasProperty('content')) {
            return '';
        }

        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        $content = $prop->getValue($response);

        if (is_string($content)) {
            return $content;
        }

        if (! is_object($content)) {
            return '';
        }

        $contentRef = new \ReflectionObject($content);
        if ($contentRef->hasProperty('text')) {
            $textProp = $contentRef->getProperty('text');
            $textProp->setAccessible(true);

            return (string) $textProp->getValue($content);
        }

        return '';
    }
}
