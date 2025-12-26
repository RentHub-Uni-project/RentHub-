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
        Schema::table('booking_update_request', function (Blueprint $table) {
            $table->renameColumn("request_number_of_guests", "requested_number_of_guests");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_update_request', function (Blueprint $table) {
            //
        });
    }
};
