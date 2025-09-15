<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'api_base_url',
        'api_config',
        'supported_features',
        'is_active',
        'requires_oauth',
        'oauth_url',
        'oauth_scopes',
        'sync_interval_minutes',
        'auto_sync_enabled',
    ];

    protected $casts = [
        'api_config' => 'array',
        'supported_features' => 'array',
        'oauth_scopes' => 'array',
        'is_active' => 'boolean',
        'requires_oauth' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'sync_interval_minutes' => 'integer',
    ];

    // Relationships
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_channels')
                    ->withPivot([
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
                        'sync_attempts'
                    ])
                    ->withTimestamps();
    }

    public function propertyChannels(): HasMany
    {
        return $this->hasMany(PropertyChannel::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'channel_id', 'channel_id');
    }

    // Accessors
    public function getLogoUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        // URLs padrão para canais conhecidos
        $defaultLogos = [
            'AIR298' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/airbnb.svg',
            'BOO142' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/bookingdotcom.svg',
            'HOM143' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/homeaway.svg',
            'EXP001' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/expedia.svg',
        ];

        return $defaultLogos[$this->channel_id] ?? null;
    }

    public function getWebsiteUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        // URLs padrão para canais conhecidos
        $defaultWebsites = [
            'AIR298' => 'https://www.airbnb.com',
            'BOO142' => 'https://www.booking.com',
            'HOM143' => 'https://www.homeaway.com',
            'EXP001' => 'https://www.expedia.com',
        ];

        return $defaultWebsites[$this->channel_id] ?? null;
    }

    public function getStatusBadgeAttribute(): string
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
        
        $class = $statusClasses[$this->is_active ? 'active' : 'inactive'] ?? 'secondary';
        $text = $statusTexts[$this->is_active ? 'active' : 'inactive'] ?? 'Desconhecido';
        
        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresOauth($query)
    {
        return $query->where('requires_oauth', true);
    }

    public function scopeByChannelId($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    // Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function requiresOauth(): bool
    {
        return $this->requires_oauth;
    }

    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->supported_features ?? []);
    }

    public function getApiConfig(?string $key = null)
    {
        if ($key) {
            return $this->api_config[$key] ?? null;
        }
        
        return $this->api_config;
    }

    public function getOauthScopes(): array
    {
        return $this->oauth_scopes ?? [];
    }

    public function getSyncIntervalMinutes(): int
    {
        return $this->sync_interval_minutes ?? 60;
    }

    public function isAutoSyncEnabled(): bool
    {
        return $this->auto_sync_enabled;
    }

    // Static methods
    public static function getChannelById(string $channelId): ?self
    {
        return static::where('channel_id', $channelId)->first();
    }

    public static function getActiveChannels()
    {
        return static::active()->get();
    }

    public static function getOauthChannels()
    {
        return static::requiresOauth()->active()->get();
    }
}