<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'user_id',
        'payment_type',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'payment_date',
        'gateway_response',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'gateway_response' => 'array',
    ];

    // Payment types
    const TYPE_SHIPMENT_FEE = 'shipment_fee';
    const TYPE_PRODUCT_PAYMENT = 'product_payment';
    const TYPE_RETURN_FEE = 'return_fee';
    const TYPE_INSURANCE = 'insurance';

    // Payment methods
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_MOBILE_MONEY = 'mobile_money';
    const METHOD_BANK_TRANSFER = 'bank_transfer';

    // Payment status
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Boot method to generate transaction ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = self::generateTransactionId();
            }
        });
    }

    // Generate unique transaction ID
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN' . date('Ymd') . strtoupper(Str::random(8));
        } while (self::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function canBeRefunded(): bool
    {
        return $this->isCompleted() && !$this->isRefunded();
    }

    // Mark payment as completed
    public function markAsCompleted($gatewayResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_date' => now(),
            'gateway_response' => $gatewayResponse,
        ]);
    }

    // Mark payment as failed
    public function markAsFailed($gatewayResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    // Refund payment
    public function refund($reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refund_reason' => $reason,
        ]);
    }
} 