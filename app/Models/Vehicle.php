<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_type',
        'plate_number',
        'model',
        'brand',
        'year',
        'capacity',
        'is_active',
        'insurance_expiry',
        'registration_expiry',
        'last_maintenance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'decimal:2',
        'insurance_expiry' => 'date',
        'registration_expiry' => 'date',
        'last_maintenance' => 'date',
    ];

    // Vehicle types
    const TYPE_MOTORCYCLE = 'motorcycle';
    const TYPE_CAR = 'car';
    const TYPE_VAN = 'van';
    const TYPE_TRUCK = 'truck';
    const TYPE_BICYCLE = 'bicycle';

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry && $this->insurance_expiry->isPast();
    }

    public function isRegistrationExpired(): bool
    {
        return $this->registration_expiry && $this->registration_expiry->isPast();
    }

    public function needsMaintenance(): bool
    {
        if (!$this->last_maintenance) {
            return true;
        }

        // Check if maintenance is due (e.g., every 6 months)
        return $this->last_maintenance->addMonths(6)->isPast();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('insurance_expiry')
                          ->orWhere('insurance_expiry', '>', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('registration_expiry')
                          ->orWhere('registration_expiry', '>', now());
                    });
    }
} 