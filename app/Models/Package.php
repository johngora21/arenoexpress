<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'sub_tracking_id',
        'qr_code',
        'description',
        'weight',
        'length',
        'width',
        'height',
        'photos',
        'special_instructions',
        'is_fragile',
        'insurance_amount',
        'declared_value',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'photos' => 'array',
        'is_fragile' => 'boolean',
        'insurance_amount' => 'decimal:2',
        'declared_value' => 'decimal:2',
    ];

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Boot method to generate sub-tracking ID and QR code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->sub_tracking_id)) {
                $package->sub_tracking_id = self::generateSubTrackingId($package->shipment_id);
            }
            if (empty($package->qr_code)) {
                $package->qr_code = self::generateQRCode($package->sub_tracking_id);
            }
        });
    }

    // Generate sub-tracking ID (e.g., 54321-A, 54321-B, 54321-C)
    public static function generateSubTrackingId($shipmentId): string
    {
        $shipment = Shipment::find($shipmentId);
        if (!$shipment) {
            throw new \Exception('Shipment not found');
        }

        $existingPackages = self::where('shipment_id', $shipmentId)->count();
        $suffix = chr(65 + $existingPackages); // A, B, C, etc.

        return $shipment->tracking_number . '-' . $suffix;
    }

    // Generate QR code for the package
    public static function generateQRCode($subTrackingId): string
    {
        // In a real implementation, you would use a QR code library
        // For now, we'll create a unique identifier
        return 'QR_' . strtoupper(Str::random(12)) . '_' . $subTrackingId;
    }

    // Calculate volume
    public function getVolumeAttribute(): float
    {
        return $this->length * $this->width * $this->height;
    }

    // Calculate dimensional weight
    public function getDimensionalWeightAttribute(): float
    {
        $volume = $this->volume;
        // Standard dimensional weight factor (varies by carrier)
        $factor = 5000; // cm³ per kg
        return $volume / $factor;
    }

    // Get the higher of actual weight or dimensional weight
    public function getChargeableWeightAttribute(): float
    {
        return max($this->weight, $this->dimensional_weight);
    }

    // Add photo to package
    public function addPhoto($photoPath): void
    {
        $photos = $this->photos ?? [];
        $photos[] = $photoPath;
        $this->update(['photos' => $photos]);
    }

    // Scopes
    public function scopeFragile($query)
    {
        return $query->where('is_fragile', true);
    }

    public function scopeInsured($query)
    {
        return $query->where('insurance_amount', '>', 0);
    }
} 