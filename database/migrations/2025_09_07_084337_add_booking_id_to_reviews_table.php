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
    Schema::table('reviews', function (Blueprint $table) {
        $table->unsignedBigInteger('booking_id')->after('hall_id')->nullable();

        $table->foreign('booking_id')
              ->references('id')
              ->on('bookings')
              ->onDelete('cascade');
    });
    }

public function down(): void
    {
    Schema::table('reviews', function (Blueprint $table) {
        $table->dropForeign(['booking_id']);
        $table->dropColumn('booking_id');
    }); 
    }
};
