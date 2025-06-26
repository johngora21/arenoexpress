<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shipment_id',
        'type',
        'title',
        'message',
        'is_read',
        'sent_at',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Notification types
    const TYPE_SHIPMENT_BOOKED = 'shipment_booked';
    const TYPE_PICKUP_SCHEDULED = 'pickup_scheduled';
    const TYPE_PICKUP_STARTED = 'pickup_started';
    const TYPE_PICKUP_COMPLETED = 'pickup_completed';
    const TYPE_IN_TRANSIT = 'in_transit';
    const TYPE_ARRIVED_AT_HUB = 'arrived_at_hub';
    const TYPE_OUT_FOR_DELIVERY = 'out_for_delivery';
    const TYPE_DELIVERED = 'delivered';
    const TYPE_PICKED_UP = 'picked_up';
    const TYPE_RETURNED = 'returned';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_PAYMENT_FAILED = 'payment_failed';
    const TYPE_DRIVER_ASSIGNED = 'driver_assigned';
    const TYPE_AGENT_ALERT = 'agent_alert';
    const TYPE_SYSTEM_ALERT = 'system_alert';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Boot method to set sent_at
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->sent_at)) {
                $notification->sent_at = now();
            }
        });
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    // Static methods for creating notifications
    public static function createShipmentBooked($userId, $shipmentId, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'shipment_id' => $shipmentId,
            'type' => self::TYPE_SHIPMENT_BOOKED,
            'title' => 'Shipment Booked',
            'message' => 'Your shipment has been successfully booked.',
            'metadata' => $metadata,
        ]);
    }

    public static function createPickupScheduled($userId, $shipmentId, $pickupTime, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'shipment_id' => $shipmentId,
            'type' => self::TYPE_PICKUP_SCHEDULED,
            'title' => 'Pickup Scheduled',
            'message' => "Your pickup has been scheduled for {$pickupTime}.",
            'metadata' => array_merge($metadata, ['pickup_time' => $pickupTime]),
        ]);
    }

    public static function createOutForDelivery($userId, $shipmentId, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'shipment_id' => $shipmentId,
            'type' => self::TYPE_OUT_FOR_DELIVERY,
            'title' => 'Out for Delivery',
            'message' => 'Your package is out for delivery.',
            'metadata' => $metadata,
        ]);
    }

    public static function createDelivered($userId, $shipmentId, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'shipment_id' => $shipmentId,
            'type' => self::TYPE_DELIVERED,
            'title' => 'Package Delivered',
            'message' => 'Your package has been successfully delivered.',
            'metadata' => $metadata,
        ]);
    }

    public static function createPaymentReceived($userId, $shipmentId, $amount, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'shipment_id' => $shipmentId,
            'type' => self::TYPE_PAYMENT_RECEIVED,
            'title' => 'Payment Received',
            'message' => "Payment of {$amount} has been received.",
            'metadata' => array_merge($metadata, ['amount' => $amount]),
        ]);
    }

    public static function createSystemAlert($userId, $title, $message, $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => self::TYPE_SYSTEM_ALERT,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
} 