<?php

namespace App\Filament\Resources\LockerBoxResource\Pages;

use App\Filament\Resources\LockerBoxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLockerBoxes extends ListRecords
{
    protected static string $resource = LockerBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
