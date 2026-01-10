<?php

namespace App\Filament\Resources\TestResourceResource\Pages;

use App\Filament\Resources\TestResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestResource extends EditRecord
{
    protected static string $resource = TestResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}



