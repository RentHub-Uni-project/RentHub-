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

        Schema::create('appartment_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appartment_id');
            $table->foreign('appartment_id')->references('id')->on('appartments')->onDelete('cascade');
            $table->text('image_url');
            $table->boolean('is_main')->default(false);
            $table->integer('display_order')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appartment_images');
    }
};
