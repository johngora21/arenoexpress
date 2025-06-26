<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'event_type',
        'location',
        'description',
        'timestamp',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    // Event types
    const EVENT_BOOKED = 'booked';
    const EVENT_PICKUP_SCHEDULED = 'pickup_scheduled';
    const EVENT_PICKUP_STARTED = 'pickup_started';
    const EVENT_PICKUP_COMPLETED = 'pickup_completed';
    const EVENT_RECEIVED_AT_AGENT = 'received_at_agent';
    const EVENT_IN_TRANSIT = 'in_transit';
    const EVENT_ARRIVED_AT_HUB = 'arrived_at_hub';
    const EVENT_DISPATCHED = 'dispatched';
    const EVENT_ARRIVED_AT_DESTINATION = 'arrived_at_destination';
    const EVENT_OUT_FOR_DELIVERY = 'out_for_delivery';
    const EVENT_DELIVERY_ATTEMPTED = 'delivery_attempted';
    const EVENT_DELIVERED = 'delivered';
    const EVENT_PICKED_UP_BY_RECEIVER = 'picked_up_by_receiver';
    const EVENT_RETURN_INITIATED = 'return_initiated';
    const EVENT_RETURNED = 'returned';
    const EVENT_PAYMENT_RECEIVED = 'payment_received';
    const EVENT_PAYMENT_FAILED = 'payment_failed';

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Boot method to set timestamp
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->timestamp)) {
                $event->timestamp = now();
            }
        });
    }

    // Scopes
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    // Helper methods
    public function isPickupEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_PICKUP_SCHEDULED,
            self::EVENT_PICKUP_STARTED,
            self::EVENT_PICKUP_COMPLETED,
        ]);
    }

    public function isDeliveryEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_OUT_FOR_DELIVERY,
            self::EVENT_DELIVERY_ATTEMPTED,
            self::EVENT_DELIVERED,
            self::EVENT_PICKED_UP_BY_RECEIVER,
        ]);
    }

    public function isPaymentEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_PAYMENT_RECEIVED,
            self::EVENT_PAYMENT_FAILED,
        ]);
    }

    public function isReturnEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_RETURN_INITIATED,
            self::EVENT_RETURNED,
        ]);
    }
} 