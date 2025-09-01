<?php

namespace App\Filament\Resources;
use Closure;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Resources\ProjectResource\RelationManagers\ClientRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\StepsRelationManager;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\TugBoat;
use App\Models\Barge;
use App\Models\Sop;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Support\Enums\Alignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use IbrahimBougaoua\FilaProgress\Tables\Columns\ProgressBar;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\Section as FormSection;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use IbrahimBougaoua\FilaProgress\Infolists\Components\ProgressBarEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Filament\Actions\CreateAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Swis\Filament\Activitylog\Tables\Actions\ActivitylogAction;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationGroup = 'Project Management';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Kegiatan';
    protected static ?string $modelLabel = 'Kegiatan';
    protected static ?string $pluralModelLabel = 'Kegiatan';

    protected static ?string $recordTitleAttribute = 'name';


    /**
     * Update activity name based on selected vessels
     */
    public static function updateActivityName(Forms\Set $set, Forms\Get $get): void
    {
        $tugBoatId = $get('tug_boat_id');
        $bargeId = $get('barge_id');
        $currentName = $get('name');
        
        // Only auto-generate if both vessels are selected
        if ($tugBoatId && $bargeId) {
            $tugBoat = TugBoat::find($tugBoatId);
            $barge = Barge::find($bargeId);
            
            if ($tugBoat && $barge) {
                $generatedName = "Kegiatan {$tugBoat->code} & {$barge->code}";
                
                // Only update if current name is empty or was previously auto-generated
                if (empty($currentName) || str_starts_with($currentName, 'Kegiatan ')) {
                    $set('name', $generatedName);
                }
            }
        } elseif ($tugBoatId && !$bargeId) {
            // If only tug boat is selected
            $tugBoat = TugBoat::find($tugBoatId);
            if ($tugBoat && (empty($currentName) || str_starts_with($currentName, 'Kegiatan '))) {
                $set('name', "Kegiatan {$tugBoat->code}");
            }
        } elseif ($bargeId && !$tugBoatId) {
            // If only barge is selected
            $barge = Barge::find($bargeId);
            if ($barge && (empty($currentName) || str_starts_with($currentName, 'Kegiatan '))) {
                $set('name', "Kegiatan {$barge->code}");
            }
        }
    }

    /**
     * Update activity name for action form
     */
    public static function updateActivityNameFromAction(Forms\Set $set, Forms\Get $get, $record): void
    {
        $tugBoatId = $get('tug_boat_id');
        $bargeId = $get('barge_id');
        
        if ($tugBoatId && $bargeId) {
            $tugBoat = TugBoat::find($tugBoatId);
            $barge = Barge::find($bargeId);
            
            if ($tugBoat && $barge) {
                $newName = "Kegiatan {$tugBoat->code} & {$barge->code}";
                $set('update_name', $newName);
            }
        } elseif ($tugBoatId) {
            $tugBoat = TugBoat::find($tugBoatId);
            if ($tugBoat) {
                $set('update_name', "Kegiatan {$tugBoat->code}");
            }
        } elseif ($bargeId) {
            $barge = Barge::find($bargeId);
            if ($barge) {
                $set('update_name', "Kegiatan {$barge->code}");
            }
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    
                    Forms\Components\Wizard\Step::make('Assignment Kapal')
                        ->description('Tugaskan Tug Boat dan tongkang')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Select::make('tug_boat_id')
                                ->label('Tug Boat ')
                                ->options(function () {
                                    return TugBoat::get()
                                        ->pluck('display_name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Pilih Tug Boat ')
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    static::updateActivityName($set, $get);
                                })
                                ->helperText('Pilih Tug Boat  yang tersedia untuk kegiatan ini'),

                            Select::make('barge_id')
                                ->label('Tongkang')
                                ->options(function () {
                                    return Barge::get()
                                        ->pluck('display_name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Pilih tongkang')
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    static::updateActivityName($set, $get);
                                })
                                ->helperText('Pilih tongkang yang tersedia untuk kegiatan ini'),
                        ])->columns(2),
                    Forms\Components\Wizard\Step::make('Informasi Kegiatan')
                        ->description('Informasi dasar kegiatan')
                        ->icon('heroicon-o-clipboard-document')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Nama Kegiatan')
                                ->maxLength(255)
                                ->placeholder('Nama akan otomatis dibuat dari kombinasi kapal')
                                ->live(onBlur: true)
                                ->columnSpanFull(),

                            DatePicker::make('start_date')
                                ->label('Tanggal Mulai')
                                ->date()
                                ->native(false)
                                ->placeholder('Pilih tanggal mulai')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $dueDate = $get('due_date');
                                    if ($state && $dueDate && $state > $dueDate) {
                                        $set('due_date', null);
                                    }
                                }),

                            DatePicker::make('due_date')
                                ->label('Tanggal Selesai')
                                ->required()
                                ->date()
                                ->native(false)
                                ->placeholder('Pilih tanggal selesai')
                                ->afterOrEqual('start_date'),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => '📝 Draft',
                                    'analysis' => '🔍 Analysis', 
                                    'in_progress' => '⚡ In Progress',
                                    'completed' => '✅ Completed',
                                    'review' => '👁️ Review',
                                    'completed (Not Payed Yet)' => '💰 Completed (Not Paid Yet)',
                                    'canceled' => '❌ Canceled',
                                ])
                                ->required()
                                ->native(false)
                                ->default('draft'),

                            Select::make('sop_id')
                                ->label('Standard Operating Procedure')
                                ->options(Sop::query()->pluck('name', 'id'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (!$state) {
                                        $set('steps', []);
                                        return;
                                    }

                                    $sop = Sop::with(['steps.tasks', 'steps.requiredDocuments'])->find($state);
                                    if (!$sop) return;

                                    // Prepare steps data
                                    $stepsData = $sop->steps->map(function ($step) {
                                        return [
                                            'name' => $step->name,
                                            'description' => $step->description,
                                            'order' => $step->order,
                                            'tasks' => $step->tasks->map(fn($task) => [
                                                'title' => $task->title,
                                                'description' => $task->description,
                                                'requires_document' => $task->requires_document,
                                            ])->toArray(),
                                            'requiredDocuments' => $step->requiredDocuments->map(fn($doc) => [
                                                'name' => $doc->name,
                                                'description' => $doc->description,
                                                'is_required' => $doc->is_required,
                                            ])->toArray(),
                                        ];
                                    })->toArray();

                                    $set('steps', $stepsData);
                                })
                                ->native(false),

                            Forms\Components\RichEditor::make('description')
                                ->label('Deskripsi')
                                ->placeholder('Deskripsi kegiatan...')
                                ->toolbarButtons([
                                    'bold',
                                    'bulletList',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'undo',
                                    'redo',
                                ])
                                ->columnSpanFull(),

                        ])->columns(2),


                    Forms\Components\Wizard\Step::make('Tahapan Kegiatan')
                        ->description('Konfigurasi tahapan, tugas, dan dokumen')
                        ->icon('heroicon-o-squares-plus')
                        ->schema([
                            Repeater::make('steps')
                                ->label('Tahapan Kegiatan')
                                ->addActionLabel('Tambah Tahapan Baru')
                                ->relationship()
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Tahapan')
                                        ->required()
                                        ->placeholder('Masukkan nama tahapan')
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('description')
                                        ->label('Deskripsi Tahapan')
                                        ->placeholder('Deskripsi tahapan...')
                                        ->toolbarButtons([
                                            'bold',
                                            'bulletList',
                                            'italic',
                                            'link',
                                            'orderedList',
                                            'undo',
                                            'redo',
                                        ])
                                        ->columnSpanFull(),

                                    FormSection::make('Tugas-tugas')
                                        ->description('Tambah dan kelola tugas untuk tahapan ini')
                                        ->collapsible()
                                        ->schema([
                                            Repeater::make('tasks')
                                                ->label('Tugas')
                                                ->relationship('tasks')
                                                ->schema([
                                                    TextInput::make('title')
                                                        ->label('Judul Tugas')
                                                        ->required()
                                                        ->placeholder('Masukkan judul tugas'),

                                                    Forms\Components\RichEditor::make('description')
                                                        ->label('Deskripsi Tugas')
                                                        ->placeholder('Deskripsi tugas...')
                                                        ->toolbarButtons([
                                                            'bold',
                                                            'bulletList',
                                                            'italic',
                                                            'link',
                                                            'orderedList',
                                                            'undo',
                                                            'redo',
                                                        ]),
                                                ])
                                                ->collapsed()
                                                ->itemLabel(fn(array $state): ?string => $state['title'] ?? null)
                                                ->addActionLabel('Tambah Tugas Baru'),
                                        ]),

                                    FormSection::make('Dokumen yang Diperlukan')
                                        ->description('Tentukan dokumen yang diperlukan untuk tahapan ini')
                                        ->collapsible()
                                        ->schema([
                                            Repeater::make('requiredDocuments')
                                                ->label('Dokumen yang Diperlukan')
                                                ->relationship('requiredDocuments')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Nama Dokumen')
                                                        ->required()
                                                        ->placeholder('Masukkan nama dokumen'),

                                                    Forms\Components\RichEditor::make('description')
                                                        ->label('Deskripsi Dokumen')
                                                        ->placeholder('Deskripsi dokumen...')
                                                        ->toolbarButtons([
                                                            'bold',
                                                            'bulletList',
                                                            'italic',
                                                            'link',
                                                            'orderedList',
                                                            'undo',
                                                            'redo',
                                                        ]),
                                                ])
                                                ->collapsed()
                                                ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                                                ->addActionLabel('Tambah Dokumen Baru')
                                        ])
                                ])
                                ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                                ->orderColumn('order')
                                ->collapsed()
                                ->reorderable(true)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->sortable(false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kegiatan')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('vessels_display')
                    ->label('Kapal Ditugaskan')
                    ->badge()
                    ->color('info')
                    ->wrap(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Selesai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('duration_in_days')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} hari" : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'analysis' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'review' => 'purple',
                        'completed (Not Payed Yet)' => 'orange',
                        'canceled' => 'danger',
                    })
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => '📝 Draft',
                        'analysis' => '🔍 Analysis',
                        'in_progress' => '⚡ In Progress',
                        'completed' => '✅ Completed',
                        'review' => '👁️ Review',
                        'completed (Not Payed Yet)' => '💰 Completed (Not Paid)',
                        'canceled' => '❌ Canceled',
                        default => Str::title($state)
                    }),

                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Status Waktu')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (Project $record): string => 
                        $record->is_overdue ? 'Terlambat' : 'Tepat Waktu'
                    ),

                // Progress Bar
                ProgressBar::make('bar')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $steps = $record->steps;
                        $totalItems = 0;
                        $completedItems = 0;

                        foreach ($steps as $step) {
                            $totalItems++;
                            if ($step->status === 'completed') {
                                $completedItems++;
                            }

                            $tasks = $step->tasks;
                            $totalItems += $tasks->count();
                            $completedItems += $tasks->where('status', 'completed')->count();

                            $documents = $step->requiredDocuments;
                            $totalItems += $documents->count();
                            $completedItems += $documents->where('status', 'approved')->count();
                        }

                        return [
                            'total' => $totalItems ?: 1,
                            'progress' => $completedItems,
                        ];
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Filters
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'draft' => '📝 Draft',
                        'analysis' => '🔍 Analysis',
                        'in_progress' => '⚡ In Progress',
                        'completed' => '✅ Completed',
                        'review' => '👁️ Review',
                        'completed (Not Payed Yet)' => '💰 Completed (Not Paid)',
                        'canceled' => '❌ Canceled',
                    ])
                    ->default(['draft', 'analysis', 'in_progress']),

                SelectFilter::make('tug_boat_id')
                    ->label('Kapal Tunda')
                    ->options(TugBoat::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('barge_id')
                    ->label('Tongkang')
                    ->options(Barge::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),


                DateRangeFilter::make('start_date')
                    ->label('Tanggal Mulai'),
                    
                DateRangeFilter::make('due_date')
                    ->label('Tanggal Selesai'),
            ])
            // Row Actions
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ActivitylogAction::make(),
                    RelationManagerAction::make('project-step-relation-manager')
                        ->label('Project Step')
                        ->slideOver()
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->relationManager(StepsRelationManager::make()),
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->label('Actions')
            ])
            
            ->recordUrl(
                fn(Project $record): string =>
                static::getUrl('view', ['record' => $record])
            )
            ->defaultSort('status', 'asc')
            ->groups([
                'client.name',
                'pic.name', // Add PIC grouping option
            ])
            // Bulk Actions
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_assign_pic')
                        ->label('Assign PIC to Projects')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->form([
                            Select::make('pic_id')
                                ->label('Select Person in Charge (PIC)')
                                ->options(function () {
                                    $user = auth()->user();
                                    
                                    if ($user->hasRole('super-admin')) {
                                        // Super admin can assign any user as PIC
                                        return User::whereHas('userClients')->pluck('name', 'id');
                                    }
                                    
                                    // Regular users can only assign PICs from their clients
                                    return User::whereHas('userClients', function ($query) use ($user) {
                                        $query->whereIn('client_id', $user->userClients()->pluck('client_id'));
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->helperText('This PIC will be assigned to all selected projects'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $picUser = User::find($data['pic_id']);
                            $updatedCount = 0;
                            
                            if (!$picUser) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Selected PIC tidak ditemukan.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            \DB::transaction(function () use ($records, $data, $picUser, &$updatedCount) {
                                foreach ($records as $project) {
                                    // Check if user has permission to assign PIC to this project
                                    $user = auth()->user();
                                    if (!$user->hasRole('super-admin')) {
                                        $hasAccess = $user->userClients()
                                            ->where('client_id', $project->client_id)
                                            ->exists();
                                            
                                        if (!$hasAccess) {
                                            continue; // Skip projects user doesn't have access to
                                        }
                                    }

                                    $project->update(['pic_id' => $data['pic_id']]);
                                    $updatedCount++;
                                }
                            });

                            if ($updatedCount > 0) {
                                Notification::make()
                                    ->title('PIC Berhasil Ditugaskan')
                                    ->body("Berhasil menugaskan {$picUser->name} sebagai PIC untuk {$updatedCount} proyek.")
                                    ->success()
                                    ->send();

                                // Send notification to the assigned PIC
                                if ($picUser->id !== auth()->id()) {
                                    Notification::make()
                                        ->title('Anda Ditugaskan sebagai PIC')
                                        ->body("Anda telah ditugaskan sebagai Person in Charge untuk {$updatedCount} proyek baru.")
                                        ->info()
                                        ->sendToDatabase($picUser);
                                }
                            } else {
                                Notification::make()
                                    ->title('Tidak Ada Proyek yang Diperbarui')
                                    ->body('Tidak ada proyek yang dapat diperbarui. Pastikan Anda memiliki akses ke proyek yang dipilih.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Assign PIC ke Multiple Projects')
                        ->modalDescription('Pilih Person in Charge yang akan ditugaskan ke semua proyek yang dipilih.')
                        ->modalSubmitActionLabel('Assign PIC'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    

    public static function getRelations(): array
    {
        return [
                //
            StepsRelationManager::class,
            ClientRelationManager::class
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Client' => $record->client->name,
            'Status' => $record->status,
            'PIC' => $record->pic?->name ?? 'No PIC assigned', // Add PIC to search results
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'pic.name']; // Add PIC name to searchable attributes
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ProjectResource::getUrl('view', ['record' => $record]);
    }


    public static function sendProjectNotifications(string $title, string $body, $project, string $type = 'info', ?string $action = null): void
    {
        // Create the notification template
        $notificationTemplate = Notification::make()
            ->title($title)
            ->body($body)
            ->icon(match ($type) {
                'success' => 'heroicon-o-check-circle',
                'danger' => 'heroicon-o-x-circle',
                'warning' => 'heroicon-o-exclamation-triangle',
                default => 'heroicon-o-information-circle',
            })
            ->persistent();

        // Add action if provided
        if ($action) {
            $notificationTemplate->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label($action)
                    ->url(static::getUrl('view', ['record' => $project->id])),
                \Filament\Notifications\Actions\Action::make('Mark As Read')
                    ->markAsRead(),
            ]);
        }

        // Optimize recipient query - use a single query with joins instead of eager loading
        $recipients = \App\Models\User::select('users.*')
            ->join('user_projects', 'users.id', '=', 'user_projects.user_id')
            ->where('user_projects.project_id', $project->id)
            ->where('users.id', '!=', auth()->id()) // Exclude current user
            ->distinct()
            ->get();

        // Also notify the PIC if they exist and aren't already in the recipients
        if ($project->pic && !$recipients->contains('id', $project->pic->id) && $project->pic->id !== auth()->id()) {
            $recipients->push($project->pic);
        }

        // Use database transaction to ensure all notifications are created or none
        \DB::transaction(function () use ($recipients, $notificationTemplate, $type) {
            // Process in chunks for large recipient lists
            foreach ($recipients->chunk(50) as $chunk) {
                foreach ($chunk as $user) {
                    $notificationTemplate->sendToDatabase($user)->broadcast($user);
                }
            }
        });

        // Send UI notification to current user
        Notification::make()
            ->title($title)
            ->body($body)
            ->{$type}()
            ->send();
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'activity' => Pages\ViewProjectActivity::route('/{record}/activity'),
        ];
    }
}