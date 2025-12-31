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
        Schema::table("notifications", function (Blueprint $table) {
            $table->enum("type", ["booking_created", "booking_cancelled", "booking_rejected", "booking_approved", "update_request_created", "update_request_updated", "update_request_cancelled", "update_request_approved", "update_request_rejected", "booking_created_by_admin", "booking_updated_by_admin", "booking_deleted_by_admin", "review_created"])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
