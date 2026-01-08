<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Illuminate\Support\Facades\Log;

class DefaultBlockTransformer implements BlockTransformerInterface
{
    protected string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Retourne le type de bloc géré.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Transforme les données du bloc (retourne les données brutes).
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données brutes (non transformées)
     */
    public function transform(array $data): array
    {
        Log::warning("Block transformer non trouvé pour le type: {$this->type}. Données brutes retournées.");

        return $data;
    }
}

