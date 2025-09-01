<?php

namespace App\Filament\Resources\BargeResource\Pages;

use App\Filament\Resources\BargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBarges extends ListRecords
{
    protected static string $resource = BargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
