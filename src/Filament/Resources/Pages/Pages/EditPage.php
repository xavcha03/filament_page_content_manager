<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Xavcha\PageContentManager\Filament\Forms\PageDeletionPolicyForm;
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Supprimer la page')
                ->modalDescription(fn (Page $record): string => "Supprimer « {$record->title} » ? Cette page ne sera plus visible sur le site.")
                ->form(fn (Page $record): array => PageDeletionPolicyForm::schema($record))
                ->action(fn (Page $record, array $data) => PageDeletionPolicyForm::applyDeletion($record, $data)),
            RestoreAction::make()
                ->action(fn (Page $record) => app(PageDeletionService::class)->restore($record)),
            ForceDeleteAction::make()
                ->modalHeading('Supprimer définitivement')
                ->modalDescription('Cette action est irréversible. Le slug redeviendra disponible.')
                ->action(fn (Page $record) => app(PageDeletionService::class)->forceDelete($record)),
        ];
    }
}
