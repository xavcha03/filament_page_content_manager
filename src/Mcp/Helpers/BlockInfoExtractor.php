<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Helpers;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

/**
 * Helper pour extraire les informations des blocs pour MCP.
 */
class BlockInfoExtractor
{
    /**
     * Extrait les informations d'un bloc pour MCP.
     *
     * @param string $type Le type du bloc
     * @param string $blockClass La classe du bloc
     * @return array<string, mixed>
     */
    public static function extract(string $type, string $blockClass): array
    {
        $info = [
            'type' => $type,
            'class' => $blockClass,
            'description' => static::getDescription($blockClass),
            'fields' => static::extractFields($blockClass),
        ];

        // Ajouter les métadonnées MCP si le bloc utilise le trait
        if (static::hasMcpMetadata($blockClass)) {
            $info['mcp_description'] = $blockClass::getMcpDescription();
            $mcpFields = $blockClass::getMcpFields();
            // Utiliser les champs MCP si disponibles, sinon utiliser l'extraction automatique
            if (!empty($mcpFields)) {
                $info['fields'] = $mcpFields;
            }
            $info['mcp_example'] = $blockClass::getMcpExample();
            $info['mcp_metadata'] = $blockClass::getMcpMetadata();
        }

        // Ajouter les infos supplémentaires si disponibles
        if (method_exists($blockClass, 'getOrder')) {
            $info['order'] = $blockClass::getOrder();
        }

        if (method_exists($blockClass, 'getGroup')) {
            $info['group'] = $blockClass::getGroup();
        }

        return $info;
    }

    /**
     * Vérifie si le bloc utilise le trait HasMcpMetadata.
     */
    protected static function hasMcpMetadata(string $blockClass): bool
    {
        $traits = class_uses_recursive($blockClass);
        return in_array(HasMcpMetadata::class, $traits, true);
    }

    /**
     * Récupère la description du bloc.
     */
    protected static function getDescription(string $blockClass): string
    {
        try {
            $block = $blockClass::make();
            return $block->getLabel() ?? $blockClass::getType();
        } catch (\Throwable $e) {
            return $blockClass::getType();
        }
    }

    /**
     * Extrait les champs du bloc depuis le formulaire Filament.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function extractFields(string $blockClass): array
    {
        $fields = [];

        try {
            $block = $blockClass::make();
            
            // Utiliser la réflexion pour accéder au schéma
            $reflection = new \ReflectionClass($block);
            
            // Essayer d'accéder à la propriété schema via différentes méthodes
            $components = null;
            
            // Méthode 1: getChildComponents() si disponible
            if (method_exists($block, 'getChildComponents')) {
                try {
                    $components = $block->getChildComponents();
                } catch (\Throwable $e) {
                    // Ignorer
                }
            }
            
            // Méthode 2: Accéder directement à la propriété schema via réflexion
            if ($components === null && $reflection->hasProperty('schema')) {
                $schemaProperty = $reflection->getProperty('schema');
                $schemaProperty->setAccessible(true);
                $components = $schemaProperty->getValue($block);
            }
            
            // Méthode 3: Utiliser getSchema() si disponible
            if ($components === null && method_exists($block, 'getSchema')) {
                try {
                    $components = $block->getSchema();
                } catch (\Throwable $e) {
                    // Ignorer
                }
            }

            if (is_array($components)) {
                foreach ($components as $component) {
                    $fieldInfo = static::extractFieldInfo($component);
                    if ($fieldInfo !== null) {
                        $fields[] = $fieldInfo;
                    }
                }
            }
        } catch (\Throwable $e) {
            // En cas d'erreur, retourner un tableau vide
        }

        return $fields;
    }

    /**
     * Extrait les informations d'un composant Filament.
     *
     * @param mixed $component
     * @return array<string, mixed>|null
     */
    protected static function extractFieldInfo($component): ?array
    {
        if (!is_object($component)) {
            return null;
        }

        $info = [
            'name' => method_exists($component, 'getName') ? $component->getName() : null,
            'label' => method_exists($component, 'getLabel') ? $component->getLabel() : null,
            'type' => class_basename($component),
            'required' => method_exists($component, 'isRequired') ? $component->isRequired() : false,
        ];

        // Extraire les contraintes si disponibles
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

        // Extraire les options pour les Select
        if (method_exists($component, 'getOptions')) {
            $options = $component->getOptions();
            if (is_array($options) && !empty($options)) {
                $info['options'] = $options;
            }
        }

        // Extraire la valeur par défaut
        if (method_exists($component, 'getDefaultState')) {
            $default = $component->getDefaultState();
            if ($default !== null) {
                $info['default'] = $default;
            }
        }

        return $info['name'] !== null ? $info : null;
    }
}

