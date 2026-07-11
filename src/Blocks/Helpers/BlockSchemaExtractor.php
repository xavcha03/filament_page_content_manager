<?php

namespace Xavcha\PageContentManager\Blocks\Helpers;

use Filament\Forms\Components\Builder\Block;

class BlockSchemaExtractor
{
    /**
     * Extrait les composants Filament d'un bloc Builder.
     *
     * @return array<int, object>
     */
    public static function getComponents(Block $block): array
    {
        if (method_exists($block, 'getChildComponents')) {
            try {
                $components = $block->getChildComponents();

                if (is_array($components) && $components !== []) {
                    return self::normalizeComponents($components);
                }
            } catch (\Throwable) {
                // Filament 4 : le container n'est pas toujours initialisé hors UI.
            }
        }

        $reflection = new \ReflectionClass($block);

        if ($reflection->hasProperty('childComponents')) {
            $property = $reflection->getProperty('childComponents');
            $property->setAccessible(true);
            $childComponents = $property->getValue($block);

            if (is_array($childComponents)) {
                $components = self::resolveChildComponents($childComponents);

                if ($components !== []) {
                    return $components;
                }
            }
        }

        if ($reflection->hasProperty('schema')) {
            $schemaProperty = $reflection->getProperty('schema');
            $schemaProperty->setAccessible(true);
            $components = $schemaProperty->getValue($block);

            if (is_array($components) && $components !== []) {
                return self::normalizeComponents($components);
            }
        }

        if (method_exists($block, 'getSchema')) {
            try {
                $components = $block->getSchema();

                if (is_array($components)) {
                    return self::normalizeComponents($components);
                }
            } catch (\Throwable) {
                // Méthode absente ou indisponible selon la version Filament.
            }
        }

        return [];
    }

    /**
     * Sérialise les composants pour une sortie JSON lisible.
     *
     * @param array<int, object> $components
     * @return array<int, array<string, mixed>>
     */
    public static function serializeComponents(array $components): array
    {
        $fields = [];

        foreach ($components as $component) {
            $field = self::serializeComponent($component);

            if ($field !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function serializeComponent(object $component): ?array
    {
        if (!method_exists($component, 'getName')) {
            return null;
        }

        $name = $component->getName();

        if ($name === null || $name === '') {
            return null;
        }

        $info = [
            'name' => $name,
            'label' => method_exists($component, 'getLabel') ? $component->getLabel() : null,
            'type' => class_basename($component),
            'required' => method_exists($component, 'isRequired') ? $component->isRequired() : false,
        ];

        if (method_exists($component, 'getMaxLength')) {
            $maxLength = $component->getMaxLength();

            if ($maxLength !== null) {
                $info['max_length'] = $maxLength;
            }
        }

        if (method_exists($component, 'getMinLength')) {
            $minLength = $component->getMinLength();

            if ($minLength !== null) {
                $info['min_length'] = $minLength;
            }
        }

        if (method_exists($component, 'getOptions')) {
            $options = $component->getOptions();

            if (is_array($options) && $options !== []) {
                $info['options'] = $options;
            }
        }

        if (method_exists($component, 'getDefaultState')) {
            $default = $component->getDefaultState();

            if ($default !== null) {
                $info['default'] = $default;
            }
        }

        if (method_exists($component, 'getMinItems')) {
            $minItems = $component->getMinItems();

            if ($minItems !== null) {
                $info['min_items'] = $minItems;
            }
        }

        if (method_exists($component, 'getMaxItems')) {
            $maxItems = $component->getMaxItems();

            if ($maxItems !== null) {
                $info['max_items'] = $maxItems;
            }
        }

        $nestedComponents = self::getComponentsFromField($component);

        if ($nestedComponents !== []) {
            $info['fields'] = self::serializeComponents($nestedComponents);
        }

        return $info;
    }

    /**
     * @param array<int|string, mixed> $childComponents
     * @return array<int, object>
     */
    protected static function resolveChildComponents(array $childComponents): array
    {
        if (isset($childComponents['default']) && is_array($childComponents['default'])) {
            return self::normalizeComponents($childComponents['default']);
        }

        return self::normalizeComponents($childComponents);
    }

    /**
     * @param array<int|string, mixed> $components
     * @return array<int, object>
     */
    protected static function normalizeComponents(array $components): array
    {
        $normalized = [];

        foreach ($components as $component) {
            if (!is_object($component)) {
                continue;
            }

            $normalized[] = $component;
        }

        return $normalized;
    }

    /**
     * @return array<int, object>
     */
    protected static function getComponentsFromField(object $component): array
    {
        if (method_exists($component, 'getChildComponents')) {
            try {
                $childComponents = $component->getChildComponents();

                if (is_array($childComponents) && $childComponents !== []) {
                    return self::normalizeComponents($childComponents);
                }
            } catch (\Throwable) {
                // Ignorer et tenter via réflexion.
            }
        }

        $reflection = new \ReflectionClass($component);

        if (!$reflection->hasProperty('childComponents')) {
            return [];
        }

        $property = $reflection->getProperty('childComponents');
        $property->setAccessible(true);
        $childComponents = $property->getValue($component);

        if (!is_array($childComponents)) {
            return [];
        }

        return self::resolveChildComponents($childComponents);
    }
}
