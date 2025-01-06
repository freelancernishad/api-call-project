<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CitizenInformation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class CitizenInformationController extends Controller
{

    function orgInfo(){

        $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.porichoybd.com/api/orgs/subscription',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'x-api-key: c4cc8c32-161c-496c-adfb-16eeed4607ad'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return $response;


    }

    public function citizeninformationNID(Request $request)
    {
        $token = $request->sToken;
        $loginStatus = apiLogin('freelancernishad123@gmail.com', '12345678', $token);
    
        if ($loginStatus['status'] != 200) {
            return $loginStatus;
        }
    
        $nationalIdNumber = $request->nidNumber;
        $dateOfBirth = date('Y-m-d', strtotime($request->dateOfBirth));
    
        // Check if the citizen information already exists in the database
        $Oldidcheck = CitizenInformation::where(['oldNationalIdNumber' => $nationalIdNumber, 'dateOfBirth' => $dateOfBirth])->count();
        $idcheck = CitizenInformation::where(['nationalIdNumber' => $nationalIdNumber, 'dateOfBirth' => $dateOfBirth])->count();
    
        if ($Oldidcheck > 0 || $idcheck > 0) {
            // Fetch existing citizen information
            $informations = CitizenInformation::where(['nationalIdNumber' => $nationalIdNumber, 'dateOfBirth' => $dateOfBirth])
                ->orWhere(['oldNationalIdNumber' => $nationalIdNumber, 'dateOfBirth' => $dateOfBirth])
                ->first();
    
            // Parse address arrays from the request or external API
            $presentAddressENArray = explode(", ", $request->presentAddressEN ?? '');
            $presentAddressBNArray = explode(", ", $request->presentAddressBN ?? '');
            $permanentAddressENArray = explode(", ", $request->permanentAddressEN ?? '');
            $permanentAddressBNArray = explode(", ", $request->permanentAddressBN ?? '');
    
            // Update or create present and permanent addresses
            $informations = $this->updateOrCreatePresentAddress($informations, $presentAddressENArray, $presentAddressBNArray);
            $informations = $this->updateOrCreatePermanentAddress($informations, $permanentAddressENArray, $permanentAddressBNArray);
    
            // Prepare response
            $informations['photoUrl'] = imageBase64('storage/app/public/' . $informations->photoUrl);
            $responseData = [
                'informations' => $informations,
                'type' => 'NID',
                'message' => 'found',
                'status' => 200,
            ];
            return $responseData;
        }
    
        // If citizen information does not exist, fetch from external API
        $requestBody = '{
            "nidNumber": "' . $nationalIdNumber . '",
            "dateOfBirth": "' . $dateOfBirth . '",
            "englishTranslation": true
        }';
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.porichoybd.com/api/v2/verifications/autofill',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/vnd.api+json',
                'x-api-key: c4cc8c32-161c-496c-adfb-16eeed4607ad'
            ],
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
    
        Log::info($response);
    
        if ($response == 'The API server is not available at the moment. Please try again later.' || $response->status == 'NO') {
            $responseData = [
                'informations' => [],
                'type' => 'NID',
                'message' => 'not-found',
                'status' => 404,
            ];
            return $responseData;
        }
    
        // Create new citizen information
        $NidInfo = (array)$response->data->nid;
        $NidInfo['dateOfBirth'] = $dateOfBirth;
        $CitizenInformation = CitizenInformation::create($NidInfo);
    
        // Parse address arrays from the API response
        $presentAddressENArray = explode(", ", $response->data->nid->presentAddressEN ?? '');
        $presentAddressBNArray = explode(", ", $response->data->nid->presentAddressBN ?? '');
        $permanentAddressENArray = explode(", ", $response->data->nid->permanentAddressEN ?? '');
        $permanentAddressBNArray = explode(", ", $response->data->nid->permanentAddressBN ?? '');
    
        // Update or create present and permanent addresses
        $CitizenInformation = $this->updateOrCreatePresentAddress($CitizenInformation, $presentAddressENArray, $presentAddressBNArray);
        $CitizenInformation = $this->updateOrCreatePermanentAddress($CitizenInformation, $permanentAddressENArray, $permanentAddressBNArray);
    
        // Handle photo URL
        $url = $response->data->nid->photoUrl;
        $client = new Client([
            'timeout' => 60,
            'connect_timeout' => 30,
        ]);
    
        try {
            $response = $client->get($url);
            $imageContent = $response->getBody()->getContents();
            $extArray = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            $ext = $extArray ?: 'jpg';
            $base64Image = base64_encode($imageContent);
            $photoUrl = "data:image/$ext;base64," . $base64Image;
            $savedPath = nidImageSave($photoUrl);
            $CitizenInformation->photoUrl = explode('?', $savedPath)[0];
        } catch (ConnectException | RequestException $e) {
            Log::warning('Failed to connect or fetch image: ' . $e->getMessage());
            $CitizenInformation->photoUrl = null;
        }
    
        $CitizenInformation->save();
    
        // Prepare response
        $CitizenInformation['photoUrl'] = imageBase64('storage/app/public/' . $CitizenInformation->photoUrl);
        $responseData = [
            'informations' => $CitizenInformation,
            'type' => 'NID',
            'message' => 'found',
            'status' => 200,
        ];
        return $responseData;
    }
    
    // Helper function to update or create present address
    protected function updateOrCreatePresentAddress($informations, $presentAddressENArray, $presentAddressBNArray)
    {
        $presentAddressENArrayCount = count($presentAddressENArray);
        $presentAddressBNArrayCount = count($presentAddressBNArray);
    
        // Update present address (English)
        if ($presentAddressENArrayCount > 6) {
            $presentHoldingENArray = explode(':', $presentAddressENArray[0] ?? '');
            $informations->presentHolding_en = $presentHoldingENArray[1] ?? null;
            $informations->presentVillage_en = $presentAddressENArray[1] ?? null;
            $presentPostENArray = explode(':', $presentAddressENArray[4] ?? '');
            $informations->presentUnion_en = $presentPostENArray[1] ?? null;
            $presentPostENArray = explode('-', $presentPostENArray[1] ?? '');
            $informations->presentPost_en = ltrim($presentPostENArray[0] ?? '');
            $informations->presentPostCode_en = $presentPostENArray[1] ?? null;
            $informations->presentThana_en = $presentAddressENArray[5] ?? null;
            $informations->presentDistrict_en = $presentAddressENArray[6] ?? null;
        } elseif ($presentAddressENArrayCount > 5) {
            $presentHoldingENArray = explode(':', $presentAddressENArray[0] ?? '');
            $informations->presentHolding_en = $presentHoldingENArray[1] ?? null;
            $presentVillageENArray = explode(':', $presentAddressENArray[1] ?? '');
            $informations->presentVillage_en = $presentVillageENArray[1] ?? null;
            $informations->presentUnion_en = $presentAddressENArray[2] ?? null;
            $presentPostENArray = explode(':', $presentAddressENArray[3] ?? '');
            $presentPostENArray = explode('-', $presentPostENArray[1] ?? '');
            $informations->presentPost_en = ltrim($presentPostENArray[0] ?? '');
            $informations->presentPostCode_en = $presentPostENArray[1] ?? null;
            $informations->presentThana_en = $presentAddressENArray[4] ?? null;
            $informations->presentDistrict_en = $presentAddressENArray[5] ?? null;
        }
    
        // Update present address (Bangla)
        if ($presentAddressBNArrayCount > 6) {
            $presentHoldingArray = explode(':', $presentAddressBNArray[0] ?? '');
            $informations->presentHolding = $presentHoldingArray[1] ?? null;
            $informations->presentVillage = $presentAddressBNArray[1] ?? null;
            $presentPostArray = explode(':', $presentAddressBNArray[4] ?? '');
            $informations->presentUnion = $presentPostArray[1] ?? null;
            $presentPostArray = explode('-', $presentPostArray[1] ?? '');
            $informations->presentPost = ltrim($presentPostArray[0] ?? '');
            $informations->presentPostCode = $presentPostArray[1] ?? null;
            $informations->presentThana = $presentAddressBNArray[5] ?? null;
            $informations->presentDistrict = $presentAddressBNArray[6] ?? null;
        } elseif ($presentAddressBNArrayCount > 5) {
            $presentHoldingArray = explode(':', $presentAddressBNArray[0] ?? '');
            $informations->presentHolding = $presentHoldingArray[1] ?? null;
            $presentVillageArray = explode(':', $presentAddressBNArray[1] ?? '');
            $informations->presentVillage = $presentVillageArray[1] ?? null;
            $informations->presentUnion = $presentAddressBNArray[2] ?? null;
            $presentPostArray = explode(':', $presentAddressBNArray[3] ?? '');
            $presentPostArray = explode('-', $presentPostArray[1] ?? '');
            $informations->presentPost = ltrim($presentPostArray[0] ?? '');
            $informations->presentPostCode = $presentPostArray[1] ?? null;
            $informations->presentThana = $presentAddressBNArray[4] ?? null;
            $informations->presentDistrict = $presentAddressBNArray[5] ?? null;
        }
    
        $informations->save();
        return $informations;
    }
    
    // Helper function to update or create permanent address
    protected function updateOrCreatePermanentAddress($informations, $permanentAddressENArray, $permanentAddressBNArray)
    {
        $permanentAddressENArrayCount = count($permanentAddressENArray);
        $permanentAddressBNArrayCount = count($permanentAddressBNArray);
    
        // Update permanent address (English)
        if ($permanentAddressENArrayCount > 8) {
            $permanentHoldingENArray = explode(':', $permanentAddressENArray[0] ?? '');
            $informations->permanentHolding_en = $permanentHoldingENArray[1] ?? null;
            $permanentVillageENArray = explode(':', $permanentAddressENArray[2] ?? '');
            $informations->permanentVillage_en = $permanentVillageENArray[1] ?? null;
            $informations->permanentUnion_en = $permanentAddressENArray[4] ?? null;
            $permanentPostENArray = explode(':', $permanentAddressENArray[6] ?? '');
            $permanentPostENArray = explode('-', $permanentPostENArray[1] ?? '');
            $informations->permanentPost_en = ltrim($permanentPostENArray[0] ?? '');
            $informations->permanentPostCode_en = $permanentPostENArray[1] ?? null;
            $informations->permanentThana_en = $permanentAddressENArray[7] ?? null;
            $informations->permanentDistrict_en = $permanentAddressENArray[8] ?? null;
        } elseif ($permanentAddressENArrayCount > 6) {
            $permanentHoldingENArray = explode(':', $permanentAddressENArray[0] ?? '');
            $informations->permanentHolding_en = $permanentHoldingENArray[1] ?? null;
            $permanentVillageENArray = explode(':', $permanentAddressENArray[1] ?? '');
            $informations->permanentVillage_en = $permanentVillageENArray[1] ?? null;
            $informations->permanentUnion_en = $permanentAddressENArray[2] ?? null;
            $permanentPostENArray = explode(':', $permanentAddressENArray[4] ?? '');
            $permanentPostENArray = explode('-', $permanentPostENArray[1] ?? '');
            $informations->permanentPost_en = ltrim($permanentPostENArray[0] ?? '');
            $informations->permanentPostCode_en = $permanentPostENArray[1] ?? null;
            $informations->permanentThana_en = $permanentAddressENArray[5] ?? null;
            $informations->permanentDistrict_en = $permanentAddressENArray[6] ?? null;
        } elseif ($permanentAddressENArrayCount > 5) {
            $permanentHoldingENArray = explode(':', $permanentAddressENArray[0] ?? '');
            $informations->permanentHolding_en = $permanentHoldingENArray[1] ?? null;
            $permanentVillageENArray = explode(':', $permanentAddressENArray[1] ?? '');
            $informations->permanentVillage_en = $permanentVillageENArray[1] ?? null;
            $informations->permanentUnion_en = $permanentAddressENArray[2] ?? null;
            $permanentPostENArray = explode(':', $permanentAddressENArray[3] ?? '');
            $permanentPostENArray = explode('-', $permanentPostENArray[1] ?? '');
            $informations->permanentPost_en = ltrim($permanentPostENArray[0] ?? '');
            $informations->permanentPostCode_en = $permanentPostENArray[1] ?? null;
            $informations->permanentThana_en = $permanentAddressENArray[4] ?? null;
            $informations->permanentDistrict_en = $permanentAddressENArray[5] ?? null;
        }
    
        // Update permanent address (Bangla)
        if ($permanentAddressBNArrayCount > 8) {
            $permanentHoldingArray = explode(':', $permanentAddressBNArray[0] ?? '');
            $informations->permanentHolding = $permanentHoldingArray[1] ?? null;
            $permanentVillageArray = explode(':', $permanentAddressBNArray[2] ?? '');
            $informations->permanentVillage = $permanentVillageArray[1] ?? null;
            $informations->permanentUnion = $permanentAddressBNArray[4] ?? null;
            $permanentPostArray = explode(':', $permanentAddressBNArray[6] ?? '');
            $permanentPostArray = explode('-', $permanentPostArray[1] ?? '');
            $informations->permanentPost = ltrim($permanentPostArray[0] ?? '');
            $informations->permanentPostCode = $permanentPostArray[1] ?? null;
            $informations->permanentThana = $permanentAddressBNArray[7] ?? null;
            $informations->permanentDistrict = $permanentAddressBNArray[8] ?? null;
        } elseif ($permanentAddressBNArrayCount > 6) {
            $permanentHoldingArray = explode(':', $permanentAddressBNArray[0] ?? '');
            $informations->permanentHolding = $permanentHoldingArray[1] ?? null;
            $permanentVillageArray = explode(':', $permanentAddressBNArray[1] ?? '');
            $informations->permanentVillage = $permanentVillageArray[1] ?? null;
            $informations->permanentUnion = $permanentAddressBNArray[2] ?? null;
            $permanentPostArray = explode(':', $permanentAddressBNArray[4] ?? '');
            $permanentPostArray = explode('-', $permanentPostArray[1] ?? '');
            $informations->permanentPost = ltrim($permanentPostArray[0] ?? '');
            $informations->permanentPostCode = $permanentPostArray[1] ?? null;
            $informations->permanentThana = $permanentAddressBNArray[5] ?? null;
            $informations->permanentDistrict = $permanentAddressBNArray[6] ?? null;
        } elseif ($permanentAddressBNArrayCount > 5) {
            $permanentHoldingArray = explode(':', $permanentAddressBNArray[0] ?? '');
            $informations->permanentHolding = $permanentHoldingArray[1] ?? null;
            $permanentVillageArray = explode(':', $permanentAddressBNArray[1] ?? '');
            $informations->permanentVillage = $permanentVillageArray[1] ?? null;
            $informations->permanentUnion = $permanentAddressBNArray[2] ?? null;
            $permanentPostArray = explode(':', $permanentAddressBNArray[3] ?? '');
            $permanentPostArray = explode('-', $permanentPostArray[1] ?? '');
            $informations->permanentPost = ltrim($permanentPostArray[0] ?? '');
            $informations->permanentPostCode = $permanentPostArray[1] ?? null;
            $informations->permanentThana = $permanentAddressBNArray[4] ?? null;
            $informations->permanentDistrict = $permanentAddressBNArray[5] ?? null;
        }
    
        $informations->save();
        return $informations;
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function citizeninformationBRN(Request $request)
    {
        $token = $request->sToken;
        $loginStatus =  apiLogin('freelancernishad123@gmail.com','12345678',$token);

        if($loginStatus['status']!=200){
            return $loginStatus;
        }



        $birthRegistrationNumber = $request->nidNumber;
        $dateOfBirth = date('Y-m-d',strtotime($request->dateOfBirth));


        $idcheck = CitizenInformation::where(['birthRegistrationNumber'=>$birthRegistrationNumber,'dateOfBirth'=>$dateOfBirth])->count();

        if($idcheck>0){
          $informations =  CitizenInformation::where(['birthRegistrationNumber'=>$birthRegistrationNumber,'dateOfBirth'=>$dateOfBirth])->first();

          $responseData = [
            'informations'=>$informations,
            'type'=>'DOB',
            'message'=>'found',
            'status'=>200,
          ];
          return $responseData;

        }else{

            $idcheckForNid = CitizenInformation::where(['birthRegistrationNumber'=>$birthRegistrationNumber])->count();
            if($idcheckForNid>0){
                $responseData = [
                    'informations'=>[],
                    'type'=>'DOB',
                    'message'=>'invaild dateOfBirth',
                    'status'=>301,
                  ];
                  return $responseData;
            }


            $requestBody = '{
                "birthRegistrationNumber": "'.$birthRegistrationNumber.'",
                "dateOfBirth": "'.$dateOfBirth.'"
              }';

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.porichoybd.com/api​/v1/verifications/autofill',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$requestBody,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'x-api-key: c4cc8c32-161c-496c-adfb-16eeed4607ad'
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);


            $response = json_decode($response);
            if($response->status=='NO'){
                $responseData = [
                    'informations'=>[],
                    'type'=>'DOB',
                    'message'=>'not-found',
                    'status'=>404,
                  ];
                  return $responseData;
            }elseif($response->status=='YES'){

                $NidInfo = (array)$response->data->birthRegistration;
                $NidInfo['dateOfBirth'] = $dateOfBirth;
                $NidInfo['birthRegistrationNumber'] = $birthRegistrationNumber;
                $CitizenInformation =  CitizenInformation::create($NidInfo);






                // $presentAddressBNArray =  explode(", ",$response->data->nid->presentAddressBN);
                // $presentAddressBNArrayCount = count($presentAddressBNArray);
                // if($presentAddressBNArrayCount>5){
                // $presentHoldingArray = explode(':',$presentAddressBNArray[0]);
                // $NidInfo['presentHolding'] = $presentHoldingArray[1];
                // $presentVillageArray = explode(':',$presentAddressBNArray[1]);
                // $NidInfo['presentVillage'] = $presentVillageArray[1];
                // $NidInfo['presentUnion'] = $presentAddressBNArray[2];
                // $presentPostArray = explode(':',$presentAddressBNArray[3]);
                // $presentPostArray = explode('-',$presentPostArray[1]);
                // $NidInfo['presentPost'] = ltrim($presentPostArray[0]);
                // $NidInfo['presentPostCode'] = $presentPostArray[1];
                // $NidInfo['presentThana'] = $presentAddressBNArray[4];
                // $NidInfo['presentThana'] = $presentAddressBNArray[4];
                // $NidInfo['presentDistrict'] = $presentAddressBNArray[5];
                // }

                // $permanentAddressArray =  explode(", ",$response->data->nid->permanentAddressBN);
                // $permanentAddressArrayCount = count($permanentAddressArray);
                // if($permanentAddressArrayCount>5){
                //  $permanentHoldingArray = explode(':',$permanentAddressArray[0]);
                // $NidInfo['permanentHolding'] = $permanentHoldingArray[1];

                //  $permanentVillageArray = explode(':',$permanentAddressArray[1]);
                // $NidInfo['permanentVillage'] = $permanentVillageArray[1];

                // $NidInfo['permanentUnion'] = $permanentAddressArray[2];

                // $permanentPostArray = explode(':',$permanentAddressArray[3]);
                // $permanentPostArray = explode('-',$permanentPostArray[1]);
                // $NidInfo['permanentPost'] = ltrim($permanentPostArray[0]);
                // $NidInfo['permanentPostCode'] = $permanentPostArray[1];

                // $NidInfo['permanentThana'] = $permanentAddressArray[4];
                // $NidInfo['permanentDistrict'] = $permanentAddressArray[5];
                // }

                // $url = $response->data->nid->photoUrl; // replace with your image URL


                // $client = new Client();
                // $response = $client->get($url);

                //  $extArray =  pathinfo($url, PATHINFO_EXTENSION);
                //  $extArrayExplode = explode('?',$extArray);
                //  $extCount =  count($extArrayExplode);
                // if($extCount>1){
                //     $ext = $extArrayExplode[0];
                // }else{
                //     $ext = $extArray;
                // }
                // $photoUrl = "data:image/$ext;base64,".base64_encode($response->getBody()->getContents());

                //  $photoUrl= nidImageSave($photoUrl);
                // $extArrayExplode = explode('?',$photoUrl);
                //  $extCount =  count($extArrayExplode);
                // if($extCount>1){
                //     $photoUrl = $extArrayExplode[0];
                // }else{
                //     $photoUrl = $extArrayExplode[0];
                // }
                // $NidInfo['photoUrl'] =  $photoUrl;


                // $CitizenInformation->update($NidInfo);


            }



        }

        $informations =   CitizenInformation::where(['birthRegistrationNumber'=>$birthRegistrationNumber,'dateOfBirth'=>$dateOfBirth])->first();
        $responseData = [
            'informations'=>$informations,
            'type'=>'NID',
            'message'=>'found',
            'status'=>200,
          ];
          return $responseData;


    }










    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CitizenInformation  $citizenInformation
     * @return \Illuminate\Http\Response
     */
    public function show(CitizenInformation $citizenInformation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CitizenInformation  $citizenInformation
     * @return \Illuminate\Http\Response
     */
    public function edit(CitizenInformation $citizenInformation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CitizenInformation  $citizenInformation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CitizenInformation $citizenInformation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CitizenInformation  $citizenInformation
     * @return \Illuminate\Http\Response
     */
    public function destroy(CitizenInformation $citizenInformation)
    {
        //
    }
}





// {
//     "transactionId": "ebaad045-5c92-4540-9aaa-e60ca44ff333-OyaAgI1",
//     "creditCost": 2,
//     "creditCurrent": 21,
//     "status": "YES",
//     "data": {
//         "birthRegistration": {
//             "fullNameBN": "মোঃ নিশাদ হোসাইন",
//             "fullNameEN": "Md. Nisad Hossain",
//             "gender": "M",
//             "dateOfBirth": "2001-08-25T00:00:00",
//             "birthPlaceBN": "বানেশ্বর পাড়া, ডাকঘর-টেপ্রীগঞ্জ-৫০২০ উপজেলা-দেবীগঞ্জ-জেলা-পঞ্চগড়।",
//             "mothersNameBN": "বানেছা বেগম",
//             "mothersNameEN": "Banesa Begum",
//             "mothersNationalityBN": "বাংলাদেশী",
//             "mothersNationalityEN": "Bangladeshi",
//             "fathersNameBN": "মোঃ জয়নাল আবেদীন",
//             "fathersNameEN": "Joynal",
//             "fathersNationalityBN": "বাংলাদেশী",
//             "fathersNationalityEN": "Bangladeshi"
//         }
//     },
//     "errors": []
// }



// {
//     "fullNameEN": "NISHAD HOSSAIN",
//     "fathersNameEN": "Md. Joynal Abedin",
//     "mothersNameEN": "Banesha Begum",
//     "presentAddressEN": "Home / Holding: -, Village / Road: Baneshwar Para, Tepriganj, Post Office: Tepriganj-5020, Debiganj, Panchagarh",
//     "permenantAddressEN": "Home / Holding: -, Village / Road: Baneshwar Para, Tepriganj, Post Office: Tepriganj-5020, Debiganj, Panchagarh",
//     "fullNameBN": "নিশাদ হোসাইন",
//     "fathersNameBN": "মোঃ জয়নাল আবেদীন",
//     "mothersNameBN": "বানেছা বেগম",
//     "spouseNameBN": "",
//     "presentAddressBN": "বাসা/হোল্ডিং: -, গ্রাম/রাস্তা: বানেশ্বর পাড়া, টেপ্রীগঞ্জ, ডাকঘর: টেপ্রীগঞ্জ-৫০২০, দেবীগঞ্জ, পঞ্চগড়",
//     "permanentAddressBN": "বাসা/হোল্ডিং: -, গ্রাম/রাস্তা: বানেশ্বর পাড়া, টেপ্রীগঞ্জ, ডাকঘর: টেপ্রীগঞ্জ-৫০২০, দেবীগঞ্জ, পঞ্চগড়",
//     "gender": "male",
//     "profession": "ছাত্র/ছাত্রী",
//     "dateOfBirth": "2001-08-25T00:00:00",
//     "nationalIdNumber": "7811287346",
//     "oldNationalIdNumber": "20017713495000344",
//     "photoUrl": "https://prportal.nidw.gov.bd/file-1f/d/b/9/82fdaaa7-b2eb-48bc-a041-71a19507f528/Photo-82fdaaa7-b2eb-48bc-a041-71a19507f528.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=fileobj%2F20230401%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20230401T044812Z&X-Amz-Expires=120&X-Amz-SignedHeaders=host&X-Amz-Signature=e0f825578c006ee9ad9412db051f6bafa396debee5f05cb8d1674372d6520ff1"
// }




// {
//     "transactionId": "74084e74-d732-44d4-ad29-1930ef4bc17c",
//     "creditCost": 0,
//     "creditCurrent": 0,
//     "data": {},
//     "errors": [
//         {
//             "code": "Validation:NoCredit",
//             "message": "You have 0"
//         }
//     ]
// }
