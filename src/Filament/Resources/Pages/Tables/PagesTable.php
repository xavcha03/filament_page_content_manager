<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'home' => 'success',
                        'standard' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home' => 'Home',
                        'standard' => 'Standard',
                        default => $state,
                    })
                    ->sortable(),
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
                TextColumn::make('published_at')
                    ->label('Date de publication')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
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
                    ->url(fn (Page $record): string => self::frontendUrlForPage($record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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





