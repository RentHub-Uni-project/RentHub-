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
        Schema::disableForeignKeyConstraints();

        Schema::create('booking_update_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->date('requested_start_date')->nullable();
            $table->date('requested_end_date')->nullable();
            $table->text('requested_tenant_notes')->nullable();
            $table->enum('status', ["pending","approved","rejected"])->default("pending");
            $table->integer('request_number_of_guests')->nullable();
            $table->timestamps(); 
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_update_request');
    }
};
