<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'nextpax_booking_id',
        'booking_number',
        'channel_partner_reference',
        'channel_id',
        'property_id',
        'supplier_property_id',
        'property_manager_code',
        'guest_first_name',
        'guest_surname',
        'guest_email',
        'guest_phone',
        'guest_country_code',
        'guest_language',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'babies',
        'pets',
        'total_amount',
        'currency',
        'payment_type',
        'room_type',
        'rate_plan_id',
        'status',
        'remarks',
        'api_response',
        'api_payload',
        'sync_status',
        'sync_error',
        'last_sync_attempt',
        'synced_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'babies' => 'integer',
        'pets' => 'integer',
        'total_amount' => 'decimal:2',
        'api_response' => 'array',
        'api_payload' => 'array',
        'last_sync_attempt' => 'datetime',
        'synced_at' => 'datetime',
    ];

    protected $dates = [
        'verified_at',
        'last_sync_attempt',
        'synced_at',
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }

    // Accessors
    public function getGuestFullNameAttribute(): string
    {
        return trim($this->guest_first_name . ' ' . $this->guest_surname);
    }

    public function getTotalOccupancyAttribute(): int
    {
        return $this->adults + $this->children + $this->babies;
    }

    public function getFormattedAmountAttribute(): string
    {
        if (!$this->total_amount) {
            return 'Preço sob consulta';
        }
        
        return $this->currency . ' ' . number_format($this->total_amount, 2, ',', '.');
    }

    public function getStatusBadgeAttribute(): string
    {
        $statusClasses = [
            'pending' => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'danger',
            'failed' => 'dark',
            'request' => 'info',
            'request-accepted' => 'success',
            'request-rejected' => 'danger',
            'request-booked' => 'primary',
        ];
        
        $statusTexts = [
            'pending' => 'Pendente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'failed' => 'Falhou',
            'request' => 'Solicitação',
            'request-accepted' => 'Aceita',
            'request-rejected' => 'Rejeitada',
            'request-booked' => 'Reservada',
        ];
        
        $class = $statusClasses[$this->status] ?? 'secondary';
        $text = $statusTexts[$this->status] ?? ucfirst($this->status);
        
        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPropertyManager($query, $propertyManagerCode)
    {
        return $query->where('property_manager_code', $propertyManagerCode);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in_date', [$startDate, $endDate])
                    ->orWhereBetween('check_out_date', [$startDate, $endDate]);
    }

    // Methods
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'request-booked']);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'request', 'request-accepted']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
