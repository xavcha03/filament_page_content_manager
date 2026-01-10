<?php

namespace Xavcha\PageContentManager\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Événement déclenché avant la transformation d'un bloc.
 * 
 * Permet aux développeurs de modifier les données brutes avant qu'elles ne soient transformées.
 */
class BlockTransforming
{
    use Dispatchable;

    /**
     * Le type du bloc (ex: 'hero', 'text').
     */
    public string $blockType;

    /**
     * Les données brutes du bloc (modifiables).
     */
    public array $data;

    /**
     * Crée une nouvelle instance de l'événement.
     *
     * @param string $blockType Le type du bloc
     * @param array $data Les données brutes du bloc
     */
    public function __construct(string $blockType, array $data)
    {
        $this->blockType = $blockType;
        $this->data = $data;
    }

    /**
     * Retourne les données brutes du bloc.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Modifie les données brutes du bloc.
     *
     * @param array $data Les nouvelles données
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

