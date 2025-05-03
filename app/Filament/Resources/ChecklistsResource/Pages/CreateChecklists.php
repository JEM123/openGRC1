<?php

namespace App\Filament\Resources\ChecklistsResource\Pages;

use App\Filament\Resources\ChecklistsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklists extends CreateRecord
{
    protected static string $resource = ChecklistsResource::class;

    protected function afterCreate(): void
    {
        $this->record->checklist_template->checklist_template_items->each(function ($item) {
            $this->record->checklist_responses()->create([
                'title' => $item->title,
                'description' => $item->description,
                'type' => $item->type,
                'user_id' => auth()->user()->id,
                'order' => $item->order,
                'response' => "",
                'notes' => "",
            ]);
        });
    }
}
