<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Xavier\MediaLibraryPro\Models\MediaFile;

class GalleryBlockTransformer implements BlockTransformerInterface
{
    /**
     * Retourne le type de bloc géré.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'gallery';
    }

    /**
     * Transforme les données du bloc gallery.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées
     */
    public function transform(array $data): array
    {
        return [
            'type' => 'gallery',
            'titre' => $data['titre'] ?? '',
            'images' => $this->transformMediaFileIds($data['images_ids'] ?? []),
        ];
    }

    /**
     * Transforme un tableau d'IDs de MediaFile en URLs.
     *
     * @param array|string $mediaFileIds Les IDs de MediaFile
     * @return array Le tableau d'URLs
     */
    protected function transformMediaFileIds($mediaFileIds): array
    {
        // Si c'est une chaîne JSON, décoder
        if (is_string($mediaFileIds)) {
            $decoded = json_decode($mediaFileIds, true);
            $mediaFileIds = is_array($decoded) ? $decoded : [$mediaFileIds];
        }

        // Si ce n'est pas un tableau, en faire un
        if (!is_array($mediaFileIds)) {
            $mediaFileIds = [$mediaFileIds];
        }

        $urls = [];
        foreach ($mediaFileIds as $id) {
            if (empty($id)) {
                continue;
            }
            
            $mediaFile = MediaFile::find($id);
            if ($mediaFile) {
                $urls[] = route('media-library-pro.serve', [
                    'media' => $mediaFile->uuid
                ]);
            }
        }

        return $urls;
    }
}

