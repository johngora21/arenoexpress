<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'shipment_id',
        'vehicle_id',
        'assignment_type',
        'status',
        'assigned_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'notes',
        'location',
        'estimated_duration',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration' => 'integer', // in minutes
    ];

    // Assignment types
    const TYPE_PICKUP = 'pickup';
    const TYPE_DELIVERY = 'delivery';

    // Assignment status
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Helper methods
    public function isPickup(): bool
    {
        return $this->assignment_type === self::TYPE_PICKUP;
    }

    public function isDelivery(): bool
    {
        return $this->assignment_type === self::TYPE_DELIVERY;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending();
    }

    public function canBeStarted(): bool
    {
        return $this->isAccepted();
    }

    public function canBeCompleted(): bool
    {
        return $this->isInProgress();
    }

    // Action methods
    public function accept(): void
    {
        if ($this->canBeAccepted()) {
            $this->update([
                'status' => self::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);
        }
    }

    public function start(): void
    {
        if ($this->canBeStarted()) {
            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        }
    }

    public function complete(): void
    {
        if ($this->canBeCompleted()) {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    }

    public function cancel($reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
    }

    public function fail($reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePickup($query)
    {
        return $query->where('assignment_type', self::TYPE_PICKUP);
    }

    public function scopeDelivery($query)
    {
        return $query->where('assignment_type', self::TYPE_DELIVERY);
    }
} 