<?php

namespace Xavcha\PageContentManager\Tests\Helpers;

use Xavcha\PageContentManager\Models\Page;

class TestHelpers
{
    /**
     * Crée une page de test avec des attributs par défaut.
     *
     * @param array $attributes
     * @return Page
     */
    public static function createPage(array $attributes = []): Page
    {
        $defaults = [
            'type' => 'standard',
            'slug' => 'test-page-' . uniqid(),
            'title' => 'Test Page',
            'content' => [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
            'status' => 'draft',
        ];

        return Page::create(array_merge($defaults, $attributes));
    }

    /**
     * Crée des données de bloc pour les tests.
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function createBlockData(string $type, array $data = []): array
    {
        $defaults = [
            'text' => [
                'titre' => 'Test Title',
                'content' => 'Test Content',
            ],
            'hero' => [
                'titre' => 'Hero Title',
                'description' => 'Hero Description',
                'variant' => 'hero',
            ],
            'image' => [
                'alt' => 'Test Alt',
                'caption' => 'Test Caption',
            ],
        ];

        $baseData = $defaults[$type] ?? [];
        
        return array_merge($baseData, $data);
    }

    /**
     * Crée une section complète pour les tests.
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function createSection(string $type, array $data = []): array
    {
        return [
            'type' => $type,
            'data' => self::createBlockData($type, $data),
        ];
    }
}

