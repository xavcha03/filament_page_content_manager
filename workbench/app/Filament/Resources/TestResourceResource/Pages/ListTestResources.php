<?php

namespace App\Filament\Resources\TestResourceResource\Pages;

use App\Filament\Resources\TestResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestResources extends ListRecords
{
    protected static string $resource = TestResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

