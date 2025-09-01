<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Barge extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'capacity',
        'cargo_type',
        'status',
        'description'
    ];

    protected $casts = [
        'capacity' => 'decimal:2'
    ];

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'capacity', 'cargo_type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                $userName = auth()->user()?->name ?? 'System';
                
                return match($eventName) {
                    'created' => "🚤 TONGKANG BARU: {$this->name} ({$this->code}) | Kapasitas: {$this->capacity_display} | Dibuat oleh: {$userName}",
                    'updated' => match($this->status) {
                        'active' => "✅ TONGKANG AKTIF: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'maintenance' => "🔧 TONGKANG MAINTENANCE: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'loading' => "📦 TONGKANG LOADING: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'unloading' => "📤 TONGKANG UNLOADING: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        'inactive' => "⏸️ TONGKANG TIDAK AKTIF: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}",
                        default => "📝 TONGKANG DIPERBARUI: {$this->name} ({$this->code}) | Diperbarui oleh: {$userName}"
                    },
                    'deleted' => "🗑️ TONGKANG DIHAPUS: {$this->name} ({$this->code}) | Dihapus oleh: {$userName}",
                    default => "TONGKANG {$this->name} telah {$eventName} oleh {$userName}"
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

    public function scopeLoading($query)
    {
        return $query->where('status', 'loading');
    }

    public function scopeUnloading($query)
    {
        return $query->where('status', 'unloading');
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

    public function setToLoading(): void
    {
        $this->update(['status' => 'loading']);
    }

    public function setToUnloading(): void
    {
        $this->update(['status' => 'unloading']);
    }

    public function setToInactive(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function completeOperation(): void
    {
        $this->update(['status' => 'active']);
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