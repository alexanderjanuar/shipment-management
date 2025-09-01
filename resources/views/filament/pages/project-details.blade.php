<x-filament-panels::page>
    <style>
        @keyframes pulse-subtle {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        @keyframes bounce-subtle {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        @keyframes spin-slow {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .animate-pulse-subtle {
            animation: pulse-subtle 2s infinite;
        }

        .animate-bounce-subtle {
            animation: bounce-subtle 2s infinite;
        }

        .animate-spin-slow {
            animation: spin-slow 3s linear infinite;
        }

        .tab-indicator {
            transition: left 0.3s ease-in-out;
        }

        /* Custom scrollbar for webkit browsers */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Dark mode scrollbar */
        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* Add this to your existing style section */

        /* PIC Button Animations */
        .pic-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pic-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.1);
        }

        .pic-button:active {
            transform: translateY(0);
        }

        /* Pulse animation for unassigned PIC */
        .pic-button-unassigned {
            animation: subtle-pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes subtle-pulse {

            0%,
            100% {
                opacity: 1;
                border-color: rgb(209, 213, 219);
            }

            50% {
                opacity: 0.8;
                border-color: rgb(156, 163, 175);
            }
        }

        /* Modal backdrop enhancement */
        .pic-modal-backdrop {
            backdrop-filter: blur(4px);
            background-color: rgba(0, 0, 0, 0.25);
        }

        /* User selection hover effects */
        .user-selection-item {
            transition: all 0.15s ease-out;
        }

        .user-selection-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Avatar ring animation */
        .avatar-ring {
            transition: all 0.2s ease-out;
        }

        .user-selection-item:hover .avatar-ring {
            ring-width: 3px;
            ring-color: rgb(245, 158, 11);
        }
    </style>



    <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="p-3 sm:p-5">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Client Logo/Initial -->
                <div class="relative flex-shrink-0 mx-auto sm:mx-0">
                    @if($record->client && $record->client->logo)
                    <div class="w-12 h-12 rounded-lg ring-1 ring-gray-100 dark:ring-gray-700">
                        <img src="{{ Storage::url($record->client->logo) }}" alt="{{ $record->client->name }}"
                            class="w-full h-full object-cover rounded-lg">
                    </div>
                    @else
                    <div class="w-12 h-12 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-gray-600 dark:text-gray-300 text-lg font-semibold">
                            {{ $record->client ? substr($record->client->name, 0, 2) : 'P' }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Project Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="text-center sm:text-left">
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white truncate">{{ $record->name }}
                            </h1>
                            <div
                                class="mt-1 flex flex-col sm:flex-row items-center gap-2 sm:gap-3 text-sm text-gray-500 dark:text-gray-400">
                                @if($record->due_date)
                                <span class="flex items-center gap-1">
                                    <x-heroicon-m-calendar-days class="w-4 h-4" />
                                    {{ $record->due_date->format('M d, Y') }}
                                </span>
                                @endif

                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <!-- Team Members Button -->
                            <button x-on:click="$dispatch('open-modal', { id: 'team-members-modal' })"
                                @if(auth()->user()->hasRole(['staff', 'client']))
                                disabled
                                @endif
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3 py-1.5
                                text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors group">
                                <div class="flex items-center -space-x-2">
                                    @php
                                    $nonDirectorMembers = $record->userProject->filter(function($userProject) {
                                    return !$userProject->user->roles->contains('name', 'direktur');
                                    });
                                    @endphp

                                    <div class="flex items-center -space-x-2">
                                        @foreach($nonDirectorMembers->take(4) as $member)
                                        <div class="relative group/tooltip py-3">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($member->user->name) }}"
                                                alt="{{ $member->user->name }}"
                                                class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover transition-transform hover:scale-110"
                                                title="{{ $member->user->name }}">

                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg 
                                opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-200 min-w-max z-[999]
                                shadow-lg">
                                                <span>{{ $member->user->name }}</span>
                                                <div
                                                    class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45">
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach

                                        @php
                                        $nonDirectorCount = $nonDirectorMembers->count();
                                        @endphp

                                        @if($nonDirectorCount > 4)
                                        <div class="relative group/tooltip py-3">
                                            <div
                                                class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 flex items-center justify-center transition-transform hover:scale-110">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">+{{
                                                    $nonDirectorCount - 4 }}</span>
                                            </div>

                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg
                                opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-200 whitespace-nowrap z-50
                                shadow-lg">
                                                {{ $nonDirectorCount - 4 }} more team members
                                                <div
                                                    class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45">
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </button>

                            <div class="hidden sm:block h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

                            <!-- Documents Button -->
                            <button x-on:click="$dispatch('open-modal', { id: 'all-documents-modal' })"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 group">
                                <div class="relative">
                                    <x-heroicon-m-document-text
                                        class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                                    <!-- Document count badge -->
                                    <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                        <span
                                            class="relative inline-flex rounded-full h-4 w-4 bg-primary-500 text-[10px] text-white items-center justify-center font-bold">
                                            {{ $record->steps->flatMap->requiredDocuments->count() }}
                                        </span>
                                    </span>
                                </div>
                                <span
                                    class="group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Documents</span>
                            </button>

                            <!-- Vertical Divider (hidden on mobile) -->
                            <div class="hidden sm:block h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

                            <!-- Status Badge -->
                            <x-filament::badge :color="match($record->status) {
            'completed' => 'success',
            'in_progress' => 'warning',
            'on_hold' => 'danger',
            default => 'gray',
        }">
                                {{ ucwords(str_replace('_', ' ', $record->status)) }}
                            </x-filament::badge>
                        </div>
                    </div>

                    @if($record->description)
                    <div x-data="{ expanded: false }" class="mt-3">
                        <div class="text-sm text-gray-600 dark:text-gray-300 text-center sm:text-left">
                            <!-- Truncated version -->
                            <template x-if="!expanded">
                                <div>
                                    {!! Str::limit(strip_tags($record->description), 100) !!}
                                    @if (strlen(strip_tags($record->description)) > 100)
                                    <button @click="expanded = true" type="button"
                                        class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                        <span class="text-sm font-medium">Read more</span>
                                        <x-heroicon-m-chevron-down class="w-3 h-3 ml-0.5" />
                                    </button>
                                    @endif
                                </div>
                            </template>

                            <!-- Full version -->
                            <template x-if="expanded">
                                <div>
                                    {!! str($record->description)->sanitizeHtml() !!}
                                    <button @click="expanded = false" type="button"
                                        class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                        <span class="text-sm font-medium">Show less</span>
                                        <x-heroicon-m-chevron-up class="w-3 h-3 ml-0.5" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <div class="space-y-6">
        <!-- Project Progress Tracker Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            @php
            $totalSteps = $record->steps->count();
            $totalProgress = 0;
            $totalItems = 0;
            $completedItems = 0;

            foreach ($record->steps as $step) {
            // Count tasks
            $tasks = $step->tasks;
            if ($tasks->count() > 0) {
            $totalItems += $tasks->count();
            $completedItems += $tasks->where('status', 'completed')->count();
            }

            // Count documents
            $documents = $step->requiredDocuments;
            if ($documents->count() > 0) {
            $totalItems += $documents->count();
            $completedItems += $documents->where('status', 'approved')->count();
            }
            }

            // Calculate overall progress percentage
            $progressPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
            @endphp

            <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-6 sm:gap-8">
                    <!-- Progress Circle -->
                    <div class="relative flex-shrink-0 mx-auto sm:mx-0">
                        <div class="w-16 sm:w-20 h-16 sm:h-20">
                            <svg class="w-full h-full" viewBox="0 0 36 36">
                                <!-- Background Circle -->
                                <path d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#E5E7EB" stroke-width="3"
                                    stroke-linecap="round" class="dark:stroke-gray-700" />

                                <!-- Progress Circle -->
                                <path d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#F59E0B" stroke-width="3"
                                    stroke-linecap="round" stroke-dasharray="{{ $progressPercentage * 1.01 }}, 100" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <span class="text-base sm:text-xl font-bold text-gray-900 dark:text-white">{{
                                        $progressPercentage }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Info -->
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 gap-2">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Progress Kegiatan</h2>
                            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Completed: {{ $completedItems
                                        }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Remaining: {{ $totalItems -
                                        $completedItems }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full transition-all duration-500"
                                    style="width: {{ $progressPercentage }}%">
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row justify-between mt-2 gap-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $completedItems }}/{{ $totalItems }} items completed
                                </p>
                                @if($record->due_date)
                                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <x-heroicon-m-calendar class="w-4 h-4" />
                                    Due {{ $record->due_date->format('M d, Y') }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="p-4 sm:p-6">
                <div class="relative">
                    <!-- Vertical Line -->
                    <div class="absolute left-6 top-0 h-full w-0.5 bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>


                    <!-- Steps -->
                    <div class="space-y-6 sm:space-y-8">
                        @foreach($record->steps->sortBy('order') as $step)
                        @php
                        $hasUploadedDocs = $step->requiredDocuments()->where('status', 'uploaded')->exists();
                        $isCompleted = $step->status === 'completed';
                        $isActive = $step->status === 'in_progress';
                        $isPending = !$isCompleted && !$isActive && $step->status === 'in_progress';

                        // Calculate step progress
                        $totalTasks = $step->tasks->count();
                        $completedTasks = $step->tasks->where('status', 'completed')->count();
                        $stepProgress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
                        @endphp

                        <div x-data="{ isOpen: false }"
                            class="relative transition-all duration-200 rounded-lg cursor-pointer"
                            @click="isOpen = !isOpen">
                            <!-- Step Header -->
                            <div class="flex items-start group">
                                <!-- Status Circle -->
                                <div @class([ 'relative z-10 flex items-center justify-center w-10 sm:w-12 h-10 sm:h-12 rounded-full border-2 transition-all duration-300'
                                    , 'bg-green-500 border-green-500'=> $isCompleted && !$hasUploadedDocs,
                                    'bg-blue-500 border-blue-500 ring-4 ring-blue-100 dark:ring-blue-900' =>
                                    $hasUploadedDocs,
                                    'bg-amber-500 border-amber-500 ring-4 ring-amber-100 dark:ring-amber-900' =>
                                    $isActive && !$hasUploadedDocs,
                                    'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600' => $isPending &&
                                    !$hasUploadedDocs,
                                    ])>
                                    @if($isCompleted && !$hasUploadedDocs)
                                    <svg class="w-5 sm:w-6 h-5 sm:h-6 text-white" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                    @else
                                    <span
                                        class="text-base sm:text-lg font-semibold {{ ($isActive || $hasUploadedDocs) ? 'text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $step->order }}
                                    </span>
                                    @endif
                                </div>


                                <!-- Step Content -->
                                <div class="flex-1 ml-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3
                                                    class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">
                                                    {{ $step->name }}
                                                </h3>

                                                @php
                                                $uploadedDocsCount = $step->requiredDocuments()
                                                ->where('status', 'uploaded')
                                                ->count();
                                                @endphp

                                                @if($uploadedDocsCount > 0)
                                                <div
                                                    class="relative inline-flex items-center gap-1 px-2 py-1 rounded-md bg-blue-500 transition-all duration-300 ease-in-out shadow-sm">
                                                    <!-- Pulse effect overlay -->
                                                    <div
                                                        class="animate-[ping_3s_cubic-bezier(0,0,0.2,1)_infinite] absolute inline-flex h-full w-full rounded-md bg-blue-400/40 opacity-50">
                                                    </div>
                                                    <!-- Badge content -->
                                                    <span class="relative text-xs font-medium text-white">
                                                        {{ $uploadedDocsCount }} Document Uploaded
                                                    </span>
                                                </div>
                                                @endif
                                            </div>
                                            <div x-data="{ expanded: false }" class="mt-3">
                                                <div
                                                    class="text-sm text-gray-600 dark:text-gray-300 text-center sm:text-left">
                                                    <!-- Truncated version -->
                                                    <template x-if="!expanded">
                                                        <div>
                                                            {!! Str::limit(strip_tags($step->description), 100) !!}
                                                            @if (strlen(strip_tags($step->description)) > 100)
                                                            <button @click="expanded = true" type="button"
                                                                class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                                                <span class="text-sm font-medium">Read more</span>
                                                                <x-heroicon-m-chevron-down class="w-3 h-3 ml-0.5" />
                                                            </button>
                                                            @endif
                                                        </div>
                                                    </template>


                                                    <!-- Full version -->
                                                    <template x-if="expanded">
                                                        <div>
                                                            {!! str($step->description)->sanitizeHtml() !!}
                                                            <button @click="expanded = false" type="button"
                                                                class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                                                <span class="text-sm font-medium">Show less</span>
                                                                <x-heroicon-m-chevron-up class="w-3 h-3 ml-0.5" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="self-start p-2 transition-transform duration-300"
                                            :class="{ 'rotate-180': isOpen }">
                                            <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400" />
                                        </div>
                                    </div>

                                    @php
                                    // Calculate step progress including tasks and documents
                                    $totalItems = 0;
                                    $completedItems = 0;

                                    // Count tasks
                                    $totalTasks = $step->tasks->count();
                                    $completedTasks = $step->tasks->where('status', 'completed')->count();
                                    $totalItems += $totalTasks;
                                    $completedItems += $completedTasks;

                                    // Count required documents
                                    $totalDocs = $step->requiredDocuments->count();
                                    $completedDocs = $step->requiredDocuments->where('status', 'approved')->count();
                                    $totalItems += $totalDocs;
                                    $completedItems += $completedDocs;

                                    // Calculate overall step progress
                                    $stepProgress = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;
                                    @endphp

                                    <!-- Progress Bar (if has tasks) -->
                                    @if($totalItems > 0)
                                    <div class="mt-3">
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1 gap-2">
                                            <span>
                                                @if($totalTasks > 0)
                                                {{ $completedTasks }}/{{ $totalTasks }} Tasks
                                                @endif
                                                @if($totalTasks > 0 && $totalDocs > 0)
                                                •
                                                @endif
                                                @if($totalDocs > 0)
                                                {{ $completedDocs }}/{{ $totalDocs }} Documents
                                                @endif
                                                Completed
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span>{{ number_format($stepProgress) }}%</span>
                                                <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                                <span class="text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                                    <x-heroicon-m-clock class="w-3 h-3" />
                                                    Updated {{ $step->updated_at->format('M d, Y H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Step Progress Bar -->
                                        <div
                                            class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 
                                                {{ match(true) {
                                                    $stepProgress == 100 => 'bg-green-500',
                                                    $stepProgress >= 70 => 'bg-amber-500',
                                                    $stepProgress >= 30 => 'bg-amber-400',
                                                    default => 'bg-amber-300'
                                                } }}" style="width: {{ $stepProgress }}%">
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Expandable Content -->
                            <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0" class="mt-4 ml-4 sm:ml-16 space-y-4"
                                @click.stop>

                                <!-- Tasks Section -->
                                @if($step->tasks->isNotEmpty())
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Tasks</h4>
                                    <div class="space-y-2">
                                        @foreach($step->tasks as $task)
                                        <div x-data="{ showComments: false }"
                                            class="flex flex-col bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                            <!-- Task Main Content -->
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3">
                                                <!-- In the task section, update the checkbox -->
                                                <input type="checkbox" wire:click="toggleTaskStatus({{ $task->id }})"
                                                    @class([ 'w-4 h-4 rounded border-gray-300 dark:border-gray-600 flex-shrink-0'
                                                    , 'text-amber-600 focus:ring-amber-500 dark:text-amber-500 dark:focus:ring-amber-400'=> 
                                                    !auth()->user()->hasRole(['staff', 'client']),
                                                    'text-gray-300 dark:text-gray-600 cursor-not-allowed' => 
                                                    auth()->user()->hasRole(['staff', 'client'])
                                                    ])
                                                    {{ $task->status === 'completed' ? 'checked' : '' }}
                                                    @disabled(auth()->user()->hasRole(['staff', 'client']))>

                                                <div class="flex-1 min-w-0">
                                                    <div
                                                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                        <p
                                                            class="text-sm font-medium text-gray-900 dark:text-gray-100 
                                                            {{ $task->status === 'completed' ? 'line-through text-gray-500 dark:text-gray-400' : '' }}">
                                                            {{ $task->title }}
                                                        </p>
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                                            {{ $task->updated_at->format('M d, Y H:i') }}
                                                        </span>
                                                    </div>
                                                    @if($task->due_date)
                                                    <div class="flex flex-wrap items-center gap-3 mt-0.5">
                                                        <p
                                                            class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                            <x-heroicon-m-calendar class="w-3 h-3" />
                                                            Due {{ $task->due_date->format('M d, Y H:i') }}
                                                        </p>
                                                        @if($task->status === 'completed')
                                                        <p
                                                            class="text-xs text-green-500 dark:text-green-400 flex items-center gap-1">
                                                            <x-heroicon-m-check-circle class="w-3 h-3" />
                                                            Completed {{ $task->completed_at?->format('M d, Y H:i') }}
                                                        </p>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>

                                                <div
                                                    class="flex flex-wrap sm:flex-nowrap items-center gap-2 mt-2 sm:mt-0">
                                                    <!-- Task Status Button & Dropdown -->
                                                    <div x-data="{ open: false }" class="relative flex-1 sm:flex-none">
                                                        <button @click="open = !open"
                                                            @class([ 'w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200 shadow-sm'
                                                            , 'opacity-85'=> auth()->user()->hasRole(['staff',
                                                            'client']),
                                                            'bg-green-50 dark:bg-green-900 text-green-700
                                                            dark:text-green-300 hover:bg-green-100
                                                            dark:hover:bg-green-800 ring-1 ring-green-200
                                                            dark:ring-green-700' => $task->status === 'completed',
                                                            'bg-amber-50 dark:bg-amber-900 text-amber-700
                                                            dark:text-amber-300 hover:bg-amber-100
                                                            dark:hover:bg-amber-800 ring-1 ring-amber-200
                                                            dark:ring-amber-700' => $task->status === 'in_progress',
                                                            'bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-300
                                                            hover:bg-red-100 dark:hover:bg-red-800 ring-1 ring-red-200
                                                            dark:ring-red-700' => $task->status === 'blocked',
                                                            'bg-gray-50 dark:bg-gray-800 text-gray-700
                                                            dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700
                                                            ring-1 ring-gray-200 dark:ring-gray-700' => $task->status
                                                            === 'pending'
                                                            ])
                                                            @if(auth()->user()->hasRole(['staff', 'client']))
                                                            disabled
                                                            @endif
                                                            >
                                                            <!-- Status Indicator Dot -->
                                                            <span class="relative flex h-2 w-2">
                                                                <span
                                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                                                    :class="{
                                                                        'bg-green-400 dark:bg-green-500': '{{ $task->status }}' === 'completed',
                                                                        'bg-amber-400 dark:bg-amber-500': '{{ $task->status }}' === 'in_progress',
                                                                        'bg-red-400 dark:bg-red-500': '{{ $task->status }}' === 'blocked',
                                                                        'bg-gray-400 dark:bg-gray-500': '{{ $task->status }}' === 'pending'
                                                                    }">
                                                                </span>
                                                                <span class="relative inline-flex rounded-full h-2 w-2"
                                                                    :class="{
                                                                        'bg-green-500 dark:bg-green-400': '{{ $task->status }}' === 'completed',
                                                                        'bg-amber-500 dark:bg-amber-400': '{{ $task->status }}' === 'in_progress',
                                                                        'bg-red-500 dark:bg-red-400': '{{ $task->status }}' === 'blocked',
                                                                        'bg-gray-500 dark:bg-gray-400': '{{ $task->status }}' === 'pending'
                                                                    }">
                                                                </span>
                                                            </span>
                                                            {{ ucfirst($task->status) }}
                                                            @if(!auth()->user()->hasRole(['staff', 'client']))
                                                            <x-heroicon-m-chevron-down
                                                                class="w-4 h-4 transition-transform" />
                                                            @endif
                                                        </button>

                                                        <!-- Status Dropdown Menu - Only show if not staff -->
                                                        @if(!auth()->user()->hasRole(['staff', 'client']))
                                                        <!-- Status Dropdown Menu -->
                                                        <div x-show="open"
                                                            x-transition:enter="transition ease-out duration-200"
                                                            x-transition:enter-start="opacity-0 scale-95"
                                                            x-transition:enter-end="opacity-100 scale-100"
                                                            x-transition:leave="transition ease-in duration-150"
                                                            x-transition:leave-start="opacity-100 scale-100"
                                                            x-transition:leave-end="opacity-0 scale-95"
                                                            @click.away="open = false"
                                                            class="absolute right-0 z-10 mt-2 w-44 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 divide-y divide-gray-100 dark:divide-gray-700">

                                                            <div class="py-1">
                                                                <button
                                                                    x-on:click="$dispatch('open-modal', { id: 'confirm-status-modal-{{ $task->id }}' }); 
                                                                        $wire.updateTaskStatus({{ $task->id }}, 'pending')"
                                                                    class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                                    <span
                                                                        class="mr-3 h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                                                                    Pending
                                                                </button>
                                                                <button
                                                                    x-on:click="$dispatch('open-modal', { id: 'confirm-status-modal-{{ $task->id }}' }); 
                                                                        $wire.updateTaskStatus({{ $task->id }}, 'in_progress')"
                                                                    class="group flex w-full items-center px-4 py-2 text-sm text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900">
                                                                    <span
                                                                        class="mr-3 h-2 w-2 rounded-full bg-amber-400 dark:bg-amber-500 animate-ping"></span>
                                                                    In Progress
                                                                </button>
                                                                <button
                                                                    x-on:click="$dispatch('open-modal', { id: 'confirm-status-modal-{{ $task->id }}' }); 
                                                                        $wire.updateTaskStatus({{ $task->id }}, 'completed')"
                                                                    class="group flex w-full items-center px-4 py-2 text-sm text-green-700 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900">
                                                                    <span
                                                                        class="mr-3 h-2 w-2 rounded-full bg-green-400 dark:bg-green-500"></span>
                                                                    Completed
                                                                </button>
                                                                <button
                                                                    x-on:click="$dispatch('open-modal', { id: 'confirm-status-modal-{{ $task->id }}' }); 
                                                                        $wire.updateTaskStatus({{ $task->id }}, 'blocked')"
                                                                    class="group flex w-full items-center px-4 py-2 text-sm text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900">
                                                                    <span
                                                                        class="mr-3 h-2 w-2 rounded-full bg-red-400 dark:bg-red-500"></span>
                                                                    Blocked
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <x-filament::modal id="confirm-status-modal-{{ $task->id }}"
                                                            width="md">
                                                            <div class="p-2 space-y-6">
                                                                <!-- Header -->
                                                                <div class="flex items-center gap-4">
                                                                    <div @class([ 'w-12 h-12 rounded-full flex items-center justify-center'
                                                                        , 'bg-gray-100 dark:bg-gray-800'=>
                                                                        $newTaskStatus === 'pending',
                                                                        'bg-amber-100 dark:bg-amber-900' =>
                                                                        $newTaskStatus === 'in_progress',
                                                                        'bg-green-100 dark:bg-green-900' =>
                                                                        $newTaskStatus === 'completed',
                                                                        'bg-red-100 dark:bg-red-900' => $newTaskStatus
                                                                        === 'blocked'
                                                                        ])>
                                                                        <x-heroicon-o-arrow-path-rounded-square
                                                                            @class([ 'w-6 h-6'
                                                                            , 'text-gray-600 dark:text-gray-400'=>
                                                                            $newTaskStatus === 'pending',
                                                                            'text-amber-600 dark:text-amber-400' =>
                                                                            $newTaskStatus === 'in_progress',
                                                                            'text-green-600 dark:text-green-400' =>
                                                                            $newTaskStatus === 'completed',
                                                                            'text-red-600 dark:text-red-400' =>
                                                                            $newTaskStatus === 'blocked'
                                                                            ]) />
                                                                    </div>

                                                                    <div>
                                                                        <h2
                                                                            class="text-lg font-medium text-gray-900 dark:text-white">
                                                                            Update Task Status</h2>
                                                                        <p
                                                                            class="text-sm text-gray-500 dark:text-gray-400">
                                                                            Are you sure you want to proceed?</p>
                                                                    </div>
                                                                </div>

                                                                <!-- Content -->
                                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                                                    <div class="flex items-center justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600 dark:text-gray-400">Current
                                                                            Status</span>
                                                                        <x-filament::badge :color="match($task->status) {
                                                                        'completed' => 'success',
                                                                        'in_progress' => 'warning',
                                                                        'blocked' => 'danger',
                                                                        default => 'gray'
                                                                    }">
                                                                            {{ ucfirst($task->status) }}
                                                                        </x-filament::badge>
                                                                    </div>

                                                                    <div class="flex items-center justify-between mt-3">
                                                                        <span
                                                                            class="text-sm text-gray-600 dark:text-gray-400">New
                                                                            Status</span>
                                                                        <x-filament::badge :color="match($newTaskStatus) {
                                                                        'completed' => 'success',
                                                                        'in_progress' => 'warning',
                                                                        'blocked' => 'danger',
                                                                        default => 'gray'
                                                                    }">
                                                                            {{ ucfirst($newTaskStatus) }}
                                                                        </x-filament::badge>
                                                                    </div>
                                                                </div>

                                                                <!-- Actions -->
                                                                <div class="flex justify-end gap-3">
                                                                    <x-filament::button color="gray"
                                                                        x-on:click="$dispatch('close-modal', { id: 'confirm-status-modal-{{ $task->id }}' })">
                                                                        Cancel
                                                                    </x-filament::button>
                                                                    <x-filament::button wire:click="confirmStatusChange"
                                                                        :color="match($newTaskStatus) {
                                                                        'completed' => 'success',
                                                                        'in_progress' => 'warning',
                                                                        'blocked' => 'danger',
                                                                        default => 'gray'
                                                                    }">
                                                                        Update Status
                                                                    </x-filament::button>
                                                                </div>
                                                            </div>
                                                        </x-filament::modal>
                                                        @endif
                                                    </div>

                                                    <!-- Comment Button with Counter -->
                                                    <button @click="showComments = !showComments"
                                                        class="flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full transition-colors"
                                                        :class="{ 
                                                            'bg-amber-50 dark:bg-amber-900 text-amber-600 dark:text-amber-400': showComments, 
                                                            'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-amber-50 dark:hover:bg-amber-900 hover:text-amber-600 dark:hover:text-amber-400': !showComments 
                                                        }">
                                                        <x-heroicon-m-chat-bubble-left-right class="w-4 h-4" />
                                                        <span>{{ $task->comments->count() }}</span>
                                                    </button>

                                                </div>
                                            </div>
                                            <!-- Comments Section -->
                                            <div x-show="showComments"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                                class="border-t border-gray-200 dark:border-gray-700">
                                                <div class="p-3 space-y-3">
                                                    <livewire:project-detail-comments :task="$task"
                                                        :wire:key="'comments-'.$task->id" />
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif


                                <!-- Documents Section -->
                                @if($step->requiredDocuments->isNotEmpty())
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Required Documents
                                    </h4>

                                    @php
                                    $orderByDocs = $step->requiredDocuments->sortBy('status');
                                    @endphp
                                    <div class="space-y-2">
                                        @foreach($orderByDocs as $document)
                                        <!-- Document Item -->
                                        <button
                                            x-on:click="$dispatch('open-modal', { id: 'document-modal-{{ $document->id }}' })"
                                            class="w-full group">
                                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg transition-all duration-200
                                                        {{ match($document->status) {
                                                            'approved' => 'border-2 border-green-200 dark:border-green-800 hover:border-green-300 dark:hover:border-green-700',
                                                            'pending_review' => 'border-2 border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700',
                                                            'rejected' => 'border-2 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700',
                                                            'uploaded' => 'border-2 border-blue-200 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700',
                                                            default => 'border-2 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                        } }}">

                                                <!-- Left: Icon with Status -->
                                                <div class="relative flex-shrink-0">
                                                    <div
                                                        class="{{ match($document->status) {
                                                                'approved' => 'bg-green-50 dark:bg-green-900 text-green-600 dark:text-green-400',
                                                                'pending_review' => 'bg-amber-50 dark:bg-amber-900 text-amber-600 dark:text-amber-400',
                                                                'rejected' => 'bg-red-50 dark:bg-red-900 text-red-600 dark:text-red-400',
                                                                'uploaded' => 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400',
                                                                default => 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                                                            } }} 
                                                            w-10 h-10 rounded-lg flex items-center justify-center transition-colors">
                                                        @if($document->submittedDocuments->count() > 0)
                                                        <x-heroicon-o-document-text class="w-5 h-5" />
                                                        @else
                                                        <x-heroicon-o-document-plus class="w-5 h-5" />
                                                        @endif
                                                    </div>

                                                    <!-- Status Dot -->
                                                    <span class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full ring-2 ring-white dark:ring-gray-800
                                                        {{ match($document->status) {
                                                            'approved' => 'bg-green-500',
                                                            'pending_review' => 'bg-amber-500',
                                                            'rejected' => 'bg-red-500',
                                                            'uploaded' => 'bg-blue-500',
                                                            default => 'bg-gray-400'
                                                        } }}">
                                                    </span>
                                                </div>

                                                <!-- Center: Document Info -->
                                                <div class="flex-1 min-w-0 text-left">
                                                    <p class="text-sm font-medium transition-colors
                                                        {{ match($document->status) {
                                                            'approved' => 'text-green-900 dark:text-green-400',
                                                            'pending_review' => 'text-amber-900 dark:text-amber-400',
                                                            'rejected' => 'text-red-900 dark:text-red-400',
                                                            default => 'text-gray-900 dark:text-gray-300'
                                                        } }}">
                                                        {{ $document->name }}
                                                    </p>
                                                    <div x-data="{ expanded: false }" class="mt-3">
                                                        <div
                                                            class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
                                                            <!-- Truncated version -->
                                                            <template x-if="!expanded">
                                                                <div>
                                                                    {!! Str::limit(strip_tags($document->description),
                                                                    100) !!}
                                                                    @if (strlen(strip_tags($document->description)) >
                                                                    100)
                                                                    <button @click="expanded = true" type="button"
                                                                        class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                                                        <span class="text-sm font-medium">Read
                                                                            more</span>
                                                                        <x-heroicon-m-chevron-down
                                                                            class="w-3 h-3 ml-0.5" />
                                                                    </button>
                                                                    @endif
                                                                </div>
                                                            </template>

                                                            <!-- Full version -->
                                                            <template x-if="expanded">
                                                                <div>
                                                                    {!! str($document->description)->sanitizeHtml() !!}
                                                                    <button @click="expanded = false" type="button"
                                                                        class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 ml-1">
                                                                        <span class="text-sm font-medium">Show
                                                                            less</span>
                                                                        <x-heroicon-m-chevron-up
                                                                            class="w-3 h-3 ml-0.5" />
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right: Status Badge -->
                                                <div class="flex items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                                                    <div
                                                        class="flex items-center gap-2 
                                                        {{ match($document->status) {
                                                            'approved' => 'bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-300',
                                                            'pending_review' => 'bg-amber-50 dark:bg-amber-900 text-amber-700 dark:text-amber-300',
                                                            'rejected' => 'bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-300',
                                                            'uploaded' => 'bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                                                            default => 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
                                                        } }} 
                                                        px-2.5 py-1 rounded-full text-xs font-medium flex-1 sm:flex-initial justify-center">
                                                        <span class="relative flex w-2 h-2">
                                                            <span class="{{ $document->status === 'pending_review' ? 'animate-ping' : '' }} 
                                                                absolute inline-flex h-full w-full rounded-full opacity-75
                                                                {{ match($document->status) {
                                                                    'approved' => 'bg-green-500',
                                                                    'pending_review' => 'bg-amber-500',
                                                                    'rejected' => 'bg-red-500',
                                                                    'uploaded' => 'bg-blue-500',
                                                                    default => 'bg-gray-500'
                                                                } }}">
                                                            </span>
                                                            <span class="relative inline-flex rounded-full h-2 w-2 
                                                                {{ match($document->status) {
                                                                    'approved' => 'bg-green-500',
                                                                    'pending_review' => 'bg-amber-500',
                                                                    'rejected' => 'bg-red-500',
                                                                    'uploaded' => 'bg-blue-500',
                                                                    default => 'bg-gray-500'
                                                                } }}">
                                                            </span>
                                                        </span>
                                                        <span class="ml-1.5">
                                                            {{ ucwords(str_replace('_', ' ', $document->status ?? 'Not
                                                            Submitted')) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>

                                        <!-- Document Modal -->
                                        <x-filament::modal id="document-modal-{{ $document->id }}" width="4xl"
                                            slide-over>
                                            @livewire('project-detail-document-modal', ['document' => $document],
                                            key('document-modal-'.$document->id))
                                        </x-filament::modal>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>


    </div>


    <x-filament::modal id="team-members-modal" width="3xl">
        <div class="min-h-[50vh]">
            @livewire('project-detail-user', ['project' => $record])
        </div>
    </x-filament::modal>

    <x-filament::modal id="all-documents-modal" width="4xl" slide-over>
        @livewire('project-detail.project-detail-all-document', ['project' => $record])
    </x-filament::modal>


    <!-- PIC Modal -->
    <x-filament::modal id="pic-modal" width="2xl">
        @livewire('project-detail.project-pic-manager', ['project' => $record])
    </x-filament::modal>


    {{-- Add this to your project view page --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const documentId = urlParams.get('openDocument');
    
    if (documentId) {
        window.dispatchEvent(
            new CustomEvent('open-modal', {
                detail: { id: `document-modal-${documentId}` }
            })
        );
    }
});
    </script>


</x-filament-panels::page>