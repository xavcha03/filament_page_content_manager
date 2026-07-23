<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Filament\Forms\Components;

use Filament\Forms;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Utilities\Get;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;

class ExperienceContentTab
{
    /**
     * Onglet Contenu pour le mode Experience : formulaire fixe, pas de builder.
     * Les champs sont bindés à experience_fields (mappé vers experience_content[key] à la sauvegarde).
     */
    public static function make(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('content_experience')
            ->label('Contenu')
            ->schema([
                Forms\Components\Placeholder::make('experience_missing_key')
                    ->label('')
                    ->content('Sélectionnez un modèle d\'Experience dans l\'onglet Général.')
                    ->visible(fn (Get $get): bool => blank($get('experience_key'))),
                Forms\Components\Placeholder::make('experience_unknown_key')
                    ->label('')
                    ->content('Le modèle d\'Experience sélectionné est introuvable ou désactivé.')
                    ->visible(function (Get $get): bool {
                        $key = $get('experience_key');
                        if (blank($key)) {
                            return false;
                        }

                        return ! app(ExperienceRegistry::class)->has((string) $key);
                    }),
                // Groupe sans statePath : lit experience_key à la racine du formulaire.
                // Groupe interne avec statePath experience_fields pour binder les valeurs.
                Components\Group::make()
                    ->visible(function (Get $get): bool {
                        $key = $get('experience_key');

                        return filled($key) && app(ExperienceRegistry::class)->has((string) $key);
                    })
                    ->key(fn (Get $get): string => 'experience-form-' . (string) ($get('experience_key') ?? 'none'))
                    ->schema(function (Get $get): array {
                        $key = $get('experience_key');
                        if (blank($key)) {
                            return [];
                        }

                        $class = app(ExperienceRegistry::class)->get((string) $key);
                        if ($class === null) {
                            return [];
                        }

                        return [
                            Components\Group::make()
                                ->statePath('experience_fields')
                                ->schema($class::make())
                                ->columnSpanFull(),
                        ];
                    })
                    ->columnSpanFull(),
            ]);
    }
}
