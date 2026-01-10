<?php

namespace Xavcha\PageContentManager\Console;

/**
 * Codes de sortie standardisés pour les commandes Artisan.
 */
class ExitCodes
{
    /**
     * Succès (0)
     */
    public const SUCCESS = 0;

    /**
     * Erreur générale (1)
     */
    public const FAILURE = 1;

    /**
     * Paramètres invalides (2)
     */
    public const INVALID_INPUT = 2;

    /**
     * Bloc non trouvé (3)
     */
    public const BLOCK_NOT_FOUND = 3;

    /**
     * Erreur de validation (4)
     */
    public const VALIDATION_ERROR = 4;
}


