<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitizenInformationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('citizen_information', function (Blueprint $table) {
            $table->id();
            $table->string('fullNameEN')->nullable();
            $table->string('fathersNameEN')->nullable();
            $table->string('mothersNameEN')->nullable();
            $table->string('spouseNameEN')->nullable();
            $table->text('presentAddressEN')->nullable();
            $table->text('permenantAddressEN')->nullable();
            $table->string('fullNameBN')->nullable();
            $table->string('fathersNameBN')->nullable();
            $table->string('mothersNameBN')->nullable();
            $table->string('spouseNameBN')->nullable();
            $table->text('presentAddressBN')->nullable();
            $table->string('presentHolding')->nullable();
            $table->string('presentVillage')->nullable();
            $table->string('presentPost')->nullable();
            $table->string('presentPostCode')->nullable();
            $table->string('presentThana')->nullable();
            $table->string('presentDistrict')->nullable();

            $table->text('permanentAddressBN')->nullable();
            $table->string('permanentHolding')->nullable();
            $table->string('permanentVillage')->nullable();
            $table->string('permanentPost')->nullable();
            $table->string('permanentPostCode')->nullable();
            $table->string('permanentThana')->nullable();
            $table->string('permanentDistrict')->nullable();


            $table->string('gender')->nullable();
            $table->string('profession')->nullable();
            $table->string('dateOfBirth')->nullable();


            $table->text('birthPlaceBN')->nullable();
            $table->string('mothersNationalityBN')->nullable();
            $table->string('mothersNationalityEN')->nullable();
            $table->string('fathersNationalityBN')->nullable();
            $table->string('fathersNationalityEN')->nullable();




            $table->string('birthRegistrationNumber')->nullable();
            $table->string('nationalIdNumber')->nullable();
            $table->string('oldNationalIdNumber')->nullable();
            $table->longText('photoUrl')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('citizen_information');
    }
}
