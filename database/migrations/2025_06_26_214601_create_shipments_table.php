<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->string('master_tracking_id')->unique();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('hub_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('route_id')->nullable()->constrained()->onDelete('set null');
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->enum('status', [
                'booked', 'awaiting_pickup', 'picked_up', 'received_at_agent',
                'in_transit', 'arrived_at_hub', 'dispatched_to_destination',
                'arrived_at_destination', 'out_for_delivery', 'delivered',
                'picked_up_by_receiver', 'returned'
            ])->default('booked');
            $table->decimal('shipment_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('pickup_date')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->text('special_instructions')->nullable();
            $table->boolean('is_business_courier')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
