<?php

namespace Xavcha\PageContentManager\Tests\Unit\Services\Transfer;

use Xavcha\PageContentManager\Services\Transfer\PageMediaReferenceResolver;
use Xavcha\PageContentManager\Tests\TestCase;

class PageMediaReferenceResolverTest extends TestCase
{
    public function test_collect_media_ids_from_nested_sections(): void
    {
        $resolver = new PageMediaReferenceResolver();

        $content = [
            'sections' => [
                [
                    'type' => 'image',
                    'data' => [
                        'image_id' => 12,
                    ],
                ],
                [
                    'type' => 'hero',
                    'data' => [
                        'images_ids' => [21, 22],
                    ],
                ],
                [
                    'type' => 'services',
                    'data' => [
                        'services' => [
                            ['image_id' => 31],
                            ['image_id' => 32],
                        ],
                    ],
                ],
            ],
            'metadata' => ['schema_version' => 1],
        ];

        $this->assertEqualsCanonicalizing([12, 21, 22, 31, 32], $resolver->collectMediaIdsFromContent($content));
    }

    public function test_make_and_detect_media_reference(): void
    {
        $resolver = new PageMediaReferenceResolver();
        $reference = $resolver->makeReference('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $this->assertTrue($resolver->isMediaReference($reference));
        $this->assertSame('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', $resolver->extractUuidFromReference($reference));
    }

    public function test_replace_media_references_with_ids(): void
    {
        $resolver = new PageMediaReferenceResolver();

        $content = [
            'sections' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'image_fond_id' => 'media:aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                        'images_ids' => [
                            'media:bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
                        ],
                    ],
                ],
            ],
            'metadata' => ['schema_version' => 1],
        ];

        $transformed = $resolver->replaceMediaReferencesWithIds($content, [
            'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa' => 101,
            'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb' => 102,
        ]);

        $this->assertSame(101, $transformed['sections'][0]['data']['image_fond_id']);
        $this->assertSame([102], $transformed['sections'][0]['data']['images_ids']);
    }
}
