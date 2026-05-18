<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Collection;
use Xavcha\PageContentManager\Filament\Forms\PageDeletionPolicyForm;
use Xavcha\PageContentManager\Services\PageDeletionService;
use Xavcha\PageContentManager\Services\PagePreviewService;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Xavcha\PageContentManager\Models\Page;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copié')
                    ->copyMessageDuration(1500),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'scheduled' => 'info',
                        'published' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'scheduled' => 'Planifié',
                        'published' => 'Publié',
                        default => $state,
                    })
                    ->sortable(),
                IconColumn::make('seo_noindex')
                    ->label('Indexée')
                    ->getStateUsing(fn (Page $record): bool => ! (bool) $record->seo_noindex)
                    ->boolean()
                    ->alignCenter()
                    ->sortable()
                    ->tooltip(fn (Page $record): string => $record->seo_noindex
                        ? 'Non indexée (noindex)'
                        : 'Indexée par les moteurs de recherche'),
                TextColumn::make('published_at')
                    ->label('Date de publication')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'scheduled' => 'Planifié',
                        'published' => 'Publié',
                    ]),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'home' => 'Home',
                        'standard' => 'Standard',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open_frontend')
                    ->label('Ouvrir')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (Page $record): bool => $record->isPublished() && ! $record->trashed())
                    ->url(fn (Page $record): string => self::frontendUrlForPage($record))
                    ->openUrlInNewTab(),
                Action::make('preview_frontend')
                    ->label('Prévisualiser')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('info')
                    ->visible(fn (Page $record): bool => ! $record->trashed() && ! $record->isPublished())
                    ->url(fn (Page $record): string => app(PagePreviewService::class)->buildFrontendPreviewUrl($record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('Supprimer les pages sélectionnées')
                        ->modalDescription('Ces pages ne seront plus visibles sur le site.')
                        ->form(fn (Collection $records): array => PageDeletionPolicyForm::schema($records))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                if ($record->isHome()) {
                                    continue;
                                }

                                PageDeletionPolicyForm::applyDeletion($record, $data);
                            }
                        }),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->action(function (Collection $records): void {
                            $service = app(PageDeletionService::class);

                            foreach ($records as $record) {
                                $service->forceDelete($record);
                            }
                        }),
                ]),
            ]);
    }

    protected static function frontendUrlForPage(Page $page): string
    {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = $page->isHome() ? '/' : '/' . ltrim($page->slug, '/');

        return $baseUrl . $path;
    }
}





