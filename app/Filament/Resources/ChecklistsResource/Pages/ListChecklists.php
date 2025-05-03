<?php

namespace App\Filament\Resources\ChecklistsResource\Pages;

use App\Filament\Resources\ChecklistsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecklists extends ListRecords
{
    protected static string $resource = ChecklistsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
