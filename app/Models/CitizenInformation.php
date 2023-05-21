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
    ];

}
