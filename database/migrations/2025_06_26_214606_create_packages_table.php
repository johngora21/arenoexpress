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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('sub_tracking_id')->unique();
            $table->string('qr_code')->unique();
            $table->text('description');
            $table->decimal('weight', 8, 2)->comment('in kg');
            $table->decimal('length', 8, 2)->nullable()->comment('in cm');
            $table->decimal('width', 8, 2)->nullable()->comment('in cm');
            $table->decimal('height', 8, 2)->nullable()->comment('in cm');
            $table->json('photos')->nullable();
            $table->text('special_instructions')->nullable();
            $table->boolean('is_fragile')->default(false);
            $table->decimal('insurance_amount', 10, 2)->default(0);
            $table->decimal('declared_value', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
