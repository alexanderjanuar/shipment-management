<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DocumentsOverview;
use App\Models\Client;
use App\Models\SubmittedDocument;
use App\Models\Progress;
use App\Models\Project;
use App\Models\Task;
use App\Models\RequiredDocument;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static string $view = 'filament.pages.dashboard';

    public $previewingDocument = null;
    public $previewUrl = null;
    public $fileType = null;

    // Modal properties
    public $showProjectModal = false;
    public $showDocumentModal = false;
    public $modalTitle = '';
    public $modalData = [];
    public $modalType = '';
    public $currentStatus = '';
    public $currentCount = 0;

    #[On('openProjectModal')]
    public function openProjectModal($status, $count)
    {
        $this->currentStatus = $status;
        $this->currentCount = $count;
        $this->modalType = 'project';
        
        // Set modal title based on status
        $this->modalTitle = match($status) {
            'all' => 'Semua Proyek (' . $count . ')',
            'in_progress' => 'Proyek Aktif (' . $count . ')',
            'completed' => 'Proyek Selesai (' . $count . ')',
            'draft' => 'Proyek Draft (' . $count . ')',
            'canceled' => 'Proyek Dibatalkan (' . $count . ')',
            default => 'Proyek (' . $count . ')'
        };

        // Get projects data
        $this->loadProjectData($status);
        
        // Open the modal using Filament's dispatch system
        $this->dispatch('open-modal', id: 'project-stats-modal');
    }

    #[On('openDocumentModal')]
    public function openDocumentModal($status, $count)
    {
        \Log::info('openDocumentModal called', ['status' => $status, 'count' => $count]);
        
        $this->currentStatus = $status;
        $this->currentCount = $count;
        $this->modalType = 'document';
        
        $this->modalTitle = match($status) {
            'pending_review' => 'Dokumen Pending Review (' . $count . ')',
            'uploaded' => 'Dokumen Terupload (' . $count . ')',
            'approved' => 'Dokumen Disetujui (' . $count . ')',
            'rejected' => 'Dokumen Ditolak (' . $count . ')',
            default => 'Dokumen (' . $count . ')'
        };

        $this->loadDocumentData($status);
        
        \Log::info('Modal data loaded', ['count' => count($this->modalData)]);
        if (!empty($this->modalData)) {
            \Log::info('First document data', $this->modalData[0]);
        }
        
        // Open the modal using Filament's dispatch system
        $this->dispatch('open-modal', id: 'document-stats-modal');
    }

    public function closeModal()
    {
        $this->modalData = [];
        $this->modalTitle = '';
        $this->currentStatus = '';
        $this->currentCount = 0;
        
        // Close both modals using Filament's dispatch system
        $this->dispatch('close-modal', id: 'project-stats-modal');
        $this->dispatch('close-modal', id: 'document-stats-modal');
    }

    private function loadProjectData($status)
    {
        $query = Project::with(['client', 'pic'])
            ->select('id', 'name', 'client_id', 'pic_id', 'status', 'priority', 'due_date', 'created_at');

        // Filter for non-admin users
        if (!auth()->user()->hasRole('super-admin')) {
            $query->whereIn('client_id', function ($subQuery) {
                $subQuery->select('client_id')
                    ->from('user_clients')
                    ->where('user_id', auth()->id());
            });
        }

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $this->modalData = $query->orderBy('created_at', 'desc')
            ->limit(50) // Limit to prevent performance issues
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client->name ?? 'Tidak ada klien',
                    'pic_name' => $project->pic->name ?? 'Belum ditugaskan',
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'due_date' => $project->due_date?->format('d M Y'),
                    'created_at' => $project->created_at->format('d M Y'),
                    'url' => route('filament.admin.resources.projects.view', $project),
                ];
            })
            ->toArray();
    }

    private function loadDocumentData($status)
    {
        $query = RequiredDocument::with(['projectStep.project.client'])
            ->select('id', 'name', 'status', 'project_step_id', 'created_at', 'updated_at')
            ->whereHas('projectStep.project', function ($projectQuery) {
                if (!auth()->user()->hasRole('super-admin')) {
                    $projectQuery->whereIn('client_id', function ($subQuery) {
                        $subQuery->select('client_id')
                            ->from('user_clients')
                            ->where('user_id', auth()->id());
                    });
                }
            });

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $this->modalData = $query->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'name' => $document->name,
                    'project_name' => $document->projectStep?->project?->name ?? 'Tidak ada proyek', // Add null safety
                    'client_name' => $document->projectStep?->project?->client?->name ?? 'Tidak ada klien', // Add null safety
                    'status' => $document->status,
                    'created_at' => $document->created_at->format('d M Y'),
                    'updated_at' => $document->updated_at->format('d M Y H:i'),
                    'url' => $document->projectStep?->project ? route('filament.admin.resources.projects.view', [
                        'record' => $document->projectStep->project->id,
                        'openDocument' => $document->id
                    ]) : '#', // Add null safety for URL
                ];
            })
            ->toArray();
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $currentStatus = request()->query('status', 'in_progress');


        // Get total count before limiting
        $totalClients = Client::count();

        // Get only 5 clients
        $clients = Client::take(5)->get();

        return [
            'clients' => $clients,
            'hasMoreClients' => $totalClients > 5,
            'totalClients' => $totalClients,
        ];
    }
}