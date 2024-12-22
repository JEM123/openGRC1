<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundleResource\Pages;
use App\Http\Controllers\BundleController;
use App\Models\Bundle;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';

    protected static ?string $navigationGroup = 'Settings';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        Tables\Columns\TextColumn::make('na')
                            ->state(function () {
                                return new HtmlString('
                                        <h3 class="font-bold text-lg">Standard</h3>
                                ');
                            })
                            ->badge()
                            ->columnSpanFull()
                            ->color('warning'),
                        Tables\Columns\TextColumn::make('code')
                            ->label('Code')
                            ->state(function (Bundle $record) {
                                return new HtmlString("<span class='font-bold'>Code: </span><br>$record->code");
                            })
                            ->sortable()
                            ->searchable(),
                        Tables\Columns\TextColumn::make('version')
                            ->label('Version')
                            ->state(function (Bundle $record) {
                                return new HtmlString("<span class='font-bold'>Rev: </span><br>$record->version");
                            })
                            ->sortable()
                            ->searchable(),
                        Tables\Columns\TextColumn::make('authority')
                            ->label('Authority')
                            ->state(function (Bundle $record) {
                                return new HtmlString("<span class='font-bold'>Source: </span><br>$record->authority");
                            })
                            ->sortable()
                            ->searchable(),
                        Tables\Columns\TextColumn::make('name')
                            ->label('Name')
                            ->weight('bold')
                            ->size('lg')
                            ->sortable()
                            ->columnSpanFull()
                            ->searchable(),
                        Tables\Columns\TextColumn::make('description')
                            ->label('Description')
                            ->limit(200)
                            ->columnSpanFull()
                            ->sortable()
                            ->searchable(),
                    ]),
            ])
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->paginationPageOptions([9, 18, 27])
            ->defaultSort('code', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->label('Details'),
                Action::make('Import')
                    ->label(function ($record) {
                        $status = Bundle::where('code', $record->code)->first();
                        if ($status->status == 'imported') {
                            return new HtmlString('Re-Import Bundle');
                        } else {
                            return new HtmlString('Import Bundle');
                        }
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->modalContent(function () {
                        return new HtmlString('
                                <div>This action will import the selected bundle into your OpenGRC. If you already have
                                content in OpenGRC with the same codes, this will overwrite that data.</div>');
                    })
                    ->modalHeading('Bundle Import')
                    ->modalIconColor('danger')
                    ->action(function (Bundle $record) {
                        Notification::make()
                            ->title('Import Started')
                            ->body("Importing bundle with code: {$record->code}")
                            ->send();
                        BundleController::importBundle($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('fetch')
                    ->label('Fetch Bundles Updates')
                    ->button()
                    ->modalContent(function () {
                        return new HtmlString('
                                <div>This action will fetch the latest bundles from the OpenGRC repository and add them to your OpenGRC.</div>');
                    })
                    ->modalHeading('Fetch Bundles')
                    ->modalIconColor('danger')
                    ->action(function () {
                        Notification::make()
                            ->title('Fetch Started')
                            ->body('Fetching the latest bundles from the OpenGRC repository.')
                            ->send();
                        BundleController::retrieve();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('authority')
                    ->options(Bundle::pluck('authority', 'authority')->toArray())
                    ->label('Authority'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Content Bundle Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code'),
                        TextEntry::make('version'),
                        TextEntry::make('authority'),
                        TextEntry::make('name')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundles::route('/'),
        ];
    }
}
