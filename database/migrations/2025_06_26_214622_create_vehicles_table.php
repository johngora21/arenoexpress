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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->enum('vehicle_type', ['motorcycle', 'car', 'van', 'truck', 'bicycle']);
            $table->string('plate_number')->unique();
            $table->string('model');
            $table->string('brand');
            $table->year('year')->nullable();
            $table->decimal('capacity', 8, 2)->nullable()->comment('in kg');
            $table->boolean('is_active')->default(true);
            $table->date('insurance_expiry')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
