<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Xavcha\PageContentManager\Filament\Resources\Pages\Pages\CreatePage;
use Xavcha\PageContentManager\Filament\Resources\Pages\Pages\EditPage;
use Xavcha\PageContentManager\Filament\Resources\Pages\Pages\ListPages;
use Xavcha\PageContentManager\Filament\Resources\Pages\Schemas\PageForm;
use Xavcha\PageContentManager\Filament\Resources\Pages\Tables\PagesTable;
use Xavcha\PageContentManager\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}






