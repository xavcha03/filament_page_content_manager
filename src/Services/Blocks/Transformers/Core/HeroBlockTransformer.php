<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Illuminate\Support\Facades\Storage;
use Xavier\MediaLibraryPro\Models\MediaFile;

class HeroBlockTransformer implements BlockTransformerInterface
{
    /**
     * Retourne le type de bloc géré.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'hero';
    }

    /**
     * Transforme les données du bloc hero.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées
     */
    public function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'hero';
        
        $transformed = [
            'type' => 'hero',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'variant' => $variant,
        ];

        // Gestion selon la variante
        if ($variant === 'projects') {
            // Variante projects : utiliser le tableau images_ids (IDs de MediaFile)
            $transformed['images'] = $this->transformMediaFileIds($data['images_ids'] ?? []);
        } else {
            // Variante hero standard : utiliser image_fond_id (ID de MediaFile)
            if (!empty($data['image_fond_id'])) {
                $imageUrl = $this->getMediaFileUrl($data['image_fond_id']);
                if ($imageUrl) {
                    $transformed['image_fond'] = $imageUrl;
                    
                    if (!empty($data['image_fond_alt'])) {
                        $transformed['image_fond_alt'] = $data['image_fond_alt'];
                    }
                }
            }
            // Support rétrocompatibilité : si image_fond existe (ancien format avec chemin)
            elseif (!empty($data['image_fond'])) {
                $transformed['image_fond'] = $this->transformImageUrl($data['image_fond']);
                
                if (!empty($data['image_fond_alt'])) {
                    $transformed['image_fond_alt'] = $data['image_fond_alt'];
                }
            }
        }

        // Bouton principal (optionnel)
        if (!empty($data['bouton_principal'])) {
            $button = $data['bouton_principal'];
            
            if (is_array($button) && !empty($button['texte']) && !empty($button['lien'])) {
                $transformed['bouton_principal'] = [
                    'texte' => $button['texte'],
                    'lien' => $button['lien'],
                ];
            }
        }

        return $transformed;
    }

    /**
     * Transforme un chemin d'image en URL absolue.
     *
     * @param string $path Le chemin de l'image
     * @return string L'URL complète de l'image
     */
    protected function transformImageUrl(string $path): string
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
        // Utiliser le disque 'public' pour générer l'URL correcte
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
        // Format: /storage/{path}
        $storagePath = '/storage/' . ltrim($path, '/');
        return url($storagePath);
    }

    /**
     * Récupère l'URL d'un MediaFile depuis son ID.
     *
     * @param int|string $mediaFileId L'ID du MediaFile
     * @return string|null L'URL du média ou null si non trouvé
     */
    protected function getMediaFileUrl($mediaFileId): ?string
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
            
            $url = $this->getMediaFileUrl($id);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }
}

