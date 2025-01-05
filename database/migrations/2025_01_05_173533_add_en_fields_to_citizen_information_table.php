<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnFieldsToCitizenInformationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('citizen_information', function (Blueprint $table) {
            // Add present address fields in English
            $table->string('presentHolding_en')->nullable();
            $table->string('presentVillage_en')->nullable();
            $table->string('presentUnion_en')->nullable();
            $table->string('presentPost_en')->nullable();
            $table->string('presentPostCode_en')->nullable();
            $table->string('presentThana_en')->nullable();
            $table->string('presentDistrict_en')->nullable();

            // Add permanent address fields in English
            $table->string('permanentHolding_en')->nullable();
            $table->string('permanentVillage_en')->nullable();
            $table->string('permanentUnion_en')->nullable();
            $table->string('permanentPost_en')->nullable();
            $table->string('permanentPostCode_en')->nullable();
            $table->string('permanentThana_en')->nullable();
            $table->string('permanentDistrict_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizen_information', function (Blueprint $table) {
            // Drop present address fields in English
            $table->dropColumn('presentHolding_en');
            $table->dropColumn('presentVillage_en');
            $table->dropColumn('presentUnion_en');
            $table->dropColumn('presentPost_en');
            $table->dropColumn('presentPostCode_en');
            $table->dropColumn('presentThana_en');
            $table->dropColumn('presentDistrict_en');

            // Drop permanent address fields in English
            $table->dropColumn('permanentHolding_en');
            $table->dropColumn('permanentVillage_en');
            $table->dropColumn('permanentUnion_en');
            $table->dropColumn('permanentPost_en');
            $table->dropColumn('permanentPostCode_en');
            $table->dropColumn('permanentThana_en');
            $table->dropColumn('permanentDistrict_en');
        });
    }
}
