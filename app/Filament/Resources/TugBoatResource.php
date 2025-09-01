<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TugBoatResource\Pages;
use App\Models\TugBoat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class TugBoatResource extends Resource
{
    protected static ?string $model = TugBoat::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Tug Boats';

    protected static ?string $modelLabel = 'Tug Boats';

    protected static ?string $pluralModelLabel = 'Tug Boats';

    protected static ?string $navigationGroup = 'Vessel Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tug Boats')
                    ->description('Data dasar Tug Boats')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kapal')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: TB Bayu Laut'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kapal')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Contoh: TB001')
                            ->alphaDash()
                            ->helperText('Gunakan format unik seperti TB001, TB002, dll'),


                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => '🟢 Aktif',
                                'maintenance' => '🔧 Maintenance',
                                'inactive' => '⚪ Tidak Aktif',
                            ])
                            ->default('active')
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Deskripsi tambahan tentang kapal tunda ini...'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kapal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),


                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'maintenance' => 'warning',
                        'inactive' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => '🟢 Aktif',
                        'maintenance' => '🔧 Maintenance',
                        'inactive' => '⚪ Tidak Aktif',
                    }),

                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Total Proyek')
                    ->counts('projects')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => '🟢 Aktif',
                        'maintenance' => '🔧 Maintenance',
                        'inactive' => '⚪ Tidak Aktif',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Ketersediaan')
                    ->placeholder('Semua kapal')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('maintenance')
                    ->label('Maintenance')
                    ->icon('heroicon-o-wrench')
                    ->color('warning')
                    ->visible(fn (TugBoat $record): bool => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(fn (TugBoat $record) => $record->setToMaintenance()),

                Tables\Actions\Action::make('activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TugBoat $record): bool => $record->status !== 'active')
                    ->requiresConfirmation()
                    ->action(fn (TugBoat $record) => $record->setToActive()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('set_maintenance')
                        ->label('Set ke Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->setToMaintenance();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Kapal Tunda')
                    ->schema([
                        Infolists\Components\TextEntry::make('display_name')
                            ->label('Nama & Kode')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('power_display')
                            ->label('Daya Mesin'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'maintenance' => 'warning',
                                'inactive' => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Tidak ada deskripsi'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistik Proyek')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_projects_count')
                            ->label('Total Proyek')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('active_projects_count')
                            ->label('Proyek Aktif')
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('is_available')
                            ->label('Status Ketersediaan')
                            ->formatStateUsing(fn (bool $state): string => $state ? '✅ Tersedia' : '❌ Sedang Digunakan')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d F Y, H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d F Y, H:i'),
                    ])
                    ->columns(2)
                    ->collapsible(),
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
            'index' => Pages\ListTugBoats::route('/'),
            'create' => Pages\CreateTugBoat::route('/create'),
            'view' => Pages\ViewTugBoat::route('/{record}'),
            'edit' => Pages\EditTugBoat::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}