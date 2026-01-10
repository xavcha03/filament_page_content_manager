<?php

namespace Xavcha\PageContentManager\Models\Concerns;

trait HasContentBlocks
{
    /**
     * Normalise le contenu pour garantir la structure canonique.
     *
     * @return void
     */
    protected function normalizeContent(): void
    {
        $content = $this->content;

        // Gérer explicitement null (le cast Laravel convertit null en [] à l'accès,
        // mais on veut être sûr de gérer tous les cas)
        if ($content === null || !is_array($content)) {
            $content = [];
        }

        // Si content est un array vide [] ou un array numérique, on le transforme en structure minimale
        // Sinon, on garantit que les clés requises existent
        if (empty($content) || !isset($content['sections'])) {
            // Réinitialiser complètement si vide ou si sections manque
            $content = [];
        }

        // Garantir que sections existe et est un tableau
        if (!isset($content['sections']) || !is_array($content['sections'])) {
            $content['sections'] = [];
        }

        // Garantir que metadata existe et est un objet
        if (!isset($content['metadata']) || !is_array($content['metadata'])) {
            $content['metadata'] = [];
        }

        // Garantir que schema_version existe et est >= 1
        if (!isset($content['metadata']['schema_version']) || !is_int($content['metadata']['schema_version']) || $content['metadata']['schema_version'] < 1) {
            $content['metadata']['schema_version'] = 1;
        }

        $this->content = $content;
    }

    /**
     * Récupère les sections du contenu.
     *
     * @return array
     */
    public function getSections(): array
    {
        $content = $this->content ?? [];

        return $content['sections'] ?? [];
    }

    /**
     * Récupère les métadonnées du contenu.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        $content = $this->content ?? [];

        return $content['metadata'] ?? [];
    }
}



