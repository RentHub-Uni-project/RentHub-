<?php

use App\Enums\BookingUpdateRequestStatus;
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
        Schema::table("booking_update_request", function (Blueprint $table) {
            $table->enum("status", BookingUpdateRequestStatus::cases())->default(BookingUpdateRequestStatus::PENDING)->change();
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
