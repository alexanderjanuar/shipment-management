<?php

namespace App\Filament\Resources\BargeResource\Pages;

use App\Filament\Resources\BargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBarge extends ViewRecord
{
    protected static string $resource = BargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
