<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'origin_hub_id',
        'destination_hub_id',
        'estimated_duration',
        'distance',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_duration' => 'integer', // in minutes
        'distance' => 'decimal:2', // in kilometers
    ];

    // Relationships
    public function originHub()
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function destinationHub()
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
} 