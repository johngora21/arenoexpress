<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'hub_id',
        'name',
        'address',
        'city',
        'state',
        'agent_id',
        'contact_phone',
        'is_active',
        'operating_hours',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'operating_hours' => 'array',
    ];

    // Relationships
    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
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