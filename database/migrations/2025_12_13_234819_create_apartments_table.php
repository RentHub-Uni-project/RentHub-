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

        Schema::create('appartments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('price_per_month', 10, 2);
            $table->integer('max_guests')->nullable();
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->text('address');
            $table->text('governorate');
            $table->enum('city', [
                "Damascus",
                "Rif_Dimashq",
                "Aleppo",
                "Homs",
                "Hama",
                "Latakia",
                "Tartus",
                "Idlib",
                "Deir ez-Zor",
                "Raqqa",
                "Hasakah",
                "Daraa",
                "As-Suwayda",
                "Quneitra"
            ]);
            $table->decimal('latitude', 10, 10)->nullable();
            $table->decimal('longitude', 10, 10)->nullable();
            $table->boolean('is_available')->default(true);
            $table->enum('status', ["approved", "rejected", "pending"])->default("pending");

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appartments');
    }
};
