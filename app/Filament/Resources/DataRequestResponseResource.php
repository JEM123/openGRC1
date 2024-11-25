<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource\Pages;
use App\Models\DataRequestResponse;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DataRequestResponseResource extends Resource
{
    protected static ?string $model = DataRequestResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Response')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('response')
                            ->required(function ($get, $record) {
                                if (is_null($record)) {
                                    return false;
                                }
                                $auditManagerId = $record->manager_id ?: 0;
                                $currentUserId = auth()->id();
                                return $currentUserId !== $auditManagerId;
                            }),

                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                Textarea::make('description'),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->preserveFilenames()
                                    ->disk('private')
                                    ->directory(function () {
                                        return "attachments/" . Carbon::now()->timestamp . '-' . Str::random(2);
                                    })
                                    ->storeFileNamesIn('file_name')
                                    ->visibility('private')
                                    ->openable()
                                    ->deletable(true)
                                    ->reorderable(),

                                Hidden::make('uploaded_by')
                                    ->default(Auth::id()),
                                Hidden::make('audit_id')
                                    ->default(function ($livewire) {
                                        $drr = DataRequestResponse::where('id', $livewire->data['id'])->first();
                                        return $drr->dataRequest->audit_id;
                                    }),
                            ]),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dataRequest.details')
                    ->label('Data Request Details')
                    ->wrap()
                    ->html()
                    ->limit(200),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester'),
                Tables\Columns\TextColumn::make('requestee.name')
                    ->label('Requestee'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ResponseStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataRequestResponses::route('/'),
            'create' => Pages\CreateDataRequestResponse::route('/create'),
            'edit' => Pages\EditDataRequestResponse::route('/{record}/edit'),
            'view' => Pages\ViewDataRequestResponse::route('/{record}'),
        ];
    }

}
