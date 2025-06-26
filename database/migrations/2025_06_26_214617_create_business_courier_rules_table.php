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
        Schema::create('business_courier_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_registered_seller')->default(false);
            $table->enum('return_fee_policy', ['prepaid', 'post_billed'])->default('post_billed');
            $table->boolean('buyer_prepayment_required')->default(true);
            $table->decimal('return_fee_amount', 10, 2)->default(0);
            $table->decimal('prepayment_percentage', 5, 2)->default(100);
            $table->boolean('standby_call_required')->default(false);
            $table->boolean('auto_refund_on_return')->default(true);
            $table->json('custom_rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_courier_rules');
    }
};
