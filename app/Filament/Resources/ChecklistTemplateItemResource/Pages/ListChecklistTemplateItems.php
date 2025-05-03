<?php

namespace App\Filament\Resources\ChecklistTemplateItemResource\Pages;

use App\Filament\Resources\ChecklistTemplateItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecklistTemplateItems extends ListRecords
{
    protected static string $resource = ChecklistTemplateItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
