<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'channel_id',
        'channel_property_id',
        'channel_room_id',
        'channel_status',
        'content_status',
        'property_status_note',
        'channel_property_url',
        'channel_config',
        'sync_settings',
        'is_active',
        'auto_sync_enabled',
        'last_sync_at',
        'last_successful_sync_at',
        'last_sync_error',
        'sync_attempts',
    ];

    protected $casts = [
        'channel_config' => 'array',
        'sync_settings' => 'array',
        'is_active' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
        'sync_attempts' => 'integer',
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    // Accessors
    public function getChannelStatusBadgeAttribute(): string
    {
        $statusClasses = [
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'warning',
            'error' => 'danger'
        ];
        
        $statusTexts = [
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'suspended' => 'Suspenso',
            'error' => 'Erro'
        ];
        
        $class = $statusClasses[$this->channel_status] ?? 'secondary';
        $text = $statusTexts[$this->channel_status] ?? ucfirst($this->channel_status);
        
        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    public function getContentStatusBadgeAttribute(): string
    {
        $statusClasses = [
            'enabled' => 'success',
            'disabled' => 'secondary',
            'pending' => 'warning',
            'error' => 'danger'
        ];
        
        $statusTexts = [
            'enabled' => 'Habilitado',
            'disabled' => 'Desabilitado',
            'pending' => 'Pendente',
            'error' => 'Erro'
        ];
        
        $class = $statusClasses[$this->content_status] ?? 'secondary';
        $text = $statusTexts[$this->content_status] ?? ucfirst($this->content_status);
        
        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    public function getSyncStatusBadgeAttribute(): string
    {
        if ($this->last_sync_error) {
            return "<span class='badge bg-danger'>Erro</span>";
        }
        
        if ($this->last_successful_sync_at) {
            $lastSync = $this->last_successful_sync_at->diffForHumans();
            return "<span class='badge bg-success'>Sincronizado ({$lastSync})</span>";
        }
        
        return "<span class='badge bg-secondary'>Nunca sincronizado</span>";
    }

    public function getChannelConfig(?string $key = null)
    {
        if ($key) {
            return $this->channel_config[$key] ?? null;
        }
        
        return $this->channel_config;
    }

    public function getSyncSettings(?string $key = null)
    {
        if ($key) {
            return $this->sync_settings[$key] ?? null;
        }
        
        return $this->sync_settings;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('channel_status', $status);
    }

    public function scopeNeedsSync($query)
    {
        return $query->where('auto_sync_enabled', true)
                    ->where(function($q) {
                        $q->whereNull('last_sync_at')
                          ->orWhere('last_sync_at', '<', now()->subMinutes(60));
                    });
    }

    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('last_sync_error');
    }

    // Methods
    public function isActive(): bool
    {
        return $this->is_active && $this->channel_status === 'active';
    }

    public function isContentEnabled(): bool
    {
        return $this->content_status === 'enabled';
    }

    public function needsSync(): bool
    {
        if (!$this->auto_sync_enabled) {
            return false;
        }

        if (!$this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->lt(now()->subMinutes($this->channel->getSyncIntervalMinutes()));
    }

    public function hasSyncError(): bool
    {
        return !is_null($this->last_sync_error);
    }

    public function getLastSyncError(): ?string
    {
        return $this->last_sync_error;
    }

    public function markSyncSuccess(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_successful_sync_at' => now(),
            'last_sync_error' => null,
            'sync_attempts' => 0,
        ]);
    }

    public function markSyncError(string $error): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_sync_error' => $error,
            'sync_attempts' => $this->sync_attempts + 1,
        ]);
    }

    public function getChannelPropertyUrl(): ?string
    {
        return $this->channel_property_url;
    }

    public function getChannelPropertyId(): ?string
    {
        return $this->channel_property_id;
    }

    public function getChannelRoomId(): ?string
    {
        return $this->channel_room_id;
    }

    public function canSync(): bool
    {
        return $this->isActive() && 
               $this->channel_property_id && 
               $this->auto_sync_enabled;
    }

    public function getSyncIntervalMinutes(): int
    {
        return $this->channel->getSyncIntervalMinutes();
    }
}