<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistsResource\Pages;
use App\Filament\Resources\ChecklistsResource\RelationManagers;
use App\Models\Checklist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class ChecklistsResource extends Resource
{
    protected static ?string $model = Checklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('checklist_template_id')
                    ->relationship('checklist_template', 'title')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->required(),
                Forms\Components\Select::make('visibility')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'simple' => 'Simple Checklist',
                        'access' => 'Access Review',
                    ])
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->user()->id)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('visibility'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('Owner')
                    ->formatStateUsing(function ($record) {
                        return $record->user->name;
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChecklistsResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklists::route('/'),
            'create' => Pages\CreateChecklists::route('/create'),
            'edit' => Pages\EditChecklists::route('/{record}/edit'),
            'view' => Pages\ViewChecklist::route('/{record}'),
            //'view' => Pages\ShowChecklist::route('/{record}'),
        ];
    }
}
