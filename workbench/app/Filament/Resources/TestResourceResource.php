<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestResourceResource\Pages;
use App\Models\TestResource;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Xavcha\PageContentManager\Filament\Forms\Components\PageDetailTabs;

class TestResourceResource extends Resource
{
    protected static ?string $model = TestResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Ressources de test';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Tabs::make('tabs')
                    ->tabs([
                        Components\Tabs\Tab::make('general')
                            ->label('Général')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                        ...PageDetailTabs::make()->toArray(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seo_title')
                    ->label('Titre SEO')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestResources::route('/'),
            'create' => Pages\CreateTestResource::route('/create'),
            'edit' => Pages\EditTestResource::route('/{record}/edit'),
        ];
    }
}

