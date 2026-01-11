<?php

namespace Xavcha\PageContentManager\Blocks\Contracts;

use Filament\Forms\Components\Builder\Block;

interface BlockInterface
{
    /**
     * Retourne le type unique du bloc (ex: 'hero', 'text').
     *
     * @return string
     */
    public static function getType(): string;

    /**
     * Crée le schéma Filament pour le formulaire du bloc.
     *
     * @return Block
     */
    public static function make(): Block;

    /**
     * Transforme les données du bloc pour l'API.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées pour l'API
     */
    public static function transform(array $data): array;
}





