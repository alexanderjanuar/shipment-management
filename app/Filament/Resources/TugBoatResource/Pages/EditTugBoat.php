<?php

namespace App\Filament\Resources\TugBoatResource\Pages;

use App\Filament\Resources\TugBoatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTugBoat extends EditRecord
{
    protected static string $resource = TugBoatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
