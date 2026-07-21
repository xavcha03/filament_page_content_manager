<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Filament\Forms\Components\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Xavcha\PageContentManager\Blocks\BlockPickerCatalog;

class ConfiguresBlockPickerModal
{
    public static function configure(Action $action): Action
    {
        return $action
            ->livewireClickHandlerEnabled()
            ->modalHeading('Ajouter une section')
            ->modalDescription('Choisissez un type de bloc à ajouter à la page.')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalContent(function (Builder $component): View {
                return view('page-content-manager::filament.block-picker-modal', [
                    'groups' => BlockPickerCatalog::grouped($component->getBlockPickerBlocks()),
                ]);
            });
    }
}
