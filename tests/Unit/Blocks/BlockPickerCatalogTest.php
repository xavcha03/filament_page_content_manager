<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Xavcha\PageContentManager\Blocks\BlockPickerCatalog;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\CTABlock;
use Xavcha\PageContentManager\Blocks\Core\GalleryBlock;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockPickerCatalogTest extends TestCase
{
    public function test_all_core_blocks_have_known_group(): void
    {
        $registry = app(BlockRegistry::class);
        $known = BlockPickerCatalog::GROUP_ORDER;

        foreach ($registry->all() as $type => $blockClass) {
            if (! str_starts_with($blockClass, 'Xavcha\\PageContentManager\\Blocks\\Core\\')) {
                continue;
            }

            $this->assertTrue(
                method_exists($blockClass, 'getGroup'),
                "{$blockClass} doit définir getGroup()"
            );

            $group = $blockClass::getGroup();
            $this->assertContains(
                $group,
                $known,
                "Le groupe « {$group} » du bloc {$type} doit être un groupe connu"
            );

            $this->assertTrue(
                method_exists($blockClass, 'getDescription'),
                "{$blockClass} doit définir getDescription()"
            );
            $this->assertNotSame('', trim((string) $blockClass::getDescription()));
        }
    }

    public function test_grouped_orders_known_groups_first(): void
    {
        $grouped = BlockPickerCatalog::grouped([
            CTABlock::make(),
            GalleryBlock::make(),
            TextBlock::make(),
            HeroBlock::make(),
        ]);

        $labels = array_keys($grouped);

        $this->assertSame('Layout', $labels[0]);
        $this->assertContains('Contenu', $labels);
        $this->assertContains('Média', $labels);
        $this->assertContains('Conversion', $labels);

        $this->assertSame('hero', $grouped['Layout'][0]['type']);
        $this->assertSame('Texte', $grouped['Contenu'][0]['label']);
        $this->assertArrayHasKey('description', $grouped['Contenu'][0]);
        $this->assertArrayHasKey('previewUrl', $grouped['Contenu'][0]);
        $this->assertArrayHasKey('searchText', $grouped['Contenu'][0]);
    }
}
