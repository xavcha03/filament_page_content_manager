<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Blocks;

use Filament\Forms\Components\Builder\Block;

/**
 * Construit les données groupées pour la modale de sélection de blocs.
 */
class BlockPickerCatalog
{
    /**
     * Ordre d'affichage des groupes connus.
     *
     * @var list<string>
     */
    public const GROUP_ORDER = [
        'Layout',
        'Contenu',
        'Média',
        'Conversion',
        'Social proof',
    ];

    /**
     * @param  array<int, Block>  $blocks
     * @return array<string, list<array{type: string, label: string, icon: mixed, description: string, previewUrl: string|null, searchText: string}>>
     */
    public static function grouped(array $blocks): array
    {
        $registry = app(BlockRegistry::class);
        $grouped = [];

        foreach ($blocks as $block) {
            $type = $block->getName();
            $blockClass = $registry->get($type);

            $group = 'Autres';
            $description = '';

            if (is_string($blockClass)) {
                if (method_exists($blockClass, 'getGroup')) {
                    try {
                        $group = $blockClass::getGroup() ?: 'Autres';
                    } catch (\Throwable) {
                        $group = 'Autres';
                    }
                }

                if (method_exists($blockClass, 'getDescription')) {
                    try {
                        $description = (string) ($blockClass::getDescription() ?? '');
                    } catch (\Throwable) {
                        $description = '';
                    }
                }
            }

            $label = (string) ($block->getLabel() ?? $type);
            $previewUrl = BlockPreviewResolver::url($type, $blockClass);

            $item = [
                'type' => $type,
                'label' => $label,
                'icon' => $block->getIcon(),
                'description' => $description,
                'previewUrl' => $previewUrl,
                'searchText' => mb_strtolower(trim($label . ' ' . $type . ' ' . $description . ' ' . $group)),
            ];

            $grouped[$group][] = $item;
        }

        return self::sortGroups($grouped);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $grouped
     * @return array<string, list<array<string, mixed>>>
     */
    public static function sortGroups(array $grouped): array
    {
        $ordered = [];

        foreach (self::GROUP_ORDER as $group) {
            if (isset($grouped[$group])) {
                $ordered[$group] = $grouped[$group];
                unset($grouped[$group]);
            }
        }

        ksort($grouped);

        return $ordered + $grouped;
    }
}
