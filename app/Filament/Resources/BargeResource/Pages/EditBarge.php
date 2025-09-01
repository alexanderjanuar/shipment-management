<?php

namespace App\Filament\Resources\BargeResource\Pages;

use App\Filament\Resources\BargeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBarge extends EditRecord
{
    protected static string $resource = BargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
