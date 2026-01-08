<?php

namespace Xavcha\PageContentManager\Blocks\Concerns;

use Illuminate\Support\Facades\Storage;
use Xavier\MediaLibraryPro\Models\MediaFile;

trait HasMediaTransformation
{
    /**
     * Récupère l'URL d'un MediaFile depuis son ID.
     *
     * @param int|string $mediaFileId L'ID du MediaFile
     * @return string|null L'URL du média ou null si non trouvé
     */
    protected static function getMediaFileUrl($mediaFileId): ?string
    {
        if (empty($mediaFileId)) {
            return null;
        }

        // Si c'est une chaîne JSON (pour multiple), décoder
        if (is_string($mediaFileId)) {
            $decoded = json_decode($mediaFileId, true);
            if (is_array($decoded) && !empty($decoded)) {
                // Prendre le premier ID si c'est un tableau
                $mediaFileId = $decoded[0];
            }
        }

        $mediaFile = MediaFile::find($mediaFileId);
        
        if (!$mediaFile) {
            return null;
        }

        // Générer l'URL via la route du package
        return route('media-library-pro.serve', [
            'media' => $mediaFile->uuid
        ]);
    }

    /**
     * Transforme un tableau d'IDs de MediaFile en URLs.
     *
     * @param array|string $mediaFileIds Les IDs de MediaFile (peut être un JSON string ou un array)
     * @return array Le tableau d'URLs
     */
    protected static function transformMediaFileIds($mediaFileIds): array
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
            
            $url = static::getMediaFileUrl($id);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Transforme un chemin d'image en URL absolue (rétrocompatibilité).
     *
     * @param string $path Le chemin de l'image
     * @return string L'URL complète de l'image
     */
    protected static function transformImageUrl(string $path): string
    {
        // Si c'est déjà une URL complète, la retourner telle quelle
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Si c'est un chemin relatif commençant par /, utiliser url() pour URL absolue
        if (str_starts_with($path, '/')) {
            return url($path);
        }

        // Les fichiers uploadés via Filament sont stockés dans storage/app/public
        $publicDisk = Storage::disk('public');
        
        // Vérifier si le fichier existe dans le storage public
        if ($publicDisk->exists($path)) {
            $storageUrl = $publicDisk->url($path);
            // S'assurer que c'est une URL absolue
            if (!filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                return url($storageUrl);
            }
            return $storageUrl;
        }

        // Fallback : construire l'URL en supposant que c'est dans le storage public
        $storagePath = '/storage/' . ltrim($path, '/');
        return url($storagePath);
    }
}

