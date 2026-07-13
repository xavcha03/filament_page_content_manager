<?php

namespace Xavcha\PageContentManager\Tests\Feature\Mcp;

use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Mcp\Helpers\BlockDataValidator;
use Xavcha\PageContentManager\Mcp\Helpers\BlockInfoExtractor;
use Xavcha\PageContentManager\Mcp\Tools\GetBlockSchemaTool;
use Xavcha\PageContentManager\Tests\Helpers\TestHelpers;
use Xavcha\PageContentManager\Tests\TestCase;

class HeroBlockMcpSchemaTest extends TestCase
{
    public function test_get_block_schema_exposes_hero_media_fields(): void
    {
        $registry = app(BlockRegistry::class);
        $registry->register('hero', HeroBlock::class);

        $blockInfo = BlockInfoExtractor::extract('hero', HeroBlock::class);
        $fieldNames = array_column($blockInfo['fields'], 'name');

        $this->assertContains('image_fond_id', $fieldNames);
        $this->assertContains('image_fond_alt', $fieldNames);
        $this->assertContains('images_ids', $fieldNames);
        $this->assertContains('image_fond', $fieldNames);

        $tool = app(GetBlockSchemaTool::class);
        $this->assertSame('get_block_schema', $tool->name());
    }

    public function test_update_block_fields_flow_accepts_hero_with_existing_image(): void
    {
        $page = TestHelpers::createPage([
            'content' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'titre' => 'Titre hero',
                            'description' => 'Description longue à raccourcir',
                            'variant' => 'hero',
                            'image_fond_id' => 123,
                            'image_fond_alt' => 'Image d\'accueil',
                        ],
                    ],
                ],
                'metadata' => ['schema_version' => 1],
            ],
        ]);

        $registry = app(BlockRegistry::class);
        $registry->register('hero', HeroBlock::class);
        $validator = app(BlockDataValidator::class);

        $content = $page->content;
        $sections = $content['sections'];
        $existingData = $sections[0]['data'];
        $patchedData = array_replace_recursive($existingData, [
            'description' => 'Description courte',
        ]);

        $validation = $validator->validateBlockData('hero', $patchedData);
        $this->assertTrue($validation['ok'], $validation['error'] ?? '');

        $sections[0]['data'] = $patchedData;
        $content['sections'] = $sections;
        $page->content = $content;
        $page->save();
        $page->refresh();

        $this->assertSame('Description courte', $page->content['sections'][0]['data']['description']);
        $this->assertSame(123, $page->content['sections'][0]['data']['image_fond_id']);
        $this->assertSame('Image d\'accueil', $page->content['sections'][0]['data']['image_fond_alt']);
    }
}
