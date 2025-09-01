<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserClientResource\Pages;
use App\Filament\Resources\UserClientResource\RelationManagers;
use App\Models\UserClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Client;

use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class UserClientResource extends Resource
{
    protected static ?string $model = UserClient::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?string $modelLabel = 'Karyawan';
    protected static ?string $pluralModelLabel = 'Karyawan';
    protected static ?string $breadcrumb = 'Karyawan';


        protected static bool $shouldRegisterNavigation = false;
    

    protected static ?string $navigationGroup = 'Master Data';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detail Pengguna')
                ->description('Buat atau edit informasi anggota tim')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->required()
                        ->placeholder('Masukkan nama lengkap')
                        ->maxLength(255)
                        ->label('Nama'),

                    Forms\Components\TextInput::make('user.email')
                        ->email()
                        ->required()
                        ->unique('users', 'email', ignoreRecord: true)
                        ->placeholder('email@contoh.com')
                        ->label('Email'),

                    Forms\Components\FileUpload::make('user.avatar_path')
                        ->label('Foto Avatar')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('300')
                        ->imageResizeTargetHeight('300')
                        ->maxSize(5120) // 5MB max
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->helperText('Unggah dan edit file gambar (maksimal 5MB). Klik edit untuk memotong dan menyesuaikan gambar.'),

                    Forms\Components\TextInput::make('user.avatar_url')
                        ->label('URL Avatar (Alternatif)')
                        ->url()
                        ->placeholder('https://contoh.com/avatar.jpg')
                        ->helperText('Atau masukkan URL jika Anda tidak ingin mengunggah file'),

                    Forms\Components\TextInput::make('user.password')
                        ->password()
                        ->required(fn($context) => $context === 'create')
                        ->dehydrated(fn($state) => filled($state))
                        ->revealable()
                        ->autocomplete('new-password')
                        ->label('Kata Sandi')
                ])
                ->aside(),
            Section::make('Penugasan')
                ->description('Tugaskan beberapa klien')
                ->schema([
                    Forms\Components\Select::make('client_ids')
                        ->multiple()
                        ->searchable()
                        ->label('Klien')
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->loadingMessage('Memuat klien...')
                        ->optionsLimit(50)
                        ->options(fn() => !auth()->user()->hasRole('super-admin')
                            ? \App\Models\Client::where('id', auth()->user()->userClients()->first()->client_id)->pluck('name', 'id')
                            : \App\Models\Client::pluck('name', 'id')),
                ])
                ->aside()
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                ->whereHas('userClients')
                ->withCount('userClients')
                ->with('roles')
            )
            ->defaultSort(function ($query) {
                return $query->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->orderByRaw("
                        CASE roles.name 
                            WHEN 'direktur' THEN 1
                            WHEN 'project-manager' THEN 2  
                            WHEN 'staff' THEN 3
                            WHEN 'client' THEN 4
                            ELSE 5
                        END ASC
                    ");
            })
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(60),
                    
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                    
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'project-manager' => 'success',
                        'direktur' => 'warning',
                        'staff' => 'info',
                        'client' => 'primary',
                        'verificator' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'project-manager' => 'Manajer Proyek',
                        'direktur' => 'Direktur',
                        'staff' => 'Staf',
                        'client' => 'Klien',
                        'verificator' => 'Verifikator',
                        default => $state,
                    }),
                    
                TextColumn::make('user_clients_count')
                    ->label('Klien yang Ditugaskan')
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Klien')
                    ->native(false)
                    ->multiple()
                    ->searchable()
                    ->options(function () {
                        if (auth()->user()->hasRole('super-admin')) {
                            return Client::pluck('name', 'id');
                        }

                        return Client::whereIn(
                            'id',
                            auth()->user()->userClients->pluck('client_id')
                        )->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('userClients', function ($query) use ($data) {
                                $query->where('client_id', $data['value']);
                            });
                        }
                    })
                    ->visible(fn() => !auth()->user()->hasRole('staff')),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Peran')
                    ->native(false)
                    ->options(fn() => \Spatie\Permission\Models\Role::whereNot('name', 'super-admin')
                        ->pluck('name', 'name')
                        ->mapWithKeys(fn($role, $key) => [
                            $key => match ($key) {
                                'project-manager' => 'Manajer Proyek',
                                'direktur' => 'Direktur',
                                'staff' => 'Staf',
                                default => $key
                            }
                        ]))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('roles', function ($query) use ($data) {
                                $query->where('name', $data['value']);
                            });
                        }
                    })
                    ->visible(fn() => !auth()->user()->hasRole('staff'))
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('change_avatar')
                        ->label('Ubah Avatar')
                        ->icon('heroicon-m-camera')
                        ->color('info')
                        ->modalHeading(fn($record) => "Ubah Avatar untuk {$record->name}")
                        ->modalIcon('heroicon-o-camera')
                        ->modalDescription('Unggah gambar avatar baru atau berikan URL.')
                        ->modalWidth('2xl')
                        ->form([
                            Forms\Components\Section::make('Avatar Saat Ini')
                                ->schema([
                                    Forms\Components\Placeholder::make('current_avatar')
                                        ->label('')
                                        ->content(function ($record) {
                                            $avatarUrl = $record->avatar;
                                            $source = '';
                                            if ($record->avatar_path) {
                                                $source = 'File yang diunggah';
                                            } elseif ($record->avatar_url) {
                                                $source = 'URL eksternal';
                                            } else {
                                                $source = 'Default yang dibuat';
                                            }
                                            
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex items-center space-x-4">
                                                    <img src="' . $avatarUrl . '" alt="Avatar Saat Ini" class="w-20 h-20 rounded-full object-cover shadow-lg">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">Avatar Saat Ini</p>
                                                        <p class="text-xs text-gray-500">Sumber: ' . $source . '</p>
                                                    </div>
                                                </div>'
                                            );
                                        })
                                ]),
                            
                            Forms\Components\Tabs::make('Opsi Avatar')
                                ->tabs([
                                    Forms\Components\Tabs\Tab::make('Unggah & Edit')
                                        ->icon('heroicon-m-photo')
                                        ->schema([
                                            Forms\Components\FileUpload::make('avatar_file')
                                                ->label('Unggah & Edit Avatar')
                                                ->image()
                                                ->imageEditor()
                                                ->disk('public')
                                                ->directory('avatars')
                                                ->visibility('public')
                                                ->imageResizeMode('cover')
                                                ->imageCropAspectRatio('1:1')
                                                ->imageResizeTargetWidth('300')
                                                ->imageResizeTargetHeight('300')
                                                ->maxSize(5120) // 5MB max
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                                                ->helperText('Unggah gambar dan klik tombol edit untuk memotong, memutar, dan menyesuaikan sebelum menyimpan.')
                                                ->columnSpanFull()
                                        ]),
                                    
                                    Forms\Components\Tabs\Tab::make('Gunakan URL')
                                        ->icon('heroicon-m-link')
                                        ->schema([
                                            Forms\Components\TextInput::make('avatar_url')
                                                ->label('URL Avatar')
                                                ->url()
                                                ->placeholder('https://contoh.com/avatar.jpg')
                                                ->helperText('Masukkan URL langsung ke gambar')
                                                ->default(fn($record) => $record->avatar_url)
                                                ->live(onBlur: true)
                                                ->columnSpanFull(),
                                                
                                            Forms\Components\Section::make('Pratinjau URL')
                                                ->schema([
                                                    Forms\Components\Placeholder::make('url_preview')
                                                        ->label('')
                                                        ->content(function ($get) {
                                                            $url = $get('avatar_url');
                                                            if (!$url) {
                                                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Masukkan URL di atas untuk melihat pratinjau</p>');
                                                            }
                                                            return new \Illuminate\Support\HtmlString(
                                                                '<div class="flex items-center space-x-4">
                                                                    <img src="' . $url . '" alt="Pratinjau URL" class="w-20 h-20 rounded-full object-cover shadow-lg" onerror="this.src=\'https://via.placeholder.com/80x80/EF4444/FFFFFF?text=Error\'; this.className=\'w-20 h-20 rounded-full bg-red-100 flex items-center justify-center text-red-500 text-xs\';">
                                                                    <div>
                                                                        <p class="text-sm font-medium text-gray-900">Pratinjau URL</p>
                                                                        <p class="text-xs text-gray-500">Avatar baru dari URL</p>
                                                                    </div>
                                                                </div>'
                                                            );
                                                        })
                                                ])
                                                ->visible(fn($get) => filled($get('avatar_url')))
                                        ]),
                                    
                                    Forms\Components\Tabs\Tab::make('Hapus Avatar')
                                        ->icon('heroicon-m-trash')
                                        ->schema([
                                            Forms\Components\Placeholder::make('remove_info')
                                                ->label('')
                                                ->content(new \Illuminate\Support\HtmlString(
                                                    '<div class="p-4 bg-red-50 rounded-lg border border-red-200">
                                                        <div class="flex items-center space-x-2">
                                                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                            </svg>
                                                            <p class="text-sm font-medium text-red-800">Hapus Avatar Saat Ini</p>
                                                        </div>
                                                        <p class="text-xs text-red-600 mt-1">Ini akan menghapus avatar saat ini dan kembali ke avatar default yang dibuat.</p>
                                                    </div>'
                                                )),
                                            
                                            Forms\Components\Checkbox::make('remove_avatar')
                                                ->label('Ya, hapus avatar saat ini')
                                                ->helperText('Centang kotak ini untuk mengkonfirmasi penghapusan avatar')
                                        ])
                                ])
                        ])
                        ->action(function (array $data, User $record): void {
                            // Handle avatar removal
                            if (!empty($data['remove_avatar'])) {
                                $record->deleteOldAvatar();
                                $record->update([
                                    'avatar_url' => null,
                                    'avatar_path' => null
                                ]);
                                
                                Notification::make()
                                    ->title('Avatar Dihapus')
                                    ->success()
                                    ->body("Avatar dihapus untuk {$record->name}. Sekarang menggunakan avatar default.")
                                    ->send();
                                return;
                            }
                            
                            // Handle file upload with editing
                            if (!empty($data['avatar_file'])) {
                                // Delete old avatar file if exists
                                $record->deleteOldAvatar();
                                
                                // Generate the storage URL with 'storage/' prefix
                                $avatarUrl = 'storage/' . $data['avatar_file'];
                                
                                $record->update([
                                    'avatar_path' => $data['avatar_file'],
                                    'avatar_url' => $avatarUrl // Set both path and URL with storage/ prefix
                                ]);
                                
                                Notification::make()
                                    ->title('Avatar Diperbarui')
                                    ->success()
                                    ->body("Berhasil mengunggah dan mengedit avatar baru untuk {$record->name}")
                                    ->send();
                                return;
                            }
                            
                            // Handle URL update
                            if (!empty($data['avatar_url'])) {
                                // Delete old avatar file if exists (since we're switching to URL)
                                $record->deleteOldAvatar();
                                
                                $record->update([
                                    'avatar_url' => $data['avatar_url'],
                                    'avatar_path' => null // Clear file path when using URL
                                ]);
                                
                                Notification::make()
                                    ->title('Avatar Diperbarui')
                                    ->success()
                                    ->body("Berhasil memperbarui URL avatar untuk {$record->name}")
                                    ->send();
                                return;
                            }
                            
                            // If no action taken
                            Notification::make()
                                ->title('Tidak Ada Perubahan')
                                ->warning()
                                ->body('Tidak ada perubahan avatar yang dilakukan.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('assign_client')
                        ->label('Tugaskan Klien')
                        ->icon('heroicon-m-building-office')
                        ->color('warning')
                        ->visible(fn() => auth()->user()->hasRole(['super-admin', 'direktur', 'project-manager']))
                        ->modalHeading(fn($record) => "Tugaskan Klien ke {$record->name}")
                        ->form(function ($record) {
                            $assignedClientIds = $record->userClients()
                                ->pluck('client_id')
                                ->toArray();

                            return [
                                Forms\Components\Select::make('client_id')
                                    ->label('Klien')
                                    ->multiple()
                                    ->options(
                                        \App\Models\Client::whereNotIn('id', $assignedClientIds)
                                            ->pluck('name', 'id')
                                    )
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->loadingMessage('Memuat klien...')
                                    ->helperText('Pilih klien untuk ditugaskan ke pengguna ini.')
                            ];
                        })
                        ->action(function (array $data, User $record): void {
                            foreach ($data['client_id'] as $clientId) {
                                UserClient::create([
                                    'user_id' => $record->id,
                                    'client_id' => $clientId
                                ]);
                            }

                            Notification::make()
                                ->title('Klien Ditugaskan')
                                ->success()
                                ->body("Berhasil menugaskan klien ke {$record->name}")
                                ->send();
                        }),

                    Tables\Actions\Action::make('assign_role')
                        ->label('Tugaskan Peran')
                        ->icon('heroicon-m-user-group')
                        ->color('success')
                        ->modalHeading(fn($record) => "Tugaskan Peran ke {$record->name}")
                        ->modalIcon('heroicon-o-user-circle')
                        ->modalDescription('Pilih peran untuk ditugaskan ke karyawan ini.')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('Peran')
                                ->options(function () {
                                    $user = auth()->user();
                                    $roles = \Spatie\Permission\Models\Role::whereNot('name', 'super-admin');

                                    if (!$user->hasRole('super-admin') && !$user->hasRole('direktur')) {
                                        return [];
                                    }

                                    if ($user->hasRole('direktur')) {
                                        $roles->whereIn('name', ['project-manager', 'staff']);
                                    }

                                    return $roles->pluck('name', 'name')
                                        ->mapWithKeys(fn($role, $key) => [
                                            $key => match ($key) {
                                                'project-manager' => 'Manajer Proyek',
                                                'direktur' => 'Direktur',
                                                'staff' => 'Staf',
                                                'client' => 'Klien',
                                                default => $key
                                            }
                                        ]);
                                })
                                ->required()
                                ->searchable()
                                ->placeholder('Pilih peran')
                                ->disabled(fn() => !auth()->user()->hasRole(['super-admin', 'direktur']))
                        ])
                        ->action(function (array $data, User $record): void {
                            $record->syncRoles([$data['role']]);
                            Notification::make()
                                ->title('Peran Ditugaskan')
                                ->success()
                                ->body("Berhasil menugaskan peran ke {$record->name}")
                                ->send();
                        }),

                    Tables\Actions\Action::make('unassign_client')
                        ->label('Batalkan Penugasan Klien')
                        ->icon('heroicon-m-building-office-2')
                        ->color('danger')
                        ->visible(fn() => auth()->user()->hasRole(['super-admin', 'direktur', 'project-manager']))
                        ->modalHeading(fn($record) => "Batalkan Penugasan Klien dari {$record->name}")
                        ->form(function ($record) {
                            $assignedClients = $record->userClients()
                                ->join('clients', 'user_clients.client_id', '=', 'clients.id')
                                ->pluck('clients.name', 'user_clients.id')
                                ->toArray();

                            return [
                                Forms\Components\Select::make('user_client_ids')
                                    ->label('Klien yang Ditugaskan')
                                    ->multiple()
                                    ->options($assignedClients)
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->loadingMessage('Memuat klien yang ditugaskan...')
                                    ->helperText('Pilih klien untuk dibatalkan penugasannya dari pengguna ini.')
                            ];
                        })
                        ->action(function (array $data, User $record): void {
                            // Delete the selected user_client relationships
                            UserClient::whereIn('id', $data['user_client_ids'])->delete();

                            Notification::make()
                                ->title('Penugasan Klien Dibatalkan')
                                ->success()
                                ->body("Berhasil membatalkan penugasan klien dari {$record->name}")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalButton('Batalkan Klien yang Dipilih'),
                ])
            ])
            ->headerActions([
                Tables\Actions\Action::make('attach_user')
                    ->label('Lampirkan Pengguna yang Tidak Ditugaskan')
                    ->icon('heroicon-m-user-plus')
                    ->color('gray')
                    ->visible(fn() => auth()->user()->hasRole('super-admin'))
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Pengguna')
                            ->options(function () {
                                return User::whereDoesntHave('userClients')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih pengguna yang tidak memiliki penugasan klien'),

                        Forms\Components\Section::make('Pilih Klien')
                            ->schema([
                                Forms\Components\CheckboxList::make('client_ids')
                                    ->label('Klien')
                                    ->options(Client::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columns(2)
                                    ->helperText('Pilih klien untuk ditugaskan ke pengguna ini')
                            ])
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['client_ids'] as $clientId) {
                            UserClient::create([
                                'user_id' => $data['user_id'],
                                'client_id' => $clientId
                            ]);
                        }

                        $userName = User::find($data['user_id'])->name;

                        Notification::make()
                            ->title('Pengguna Ditugaskan')
                            ->success()
                            ->body("Berhasil menugaskan {$userName} ke klien yang dipilih")
                            ->send();
                    })
                    ->modalHeading('Lampirkan Pengguna ke Klien')
                    ->modalDescription('Pilih pengguna yang tidak ditugaskan dan klien untuk ditugaskan kepada mereka.')
                    ->requiresConfirmation()
                    ->modalButton('Lampirkan Pengguna'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assignRole')
                        ->label('Tugaskan Peran')
                        ->icon('heroicon-m-user-group')
                        ->color('success')
                        ->modalHeading('Tugaskan Peran ke Karyawan yang Dipilih')
                        ->modalIcon('heroicon-o-user-circle')
                        ->modalDescription('Pilih peran untuk ditugaskan ke semua karyawan yang dipilih.')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('Peran')
                                ->options(function () {
                                    $user = auth()->user();
                                    $roles = \Spatie\Permission\Models\Role::whereNot('name', 'super-admin');

                                    if (!$user->hasRole('super-admin') && !$user->hasRole('direktur')) {
                                        return [];
                                    }

                                    if ($user->hasRole('direktur')) {
                                        $roles->whereIn('name', ['project-manager', 'staff']);
                                    }

                                    return $roles->pluck('name', 'name')
                                        ->mapWithKeys(fn($role, $key) => [
                                            $key => match ($key) {
                                                'project-manager' => 'Manajer Proyek',
                                                'direktur' => 'Direktur',
                                                'staff' => 'Staf',
                                                default => $key
                                            }
                                        ]);
                                })
                                ->required()
                                ->searchable()
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn($record) => $record->syncRoles([$data['role']]));

                            Notification::make()
                                ->title('Peran Ditugaskan')
                                ->success()
                                ->body('Berhasil menugaskan peran ke karyawan yang dipilih.')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery();

        if (auth()->user()->hasRole('super-admin')) {
            return $query;
        }

        if (auth()->user()->hasRole(['direktur', 'project-manager'])) {
            return $query->whereHas('client', function ($q) {
                $q->whereIn('id', auth()->user()->userClients->pluck('client_id'));
            });
        }

        if (auth()->user()->hasRole('staff')) {
            return $query->where('user_id', auth()->user()->id);
        }

        return $query;
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
            'index' => Pages\ListUserClients::route('/'),
            'create' => Pages\CreateUserClient::route('/create'),
            'edit' => Pages\EditUserClient::route('/{record}/edit'),
            'view' => Pages\ViewUserClient::route('/{record}'),
        ];
    }
}