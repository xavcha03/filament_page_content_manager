<?php

namespace Xavcha\PageContentManager\Services\Blocks\Contracts;

interface BlockTransformerInterface
{
    /**
     * Retourne le type de bloc géré par ce transformer.
     *
     * @return string Le type de bloc (ex: 'hero', 'raw_section')
     */
    public function getType(): string;

    /**
     * Transforme les données d'un bloc pour l'API.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées pour l'API
     */
    public function transform(array $data): array;
}

