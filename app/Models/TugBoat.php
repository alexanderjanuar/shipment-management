<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TugBoat extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'power',
        'status',
        'description'
    ];

    protected $casts = [
        'power' => 'decimal:2'
    ];

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'power', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                $userName = auth()->user()?->name ?? 'System';
                
                return match($eventName) {
                    'created' => "🚢 KAPAL TUNDA BARU: {$this->name} ({$this->code}) | Dibuat oleh: {$userName}",
                    'updated' => match($this->status) {
                        'active' => "✅ KAPAL TUNDA AKTIF: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'maintenance' => "🔧 KAPAL TUNDA MAINTENANCE: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'inactive' => "⏸️ KAPAL TUNDA TIDAK AKTIF: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        default => "📝 KAPAL TUNDA DIPERBARUI: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}"
                    },
                    'deleted' => "🗑️ KAPAL TUNDA DIHAPUS: {$this->name} ({$this->code}) | Dihapus oleh: {$userName}",
                    default => "KAPAL TUNDA {$this->name} telah {$eventName} oleh {$userName}"
                };
            });
    }

    /**
     * Relationships
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                    ->whereDoesntHave('projects', function ($q) {
                        $q->whereIn('status', ['draft', 'analysis', 'in_progress', 'review']);
                    });
    }

    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Accessors
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }

    public function getIsAvailableAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return !$this->projects()
            ->whereIn('status', ['draft', 'analysis', 'in_progress', 'review'])
            ->exists();
    }

    /**
     * Helper Methods
     */
    public function setToMaintenance(): void
    {
        $this->update(['status' => 'maintenance']);
    }

    public function setToActive(): void
    {
        $this->update(['status' => 'active']);
    }

    public function setToInactive(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function getTotalProjectsCount(): int
    {
        return $this->projects()->count();
    }

    public function getActiveProjectsCount(): int
    {
        return $this->projects()
            ->whereIn('status', ['draft', 'analysis', 'in_progress', 'review'])
            ->count();
    }
}