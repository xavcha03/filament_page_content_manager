<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Helpers;

use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Helpers\BlockSchemaExtractor;

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
        try {
            $block = $blockClass::make();

            return BlockSchemaExtractor::serializeComponents(
                BlockSchemaExtractor::getComponents($block)
            );
        } catch (\Throwable) {
            return [];
        }
    }
}

