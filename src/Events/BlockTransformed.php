<?php

namespace Xavcha\PageContentManager\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Événement déclenché après la transformation d'un bloc.
 * 
 * Permet aux développeurs de modifier les données transformées avant qu'elles ne soient retournées.
 */
class BlockTransformed
{
    use Dispatchable;

    /**
     * Le type du bloc (ex: 'hero', 'text').
     */
    public string $blockType;

    /**
     * Les données transformées du bloc (modifiables).
     */
    public array $transformedData;

    /**
     * Crée une nouvelle instance de l'événement.
     *
     * @param string $blockType Le type du bloc
     * @param array $transformedData Les données transformées du bloc
     */
    public function __construct(string $blockType, array $transformedData)
    {
        $this->blockType = $blockType;
        $this->transformedData = $transformedData;
    }

    /**
     * Retourne les données transformées du bloc.
     *
     * @return array
     */
    public function getTransformedData(): array
    {
        return $this->transformedData;
    }

    /**
     * Modifie les données transformées du bloc.
     *
     * @param array $transformedData Les nouvelles données transformées
     * @return void
     */
    public function setTransformedData(array $transformedData): void
    {
        $this->transformedData = $transformedData;
    }
}




