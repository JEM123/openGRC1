<?php

namespace App\Filament\Resources\ChecklistTemplateItemResource\Pages;

use App\Filament\Resources\ChecklistTemplateItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplateItem extends EditRecord
{
    protected static string $resource = ChecklistTemplateItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
