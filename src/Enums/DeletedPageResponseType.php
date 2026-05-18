<?php

namespace Xavcha\PageContentManager\Enums;

enum DeletedPageResponseType: string
{
    case NotFound = '404';
    case Gone = '410';
    case RedirectToPage = '301_page';
    case RedirectToUrl = '301_url';

    public function label(): string
    {
        return match ($this) {
            self::NotFound => 'Retourner une erreur 404 — page introuvable',
            self::Gone => 'Retourner une erreur 410 — page supprimée définitivement',
            self::RedirectToPage => 'Rediriger vers une autre page',
            self::RedirectToUrl => 'Rediriger vers une URL personnalisée',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
