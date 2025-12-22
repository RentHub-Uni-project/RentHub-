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
        Schema::table('apartments', function (Blueprint $table) {
            $table->enum('governorate', [
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
            ])->change();
            $table->text('city')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
