<?php

namespace Xavcha\PageContentManager\Services\Blocks;

use Illuminate\Support\Facades\Log;

class SectionTransformerService
{
    protected BlockTransformerFactory $factory;

    public function __construct(BlockTransformerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Transforme un tableau de sections.
     *
     * @param array $sections Le tableau de sections à transformer
     * @return array Le tableau de sections transformées
     */
    public function transform(array $sections): array
    {
        if (empty($sections) || !is_array($sections)) {
            return [];
        }

        $transformed = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $type = $section['type'] ?? null;
            $data = $section['data'] ?? [];

            if (empty($type)) {
                Log::warning('Section sans type ignorée', ['section' => $section]);
                continue;
            }

            try {
                $transformer = $this->factory->getTransformer($type);
                $transformedData = $transformer->transform($data);

                $transformed[] = [
                    'type' => $type,
                    'data' => $transformedData,
                ];
            } catch (\Throwable $e) {
                Log::error('Erreur lors de la transformation d\'une section', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // En cas d'erreur, on retourne les données brutes
                $transformed[] = [
                    'type' => $type,
                    'data' => $data,
                ];
            }
        }

        return $transformed;
    }
}

