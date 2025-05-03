<?php

namespace App\Filament\Resources\ChecklistResponsesResource\Pages;

use App\Filament\Resources\ChecklistResponsesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistResponses extends EditRecord
{
    protected static string $resource = ChecklistResponsesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
