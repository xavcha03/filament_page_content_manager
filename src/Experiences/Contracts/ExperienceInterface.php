<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Experiences\Contracts;

interface ExperienceInterface
{
    /**
     * Clé unique de l'Experience (ex: home-organic).
     */
    public static function getKey(): string;

    /**
     * Label affiché dans Filament.
     */
    public static function getLabel(): string;

    /**
     * Schéma Filament fixe (liste de composants de formulaire).
     * La structure est figée côté code : l'admin ne peut pas l'étendre.
     *
     * @return array<int, mixed>
     */
    public static function make(): array;

    /**
     * Transforme les données stockées pour l'API frontend.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function transform(array $data): array;
}
