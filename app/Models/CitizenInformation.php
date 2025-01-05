<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitizenInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullNameEN',
        'fathersNameEN',
        'mothersNameEN',
        'spouseNameEN',
        'presentAddressEN',
        'permenantAddressEN',
        'fullNameBN',
        'fathersNameBN',
        'mothersNameBN',
        'spouseNameBN',

        'presentAddressBN',
        'presentHolding',
        'presentVillage',
        'presentUnion',
        'presentPost',
        'presentPostCode',
        'presentThana',
        'presentDistrict',

        'permanentAddressBN',
        'permanentHolding',
        'permanentVillage',
        'permanentUnion',
        'permanentPost',
        'permanentPostCode',
        'permanentThana',
        'permanentDistrict',

        'gender',
        'profession',
        'dateOfBirth',

        'birthPlaceBN',
        'mothersNationalityBN',
        'mothersNationalityEN',
        'fathersNationalityBN',
        'fathersNationalityEN',
        'birthRegistrationNumber',


        'nationalIdNumber',
        'oldNationalIdNumber',
        'photoUrl',



           // New fields for present address in English
           'presentHolding_en',
           'presentVillage_en',
           'presentUnion_en',
           'presentPost_en',
           'presentPostCode_en',
           'presentThana_en',
           'presentDistrict_en',

           // New fields for permanent address in English
           'permanentHolding_en',
           'permanentVillage_en',
           'permanentUnion_en',
           'permanentPost_en',
           'permanentPostCode_en',
           'permanentThana_en',
           'permanentDistrict_en',

    ];

}
