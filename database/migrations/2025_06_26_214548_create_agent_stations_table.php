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
        Schema::create('agent_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('operating_hours')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_stations');
    }
};
