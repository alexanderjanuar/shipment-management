<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
use App\Models\TugBoat;
use App\Models\Barge;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Only keep the fields that belong to the projects table
        $projectData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : null,
            'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date'])->format('Y-m-d') : null,
            'status' => $data['status'] ?? 'draft',
            'sop_id' => $data['sop_id'] ?? null,
            'tug_boat_id' => $data['tug_boat_id'] ?? null,
            'barge_id' => $data['barge_id'] ?? null,
        ];

        // Store user project data for use after creation
        $this->userProjectData = $data['userProject'] ?? [];

        return $projectData;
    }

    protected function afterCreate(): void
    {
        $project = $this->record;

        // Create user project relationships from form data
        foreach ($this->userProjectData as $userData) {
            $project->userProject()->create([
                'user_id' => $userData['user_id'],
                'role' => $userData['role'] ?? 'staff'
            ]);
        }

        // Update vessel status if assigned
        if ($project->tug_boat_id || $project->barge_id) {
            $this->updateVesselStatus($project);
        }

        // Log the creation
        \Log::info('Kegiatan created with vessels', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'tug_boat_id' => $project->tug_boat_id,
            'barge_id' => $project->barge_id,
            'assigned_users' => collect($this->userProjectData)->pluck('user_id')->toArray(),
            'created_by' => auth()->user()->name
        ]);

        // Prepare vessel information for notification
        $vesselInfo = $this->getVesselNotificationInfo($project);

        // Send notifications to assigned users
        if (!empty($this->userProjectData)) {
            $assignedUserIds = collect($this->userProjectData)->pluck('user_id')->toArray();
            $assignedUsers = User::whereIn('id', $assignedUserIds)->get();

            foreach ($assignedUsers as $user) {
                if ($user->id !== auth()->id()) {
                    Notification::make()
                        ->title('Kegiatan Baru Ditugaskan')
                        ->body(sprintf(
                            "Anda telah ditugaskan ke kegiatan: <strong>%s</strong>%s<br>Status: %s<br>Tanggal Selesai: %s",
                            $project->name,
                            $vesselInfo,
                            ucwords(str_replace('_', ' ', $project->status)),
                            $project->due_date ? $project->due_date->format('d M Y') : '-'
                        ))
                        ->success()
                        ->actions([
                            Action::make('view')
                                ->label('Lihat Kegiatan')
                                ->url(ProjectResource::getUrl('view', ['record' => $project->id]))
                        ])
                        ->sendToDatabase($user);
                }
            }
        }

        // Show success notification with vessel details
        $this->showSuccessNotification($project, $vesselInfo);
    }

    /**
     * Update vessel status when assigned to project
     */
    protected function updateVesselStatus($project): void
    {
        // Log vessel assignment activity
        if ($project->tugBoat) {
            activity()
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->log("Kapal Tunda {$project->tugBoat->display_name} ditugaskan ke kegiatan {$project->name}");
        }

        if ($project->barge) {
            activity()
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->log("Tongkang {$project->barge->display_name} ditugaskan ke kegiatan {$project->name}");
        }
    }

    /**
     * Get vessel information for notifications
     */
    protected function getVesselNotificationInfo($project): string
    {
        $vesselInfo = '';
        
        if ($project->tugBoat || $project->barge) {
            $vessels = [];
            
            if ($project->tugBoat) {
                $vessels[] = "Kapal Tunda: {$project->tugBoat->display_name}";
            }
            
            if ($project->barge) {
                $vessels[] = "Tongkang: {$project->barge->display_name}";
            }
            
            $vesselInfo = '<br><strong>Kapal:</strong> ' . implode(', ', $vessels);
        }

        return $vesselInfo;
    }

    /**
     * Show success notification with vessel and assignment details
     */
    protected function showSuccessNotification($project, string $vesselInfo): void
    {
        $assignedCount = count($this->userProjectData);
        $roles = collect($this->userProjectData)->pluck('role')->unique()->implode(', ');
        
        $notificationBody = "Kegiatan berhasil dibuat";
        
        if ($assignedCount > 0) {
            $notificationBody .= " dan ditugaskan ke {$assignedCount} anggota tim";
            if ($roles) {
                $notificationBody .= " dengan peran: {$roles}";
            }
        }
        
        if ($project->tugBoat || $project->barge) {
            $vessels = [];
            if ($project->tugBoat) $vessels[] = $project->tugBoat->display_name;
            if ($project->barge) $vessels[] = $project->barge->display_name;
            
            $notificationBody .= "<br>Kapal ditugaskan: " . implode(' & ', $vessels);
        }
        
        Notification::make()
            ->title('Kegiatan Berhasil Dibuat')
            ->body($notificationBody)
            ->success()
            ->duration(6000)
            ->actions([
                Action::make('view')
                    ->label('Lihat Kegiatan')
                    ->url(ProjectResource::getUrl('view', ['record' => $project->id]))
            ])
            ->send();
    }

    protected $userProjectData = [];
}