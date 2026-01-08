<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;

class TextBlockTransformer implements BlockTransformerInterface
{
    /**
     * Retourne le type de bloc géré.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'text';
    }

    /**
     * Transforme les données du bloc text.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées
     */
    public function transform(array $data): array
    {
        return [
            'type' => 'text',
            'titre' => $data['titre'] ?? '',
            'content' => $data['content'] ?? '',
        ];
    }
}

