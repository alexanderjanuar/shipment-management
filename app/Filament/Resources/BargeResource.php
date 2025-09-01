<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BargeResource\Pages;
use App\Models\Barge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class BargeResource extends Resource
{
    protected static ?string $model = Barge::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Tongkang';

    protected static ?string $modelLabel = 'Tongkang';

    protected static ?string $pluralModelLabel = 'Tongkang';

    protected static ?string $navigationGroup = 'Vessel Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tongkang')
                    ->description('Data dasar tongkang')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tongkang')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: BG Sumber Rejeki'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Tongkang')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Contoh: BG001')
                            ->alphaDash()
                            ->helperText('Gunakan format unik seperti BG001, BG002, dll'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => '🟢 Aktif',
                                'maintenance' => '🔧 Maintenance',
                                'loading' => '📦 Loading',
                                'unloading' => '📤 Unloading',
                                'inactive' => '⚪ Tidak Aktif',
                            ])
                            ->default('active')
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Deskripsi tambahan tentang tongkang ini...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tongkang')
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
                        'loading' => 'info',
                        'unloading' => 'info',
                        'inactive' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => '🟢 Aktif',
                        'maintenance' => '🔧 Maintenance',
                        'loading' => '📦 Loading',
                        'unloading' => '📤 Unloading',
                        'inactive' => '⚪ Tidak Aktif',
                    }),

                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Total Proyek')
                    ->counts('projects')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('is_available')
                    ->label('Ketersediaan')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? '✅ Tersedia' : '❌ Tidak Tersedia')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),

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
                        'loading' => '📦 Loading',
                        'unloading' => '📤 Unloading',
                        'inactive' => '⚪ Tidak Aktif',
                    ])
                    ->multiple(),


                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Ketersediaan')
                    ->placeholder('Semua tongkang')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('maintenance')
                        ->label('Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->visible(fn (Barge $record): bool => $record->status !== 'maintenance')
                        ->requiresConfirmation()
                        ->action(fn (Barge $record) => $record->setToMaintenance()),

                    Tables\Actions\Action::make('loading')
                        ->label('Mulai Loading')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (Barge $record): bool => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(fn (Barge $record) => $record->setToLoading()),

                    Tables\Actions\Action::make('unloading')
                        ->label('Mulai Unloading')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->visible(fn (Barge $record): bool => $record->status === 'loading')
                        ->requiresConfirmation()
                        ->action(fn (Barge $record) => $record->setToUnloading()),

                    Tables\Actions\Action::make('complete')
                        ->label('Selesai Operasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Barge $record): bool => in_array($record->status, ['loading', 'unloading']))
                        ->requiresConfirmation()
                        ->action(fn (Barge $record) => $record->completeOperation()),

                    Tables\Actions\Action::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Barge $record): bool => in_array($record->status, ['maintenance', 'inactive']))
                        ->requiresConfirmation()
                        ->action(fn (Barge $record) => $record->setToActive()),
                ])
                ->label('Aksi')
                ->button()
                ->outlined(),
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

                    Tables\Actions\BulkAction::make('set_active')
                        ->label('Set ke Aktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->setToActive();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Tongkang')
                    ->schema([
                        Infolists\Components\TextEntry::make('display_name')
                            ->label('Nama & Kode')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('capacity_display')
                            ->label('Kapasitas'),

                        Infolists\Components\TextEntry::make('cargo_type')
                            ->label('Jenis Kargo')
                            ->placeholder('Tidak ditentukan'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'maintenance' => 'warning',
                                'loading' => 'info',
                                'unloading' => 'info',
                                'inactive' => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Tidak ada deskripsi')
                            ->columnSpanFull(),
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
            'index' => Pages\ListBarges::route('/'),
            'create' => Pages\CreateBarge::route('/create'),
            'view' => Pages\ViewBarge::route('/{record}'),
            'edit' => Pages\EditBarge::route('/{record}/edit'),
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