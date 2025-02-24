<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Http\Controllers\HelperController;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\ListRecords;
use Spatie\Activitylog\Models\Activity;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\Status;
use App\Models\Activity as CustomActivity;
use Filament\Tables\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ActivityLogResource extends Resource
{
    protected static ?string $model = CustomActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('User Email')
                    ->formatStateUsing(function ($state) {
                        $json = json_decode($state, true);
                        return $json['email'] ?? '-';
                    })
                    ->default('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn($state) => str_replace('App\\Models\\', '', $state))
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'login' => 'Successful Login',
                        'login_failed' => 'Failed Login',
                        'logout' => 'Logout',
                        'login_lockout' => 'Account Lockout',
                    ])
                    ->label('Login Status'),
            ])
            ->actions([
                ViewAction::make()
            ])
            ->bulkActions([])
            ->recordUrl(null);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('event')
                            ->columnSpan(1)
                            ->badge(),
                        TextEntry::make('description')
                            ->columnSpan(1),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('ip_address'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('causer.name')
                            ->label('Performed By')
                            ->default('System'),
                        TextEntry::make('properties')
                            ->columnSpanFull()
                            ->label('Additional Details')
                            ->markdown()
                            ->formatStateUsing(fn($state) => HelperController::prettyPrintJson($state))
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }


}