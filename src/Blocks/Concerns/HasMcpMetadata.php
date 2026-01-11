<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Blocks\Concerns;

/**
 * Trait optionnel pour ajouter des métadonnées MCP aux blocs.
 * 
 * Ce trait permet aux blocs de fournir des informations supplémentaires
 * pour la découverte MCP (description, exemples, champs, etc.) sans casser la
 * rétrocompatibilité avec les blocs existants.
 * 
 * Pour utiliser ce trait, ajoutez simplement "use HasMcpMetadata;" dans votre bloc.
 * Vous pouvez ensuite surcharger les méthodes pour personnaliser les informations MCP.
 */
trait HasMcpMetadata
{
    /**
     * Retourne la description du bloc pour MCP.
     * Par défaut, utilise le label du bloc Filament.
     * 
     * @return string
     */
    public static function getMcpDescription(): string
    {
        try {
            $block = static::make();
            return $block->getLabel() ?? static::getType();
        } catch (\Throwable $e) {
            return static::getType();
        }
    }

    /**
     * Retourne la liste des champs du bloc pour MCP.
     * Peut être surchargé dans le bloc pour fournir une liste personnalisée.
     * 
     * Format attendu :
     * [
     *     [
     *         'name' => 'titre',
     *         'label' => 'Titre',
     *         'type' => 'string',
     *         'required' => true,
     *         'description' => 'Le titre du bloc',
     *     ],
     *     ...
     * ]
     * 
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [];
    }

    /**
     * Retourne un exemple de données pour le bloc.
     * Peut être surchargé dans le bloc pour fournir un exemple personnalisé.
     * 
     * @return array<string, mixed>|null
     */
    public static function getMcpExample(): ?array
    {
        return null;
    }

    /**
     * Retourne des informations supplémentaires sur le bloc pour MCP.
     * Peut être surchargé dans le bloc pour fournir des infos personnalisées.
     * 
     * @return array<string, mixed>
     */
    public static function getMcpMetadata(): array
    {
        return [];
    }
}

