<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\EditRecord;
use Xavcha\PageContentManager\Filament\Forms\PageDeletionPolicyForm;
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;
use Xavcha\PageContentManager\Services\PagePreviewService;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_frontend')
                ->label('Ouvrir')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn (Page $record): bool => $record->isPublished() && ! $record->trashed())
                ->url(fn (Page $record): string => rtrim((string) config('app.frontend_url', config('app.url')), '/')
                    . ($record->isHome() ? '/' : '/' . ltrim($record->slug, '/')))
                ->openUrlInNewTab(),
            Action::make('preview_frontend')
                ->label('Prévisualiser')
                ->icon(Heroicon::OutlinedEye)
                ->color('info')
                ->visible(fn (Page $record): bool => ! $record->trashed() && ! $record->isPublished())
                ->url(fn (Page $record): string => app(PagePreviewService::class)->buildFrontendPreviewUrl($record))
                ->openUrlInNewTab(),
            Action::make('export_page')
                ->label('Exporter')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (Page $record): bool => ! $record->trashed())
                ->action(function (Page $record) {
                    $path = app(PageTransferService::class)->exportToFile([$record]);

                    return response()->download($path, basename($path))->deleteFileAfterSend();
                }),
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
