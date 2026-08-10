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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the user who was booked
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_timezone');
            $table->dateTime('start_time'); // Stored in UTC
            $table->dateTime('end_time'); // Stored in UTC
            $table->string('meet_link')->nullable();
            $table->string('status')->default('confirmed'); // confirmed, cancelled
            $table->json('lead_data')->nullable(); // Custom screening questions
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
