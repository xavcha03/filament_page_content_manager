<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Illuminate\Support\Facades\Storage;

class CTABlockTransformer implements BlockTransformerInterface
{
    /**
     * Retourne le type de bloc géré.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'cta';
    }

    /**
     * Transforme les données du bloc cta.
     *
     * @param array $data Les données brutes du bloc
     * @return array Les données transformées
     */
    public function transform(array $data): array
    {
        $variant = $data['variant'] ?? 'simple';

        $transformed = [
            'type' => 'cta',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'variant' => $variant,
            'cta_text' => $data['cta_text'] ?? '',
            'cta_link' => $data['cta_link'] ?? '',
        ];

        // Champs spécifiques au variant "hero"
        if ($variant === 'hero') {
            if (!empty($data['background_image'])) {
                $transformed['background_image'] = $this->transformImageUrl($data['background_image']);
            }

            if (!empty($data['phone_number'])) {
                $transformed['phone_number'] = $data['phone_number'];
            }
        }

        // Champs spécifiques au variant "subscription"
        if ($variant === 'subscription' && !empty($data['secondary_cta_text'])) {
            $transformed['secondary_cta_text'] = $data['secondary_cta_text'];
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

