<?php

namespace App\Filament\Resources\ChecklistsResource\Pages;

use App\Filament\Resources\ChecklistsResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;

class ViewChecklist extends ViewRecord
{
    protected static string $resource = ChecklistsResource::class;
    protected static string $view = 'filament.resources.checklists-resource.pages.view-checklist';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('items.responses');
    }
} 