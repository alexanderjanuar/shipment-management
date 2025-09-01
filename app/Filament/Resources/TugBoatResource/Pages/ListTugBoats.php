<?php

namespace App\Filament\Resources\TugBoatResource\Pages;

use App\Filament\Resources\TugBoatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTugBoats extends ListRecords
{
    protected static string $resource = TugBoatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
