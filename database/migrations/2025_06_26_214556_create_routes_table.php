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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('origin_hub_id')->constrained('hubs')->onDelete('cascade');
            $table->foreignId('destination_hub_id')->constrained('hubs')->onDelete('cascade');
            $table->integer('estimated_duration')->comment('in minutes');
            $table->decimal('distance', 8, 2)->comment('in kilometers');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
