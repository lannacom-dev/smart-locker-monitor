<?php

namespace App\Filament\Resources\LockerEventResource\Pages;

use App\Filament\Resources\LockerEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLockerEvent extends EditRecord
{
    protected static string $resource = LockerEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
