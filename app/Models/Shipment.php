<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'master_tracking_id',
        'sender_id',
        'receiver_id',
        'agent_id',
        'driver_id',
        'hub_id',
        'route_id',
        'pickup_address',
        'delivery_address',
        'status',
        'shipment_fee',
        'total_amount',
        'payment_status',
        'pickup_date',
        'delivery_date',
        'special_instructions',
        'is_business_courier',
    ];

    protected $casts = [
        'shipment_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
        'is_business_courier' => 'boolean',
    ];

    // Status constants
    const STATUS_BOOKED = 'booked';
    const STATUS_AWAITING_PICKUP = 'awaiting_pickup';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_RECEIVED_AT_AGENT = 'received_at_agent';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED_AT_HUB = 'arrived_at_hub';
    const STATUS_DISPATCHED_TO_DESTINATION = 'dispatched_to_destination';
    const STATUS_ARRIVED_AT_DESTINATION = 'arrived_at_destination';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_PICKED_UP_BY_RECEIVER = 'picked_up_by_receiver';
    const STATUS_RETURNED = 'returned';

    // Payment status constants
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    // Relationships
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statuses()
    {
        return $this->hasMany(ShipmentStatus::class);
    }

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    // Boot method to generate tracking numbers
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->tracking_number)) {
                $shipment->tracking_number = self::generateTrackingNumber();
            }
            if (empty($shipment->master_tracking_id)) {
                $shipment->master_tracking_id = self::generateMasterTrackingId();
            }
        });
    }

    // Generate unique tracking number
    public static function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'TRK' . date('Y') . strtoupper(Str::random(8));
        } while (self::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    // Generate master tracking ID for multi-package shipments
    public static function generateMasterTrackingId(): string
    {
        do {
            $masterId = 'MT' . date('Y') . strtoupper(Str::random(6));
        } while (self::where('master_tracking_id', $masterId)->exists());

        return $masterId;
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeBusinessCourier($query)
    {
        return $query->where('is_business_courier', true);
    }

    public function scopePersonalCourier($query)
    {
        return $query->where('is_business_courier', false);
    }

    // Helper methods
    public function canBePickedUp(): bool
    {
        return in_array($this->status, [
            self::STATUS_BOOKED,
            self::STATUS_AWAITING_PICKUP
        ]);
    }

    public function canBeDelivered(): bool
    {
        return in_array($this->status, [
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_ARRIVED_AT_DESTINATION
        ]);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_PICKED_UP_BY_RECEIVER
        ]);
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }
} 