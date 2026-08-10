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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('event_type_id')->nullable()->after('id')->constrained('event_types')->nullOnDelete();
            $table->uuid('uuid')->nullable()->unique()->after('event_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'event_type_id')) {
                $table->dropForeign(['event_type_id']);
                $table->dropColumn(['event_type_id', 'uuid']);
            }
        });
    }
};
