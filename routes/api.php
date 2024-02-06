<?php

use App\Models\User;
use App\Models\ApiToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CitizenInformationController;
use Illuminate\Http\Request;

Route::get('/user', function (Request $request) {

    return  $request->header('Origin');



       });
Route::middleware('ipProtection')->group(function () {




    Route::get('/token/genarate', function () {
        $randomString = Str::random(50);
        $hashedToken = Hash::make($randomString);
        $encodedToken = base64_encode($hashedToken);
        $validatedData = [
            'genarate' => date('Y-m-d H:i:s'),
            'token'=>$encodedToken,
            'use' => 0,
            'expired' => date('Y-m-d H:i:s', strtotime("+5 minutes")),
        ];
        $apitoken = ApiToken::create($validatedData);
        $apitoken = $apitoken->token;
        return response()->json([
            'apitoken' => $apitoken,
        ], 201);
    });



Route::post('citizen/information/nid', [CitizenInformationController::class,'citizeninformationNID']);
Route::post('citizen/information/brn', [CitizenInformationController::class,'citizeninformationBRN']);
Route::post('main/balance', [CitizenInformationController::class,'orgInfo']);







});

