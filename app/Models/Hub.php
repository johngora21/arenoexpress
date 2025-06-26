<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hub extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'hub_type',
        'is_active',
        'manager_id',
        'contact_phone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Hub types
    const TYPE_REGIONAL = 'regional';
    const TYPE_LOCAL = 'local';

    // Relationships
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function agentStations()
    {
        return $this->hasMany(AgentStation::class);
    }

    public function originRoutes()
    {
        return $this->hasMany(Route::class, 'origin_hub_id');
    }

    public function destinationRoutes()
    {
        return $this->hasMany(Route::class, 'destination_hub_id');
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

    public function scopeRegional($query)
    {
        return $query->where('hub_type', self::TYPE_REGIONAL);
    }

    public function scopeLocal($query)
    {
        return $query->where('hub_type', self::TYPE_LOCAL);
    }
} 