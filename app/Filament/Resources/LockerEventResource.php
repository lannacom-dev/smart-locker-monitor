<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LockerEventResource\Pages;
use App\Filament\Resources\LockerEventResource\RelationManagers;
use App\Models\LockerEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LockerEventResource extends Resource
{
    protected static ?string $model = LockerEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('locker_id')
                    ->relationship('locker', 'name')
                    ->required(),
                Forms\Components\Select::make('locker_box_id')
                    ->relationship('lockerBox', 'id'),
                Forms\Components\TextInput::make('event_type')
                    ->required()
                    ->maxLength(510),
                Forms\Components\Textarea::make('payload')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('locker.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lockerBox.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLockerEvents::route('/'),
            'create' => Pages\CreateLockerEvent::route('/create'),
            'edit' => Pages\EditLockerEvent::route('/{record}/edit'),
        ];
    }
}
