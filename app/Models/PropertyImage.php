<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'image_path',
        'image_name',
        'alt_text',
        'type',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Accessors
    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $pathInfo = pathinfo($this->image_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        
        if (Storage::exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }
        
        return $this->image_url;
    }

    public function getImageSizeAttribute(): string
    {
        if (Storage::exists($this->image_path)) {
            $size = Storage::size($this->image_path);
            return $this->formatBytes($size);
        }
        
        return 'N/A';
    }

    public function getImageDimensionsAttribute(): string
    {
        if (Storage::exists($this->image_path)) {
            $path = Storage::path($this->image_path);
            if (function_exists('getimagesize')) {
                $dimensions = getimagesize($path);
                if ($dimensions) {
                    return $dimensions[0] . 'x' . $dimensions[1];
                }
            }
        }
        
        return 'N/A';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    public function scopeGallery($query)
    {
        return $query->where('type', 'gallery');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Methods
    public function isMain(): bool
    {
        return $this->type === 'main';
    }

    public function isGallery(): bool
    {
        return $this->type === 'gallery';
    }

    public function deleteImage(): bool
    {
        // Delete the actual file
        if (Storage::exists($this->image_path)) {
            Storage::delete($this->image_path);
        }
        
        // Delete thumbnail if exists
        $pathInfo = pathinfo($this->image_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        if (Storage::exists($thumbnailPath)) {
            Storage::delete($thumbnailPath);
        }
        
        // Delete the database record
        return $this->delete();
    }

    public function moveToPosition(int $newPosition): bool
    {
        $oldPosition = $this->sort_order;
        
        if ($oldPosition === $newPosition) {
            return true;
        }
        
        if ($oldPosition < $newPosition) {
            // Moving down - shift others up
            PropertyImage::where('property_id', $this->property_id)
                ->where('type', $this->type)
                ->where('sort_order', '>', $oldPosition)
                ->where('sort_order', '<=', $newPosition)
                ->decrement('sort_order');
        } else {
            // Moving up - shift others down
            PropertyImage::where('property_id', $this->property_id)
                ->where('type', $this->type)
                ->where('sort_order', '>=', $newPosition)
                ->where('sort_order', '<', $oldPosition)
                ->increment('sort_order');
        }
        
        $this->sort_order = $newPosition;
        return $this->save();
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 