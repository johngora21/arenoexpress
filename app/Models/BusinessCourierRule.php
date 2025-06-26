<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCourierRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'is_registered_seller',
        'return_fee_policy',
        'buyer_prepayment_required',
        'return_fee_amount',
        'prepayment_percentage',
        'standby_call_required',
        'auto_refund_on_return',
        'custom_rules',
    ];

    protected $casts = [
        'is_registered_seller' => 'boolean',
        'buyer_prepayment_required' => 'boolean',
        'return_fee_amount' => 'decimal:2',
        'prepayment_percentage' => 'decimal:2',
        'standby_call_required' => 'boolean',
        'auto_refund_on_return' => 'boolean',
        'custom_rules' => 'array',
    ];

    // Return fee policies
    const RETURN_FEE_PREPAID = 'prepaid';
    const RETURN_FEE_POST_BILLED = 'post_billed';

    // Relationships
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Helper methods
    public function requiresReturnFeePrepayment(): bool
    {
        return $this->return_fee_policy === self::RETURN_FEE_PREPAID;
    }

    public function requiresBuyerPrepayment(): bool
    {
        return $this->buyer_prepayment_required;
    }

    public function isRegisteredSeller(): bool
    {
        return $this->is_registered_seller;
    }

    public function requiresStandbyCall(): bool
    {
        return $this->standby_call_required;
    }

    public function shouldAutoRefundOnReturn(): bool
    {
        return $this->auto_refund_on_return;
    }

    // Calculate return fee based on policy
    public function getReturnFeeAmount($shipmentAmount = 0): float
    {
        if ($this->return_fee_amount > 0) {
            return $this->return_fee_amount;
        }

        // Default return fee calculation (can be customized)
        return $shipmentAmount * 0.15; // 15% of shipment amount
    }

    // Calculate prepayment amount
    public function getPrepaymentAmount($totalAmount): float
    {
        if (!$this->requiresBuyerPrepayment()) {
            return 0;
        }

        $percentage = $this->prepayment_percentage ?: 100; // Default to 100%
        return ($totalAmount * $percentage) / 100;
    }

    // Scopes
    public function scopeRegisteredSellers($query)
    {
        return $query->where('is_registered_seller', true);
    }

    public function scopeUnregisteredSellers($query)
    {
        return $query->where('is_registered_seller', false);
    }

    public function scopePrepaidReturnFee($query)
    {
        return $query->where('return_fee_policy', self::RETURN_FEE_PREPAID);
    }

    public function scopePostBilledReturnFee($query)
    {
        return $query->where('return_fee_policy', self::RETURN_FEE_POST_BILLED);
    }
} 