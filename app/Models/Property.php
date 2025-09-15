<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'property_id',
        'channel_type',
        'channel_property_id',
        'property_manager_code',
        'supplier_property_id',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'email',
        'total_rooms',
        'is_active',
        'channel_config',
        // New fields
        'main_image',
        'gallery_images',
        'description',
        'property_type',
        'max_occupancy',
        'max_adults',
        'max_children',
        'bedrooms',
        'bathrooms',
        'postal_code',
        'latitude',
        'longitude',
        'amenities',
        'house_rules',
        'base_price',
        'currency',
        'nightly_rate',
        'weekly_rate',
        'monthly_rate',
        'cleaning_fee',
        'security_deposit',
        'pricing_rules',
        'contact_name',
        'contact_phone',
        'contact_email',
        'check_in_from',
        'check_in_until',
        'check_out_from',
        'check_out_until',
        'status',
        'verified_at',
        'verification_notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'channel_config' => 'array',
        'total_rooms' => 'integer',
        'gallery_images' => 'array',
        'amenities' => 'array',
        'house_rules' => 'array',
        'pricing_rules' => 'array',
        'max_occupancy' => 'integer',
        'max_adults' => 'integer',
        'max_children' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'base_price' => 'decimal:2',
        'nightly_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'check_in_from' => 'string',
        'check_in_until' => 'string',
        'check_out_from' => 'string',
        'check_out_until' => 'string',
        'verified_at' => 'datetime',
    ];

    protected $dates = [
        'verified_at',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'property_id', 'property_id');
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'property_channels')
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

    public function mainImage()
    {
        return $this->hasOne(PropertyImage::class)->where('type', 'main');
    }

    public function galleryImages()
    {
        return $this->hasMany(PropertyImage::class)->where('type', 'gallery')->orderBy('sort_order');
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->address, $this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }

    public function getMainImageUrlAttribute(): ?string
    {
        if ($this->main_image) {
            return Storage::url($this->main_image);
        }
        
        $mainImage = $this->mainImage;
        if ($mainImage) {
            return Storage::url($mainImage->image_path);
        }
        
        return null;
    }

    public function getGalleryImagesUrlsAttribute(): array
    {
        $urls = [];
        
        if ($this->gallery_images) {
            foreach ($this->gallery_images as $image) {
                if (Storage::exists($image)) {
                    $urls[] = Storage::url($image);
                }
            }
        }
        
        // Also check the PropertyImage relationship
        foreach ($this->galleryImages as $image) {
            if (Storage::exists($image->image_path)) {
                $urls[] = Storage::url($image->image_path);
            }
        }
        
        return array_unique($urls);
    }

    public function getStatusBadgeAttribute(): string
    {
        $statusClasses = [
            'draft' => 'secondary',
            'pending' => 'warning',
            'active' => 'success',
            'inactive' => 'danger',
            'suspended' => 'dark'
        ];
        
        $statusTexts = [
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'suspended' => 'Suspenso'
        ];
        
        $class = $statusClasses[$this->status] ?? 'secondary';
        $text = $statusTexts[$this->status] ?? ucfirst($this->status);
        
        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    public function getPropertyTypeTextAttribute(): string
    {
        $types = [
            'apartment' => 'Apartamento',
            'house' => 'Casa',
            'hotel' => 'Hotel',
            'hostel' => 'Hostel',
            'resort' => 'Resort',
            'villa' => 'Vila',
            'cabin' => 'Cabana',
            'loft' => 'Loft'
        ];
        
        return $types[$this->property_type] ?? ucfirst($this->property_type);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('property_type', $type);
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function canBeBooked(): bool
    {
        return $this->isActive() && $this->isVerified();
    }

    public function getFormattedPriceAttribute(): string
    {
        if (!$this->base_price) {
            return 'Preço sob consulta';
        }
        
        return 'R$ ' . number_format($this->base_price, 2, ',', '.');
    }

    public function getCoordinatesAttribute(): array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude
            ];
        }
        
        return [];
    }

    public function hasCoordinates(): bool
    {
        return !empty($this->coordinates);
    }

    // Channel-related methods
    public function isConnectedToChannel(string $channelId): bool
    {
        return $this->channels()->where('channels.channel_id', $channelId)->exists();
    }

    public function getChannelConnection(string $channelId): ?PropertyChannel
    {
        return $this->propertyChannels()
            ->whereHas('channel', function($query) use ($channelId) {
                $query->where('channel_id', $channelId);
            })
            ->first();
    }

    public function getActiveChannels()
    {
        return $this->channels()->wherePivot('is_active', true)->get();
    }

    public function getChannelStatus(string $channelId): ?string
    {
        $connection = $this->getChannelConnection($channelId);
        return $connection ? $connection->channel_status : null;
    }

    public function getChannelPropertyId(string $channelId): ?string
    {
        $connection = $this->getChannelConnection($channelId);
        return $connection ? $connection->channel_property_id : null;
    }

    public function canSyncWithChannel(string $channelId): bool
    {
        $connection = $this->getChannelConnection($channelId);
        return $connection ? $connection->canSync() : false;
    }

    public function getConnectedChannelsCount(): int
    {
        return $this->channels()->count();
    }

    public function getActiveChannelsCount(): int
    {
        return $this->channels()->wherePivot('is_active', true)->count();
    }

    public function hasAnyActiveChannel(): bool
    {
        return $this->getActiveChannelsCount() > 0;
    }

    public function getChannelUrls(): array
    {
        $urls = [];
        foreach ($this->propertyChannels as $propertyChannel) {
            if ($propertyChannel->channel_property_url) {
                $urls[$propertyChannel->channel->name] = $propertyChannel->channel_property_url;
            }
        }
        return $urls;
    }

    /**
     * Boot method para sincronização automática com canais
     */
    protected static function boot()
    {
        parent::boot();

        // Quando uma propriedade é criada, conectá-la automaticamente aos canais ativos
        static::created(function ($property) {
            if ($property->status === 'active') {
                $property->syncWithActiveChannels();
            }
        });

        // Quando uma propriedade é atualizada para ativa, conectá-la aos canais
        static::updated(function ($property) {
            if ($property->isDirty('status') && $property->status === 'active') {
                $property->syncWithActiveChannels();
            }
        });
    }

    /**
     * Sincronizar propriedade com canais ativos
     */
    public function syncWithActiveChannels()
    {
        $activeChannels = Channel::where('is_active', true)->get();
        
        foreach ($activeChannels as $channel) {
            // Verificar se já existe conexão
            $existingConnection = PropertyChannel::where('property_id', $this->id)
                ->where('channel_id', $channel->id)
                ->first();

            if (!$existingConnection) {
                // Criar conexão automática
                $connectionData = $this->createChannelConnectionData($channel);
                
                PropertyChannel::create([
                    'property_id' => $this->id,
                    'channel_id' => $channel->id,
                    'is_active' => true,
                    'auto_sync_enabled' => true,
                    'channel_status' => 'active',
                    'content_status' => 'enabled',
                    'channel_property_id' => $connectionData['channel_property_id'],
                    'channel_config' => $connectionData['channel_config'],
                    'last_sync_at' => now(),
                ]);
            }
        }
    }

    /**
     * Criar dados de conexão para um canal específico
     */
    private function createChannelConnectionData(Channel $channel): array
    {
        $baseData = [
            'channel_property_id' => strtolower($channel->slug) . '-' . $this->id,
            'channel_config' => [
                'property_id' => strtolower($channel->slug) . '-' . $this->id,
                'sync_enabled' => true,
                'auto_created' => true,
            ],
        ];

        // Dados específicos por canal
        switch ($channel->channel_id) {
            case 'AIR298': // Airbnb
                return [
                    'channel_property_id' => 'airbnb-' . $this->id,
                    'channel_config' => [
                        'listing_id' => 'airbnb-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            case 'BOO142': // Booking.com
                return [
                    'channel_property_id' => 'booking-' . $this->id,
                    'channel_config' => [
                        'property_id' => 'booking-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            case 'HOM143': // HomeAway
                return [
                    'channel_property_id' => 'homeaway-' . $this->id,
                    'channel_config' => [
                        'property_id' => 'homeaway-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            case 'EXP001': // Expedia
                return [
                    'channel_property_id' => 'expedia-' . $this->id,
                    'channel_config' => [
                        'property_id' => 'expedia-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            case 'VRB001': // VRBO
                return [
                    'channel_property_id' => 'vrbo-' . $this->id,
                    'channel_config' => [
                        'property_id' => 'vrbo-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            case 'DIRECT': // Reserva Direta
                return [
                    'channel_property_id' => 'direct-' . $this->id,
                    'channel_config' => [
                        'property_id' => 'direct-' . $this->id,
                        'sync_enabled' => true,
                        'auto_created' => true,
                    ],
                ];

            default:
                return $baseData;
        }
    }
}
