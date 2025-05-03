<?php

namespace App\Filament\Resources\ChecklistsResource\Pages;

use App\Filament\Resources\ChecklistsResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;

class ViewChecklist extends ViewRecord
{
    protected static string $resource = ChecklistsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

} 