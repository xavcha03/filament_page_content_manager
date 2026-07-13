<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Xavcha\PageContentManager\Filament\Forms\PageImportPreviewForm;
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_page')
                ->label('Importer')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Importer une page')
                ->modalDescription('Téléversez une archive .xavcha-page.zip exportée depuis un autre environnement.')
                ->modalSubmitActionLabel('Confirmer l\'import')
                ->form(PageImportPreviewForm::schema())
                ->action(function (array $data): void {
                    $archive = $data['archive'] ?? null;
                    $path = PageImportPreviewForm::resolveArchivePathForImport($archive);

                    if ($path === null) {
                        Notification::make()
                            ->title('Archive introuvable')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $result = app(PageTransferService::class)->importFromPath($path, [
                            'on_conflict' => $data['on_conflict'] ?? 'replace',
                            'import_as_draft' => (bool) ($data['import_as_draft'] ?? true),
                        ]);
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Import impossible')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if (is_string($archive)) {
                        Storage::disk('local')->delete($archive);
                    }

                    $count = count($result['pages']);

                    Notification::make()
                        ->title($count === 1 ? 'Page importée' : "{$count} pages importées")
                        ->body(collect($result['pages'])->pluck('title')->implode(', '))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}






