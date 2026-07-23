<?php

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Xavcha\PageContentManager\Filament\Resources\Pages\Concerns\HandlesExperienceFormState;
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

class CreatePage extends CreateRecord
{
    use HandlesExperienceFormState;

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->dehydrateExperienceFields($data);
    }
}
