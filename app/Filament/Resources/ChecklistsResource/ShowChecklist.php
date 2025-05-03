<?php

namespace App\Filament\Resources\ChecklistsResource\Pages;

use App\Filament\Resources\ChecklistsResource;
use Filament\Resources\Pages\ViewRecord;


class ShowChecklist extends ViewRecord
{
    protected static string $resource = ChecklistsResource::class;

    
    public function getViewData(): array
    {
        return [
            'checklist_items' => $this->record->checklist_items,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.resources.checklists-resource.pages.show-checklist', [
            'checklist_items' => $this->record->checklist_items,
        ]);
    }


}