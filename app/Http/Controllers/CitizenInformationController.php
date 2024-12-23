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
        $loginStatus =  apiLogin('freelancernishad123@gmail.com','12345678',$token);

        if($loginStatus['status']!=200){
            return $loginStatus;

        }


        $nationalIdNumber = $request->nidNumber;
        $dateOfBirth = date('Y-m-d',strtotime($request->dateOfBirth));




        $Oldidcheck = CitizenInformation::where(['oldNationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->count();



        if($Oldidcheck>0){
          $informations = CitizenInformation::where(['oldNationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->first();
          $informations['photoUrl'] = imageBase64('storage/app/public/'.$informations->photoUrl);
          $responseData = [
            'informations'=>$informations,
            'type'=>'NID',
            'message'=>'found',
            'status'=>200,
          ];
          return $responseData;
        }


        $idcheck = CitizenInformation::where(['nationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->count();
        if($idcheck>0){
          $informations = CitizenInformation::where(['nationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->first();
          $informations['photoUrl'] = imageBase64('storage/app/public/'.$informations->photoUrl);
          $responseData = [
            'informations'=>$informations,
            'type'=>'NID',
            'message'=>'found',
            'status'=>200,
          ];
          return $responseData;
        }else{


            $oldidcheckForNid = CitizenInformation::where(['oldNationalIdNumber'=>$nationalIdNumber])->count();
            if($oldidcheckForNid>0){
                $responseData = [
                    'informations'=>[],
                    'type'=>'NID',
                    'message'=>'invaild dateOfBirth',
                    'status'=>301,
                  ];
                  return $responseData;
            }


            $idcheckForNid = CitizenInformation::where(['nationalIdNumber'=>$nationalIdNumber])->count();
            if($idcheckForNid>0){
                $responseData = [
                    'informations'=>[],
                    'type'=>'NID',
                    'message'=>'invaild dateOfBirth',
                    'status'=>301,
                  ];
                  return $responseData;
            }


            $requestBody = '{
                "nidNumber": "'.$nationalIdNumber.'",
                "dateOfBirth": "'.$dateOfBirth.'",
                "englishTranslation": true
              }';
              $curl = curl_init();
              curl_setopt_array($curl, array(
                // CURLOPT_URL => 'https://api.porichoybd.com/sandbox-api/v2/verifications/autofill',
                CURLOPT_URL => 'https://api.porichoybd.com/api/v2/verifications/autofill',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$requestBody,
                CURLOPT_HTTPHEADER => array(
                  'Content-Type: application/vnd.api+json',
                  'x-api-key: c4cc8c32-161c-496c-adfb-16eeed4607ad'
                ),
              ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);

            if($response->status=='NO'){
                $responseData = [
                    'informations'=>[],
                    'type'=>'NID',
                    'message'=>'not-found',
                    'status'=>404,
                  ];
                  return $responseData;
            }elseif($response->status=='YES'){
                $NidInfo = (array)$response->data->nid;
                $NidInfo['dateOfBirth'] = $dateOfBirth;
                $CitizenInformation =  CitizenInformation::create($NidInfo);
                $presentAddressBNArray =  explode(", ",$response->data->nid->presentAddressBN);
                $presentAddressBNArrayCount = count($presentAddressBNArray);


                if($presentAddressBNArrayCount>6){
                    $presentHoldingArray = explode(':',$presentAddressBNArray[0]);
                    $NidInfo['presentHolding'] = $presentHoldingArray[1];
                    // $presentVillageArray = explode(':',$presentAddressBNArray[1]);
                    $NidInfo['presentVillage'] = $presentAddressBNArray[1];
                    $presentPostArray = explode(':',$presentAddressBNArray[4]);
                    $NidInfo['presentUnion'] = $presentPostArray[1];
                    $presentPostArray = explode('-',$presentPostArray[1]);
                    $NidInfo['presentPost'] = ltrim($presentPostArray[0]);
                    $NidInfo['presentPostCode'] = $presentPostArray[1];
                    $NidInfo['presentThana'] = $presentAddressBNArray[5];
                    $NidInfo['presentDistrict'] = $presentAddressBNArray[6];


                }


                elseif($presentAddressBNArrayCount>5){
                    $presentHoldingArray = explode(':',$presentAddressBNArray[0]);
                    $NidInfo['presentHolding'] = $presentHoldingArray[1];
                    $presentVillageArray = explode(':',$presentAddressBNArray[1]);
                    $NidInfo['presentVillage'] = $presentVillageArray[1];
                    $NidInfo['presentUnion'] = $presentAddressBNArray[2];

                    $presentPostArray = explode(':',$presentAddressBNArray[3]);
                    $presentPostArray = explode('-',$presentPostArray[1]);

                    $NidInfo['presentPost'] = ltrim($presentPostArray[0]);
                    $NidInfo['presentPostCode'] = $presentPostArray[1];
                    $NidInfo['presentThana'] = $presentAddressBNArray[4];
                    $NidInfo['presentDistrict'] = $presentAddressBNArray[5];
                }




                $permanentAddressArray =  explode(", ",$response->data->nid->permanentAddressBN);
                $permanentAddressArrayCount = count($permanentAddressArray);
                if($permanentAddressArrayCount>8){

                    $permanentHoldingArray = explode(':',$permanentAddressArray[0]);
                    $NidInfo['permanentHolding'] = $permanentHoldingArray[1];

                     $permanentVillageArray = explode(':',$permanentAddressArray[2]);
                    $NidInfo['permanentVillage'] = $permanentVillageArray[1];

                    $NidInfo['permanentUnion'] = $permanentAddressArray[4];

                    $permanentPostArray = explode(':',$permanentAddressArray[6]);
                    $permanentPostArray = explode('-',$permanentPostArray[1]);
                    $NidInfo['permanentPost'] = ltrim($permanentPostArray[0]);
                    $NidInfo['permanentPostCode'] = $permanentPostArray[1];

                    $NidInfo['permanentThana'] = $permanentAddressArray[7];
                    $NidInfo['permanentDistrict'] = $permanentAddressArray[8];
                }elseif($permanentAddressArrayCount>6){

                    $permanentHoldingArray = explode(':',$permanentAddressArray[0]);
                    $NidInfo['permanentHolding'] = $permanentHoldingArray[1];

                     $permanentVillageArray = explode(':',$permanentAddressArray[1]);
                    $NidInfo['permanentVillage'] = $permanentVillageArray[1];



                    $NidInfo['permanentUnion'] = $permanentAddressArray[2];

                    $permanentPostArray = explode(':',$permanentAddressArray[4]);
                    $permanentPostArray = explode('-',$permanentPostArray[1]);
                    $NidInfo['permanentPost'] = ltrim($permanentPostArray[0]);
                    $NidInfo['permanentPostCode'] = $permanentPostArray[1];

                    $NidInfo['permanentThana'] = $permanentAddressArray[5];
                    $NidInfo['permanentDistrict'] = $permanentAddressArray[6];
                }elseif($permanentAddressArrayCount>5){


                    $permanentHoldingArray = explode(':',$permanentAddressArray[0]);
                    $NidInfo['permanentHolding'] = $permanentHoldingArray[1];

                     $permanentVillageArray = explode(':',$permanentAddressArray[1]);
                    $NidInfo['permanentVillage'] = $permanentVillageArray[1];

                    $NidInfo['permanentUnion'] = $permanentAddressArray[2];

                    $permanentPostArray = explode(':',$permanentAddressArray[3]);
                    $permanentPostArray = explode('-',$permanentPostArray[1]);
                    $NidInfo['permanentPost'] = ltrim($permanentPostArray[0]);
                    $NidInfo['permanentPostCode'] = $permanentPostArray[1];

                    $NidInfo['permanentThana'] = $permanentAddressArray[4];
                    $NidInfo['permanentDistrict'] = $permanentAddressArray[5];
                }

                $url = $response->data->nid->photoUrl; // Replace with actual URL

                $client = new Client([
                    'timeout' => 60, // Timeout in seconds
                    'connect_timeout' => 30, // Connection timeout in seconds
                ]);

                try {
                    // Try fetching the image
                    $response = $client->get($url);
                    $imageContent = $response->getBody()->getContents();

                    // Extract the extension from the URL, default to 'jpg' if not found
                    $extArray = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $ext = $extArray ?: 'jpg';

                    // Base64 encode the image
                    $base64Image = base64_encode($imageContent);
                    $photoUrl = "data:image/$ext;base64," . $base64Image;

                    // Save the image using the custom function
                    $savedPath = nidImageSave($photoUrl);

                    // Remove query parameters if present
                    $NidInfo['photoUrl'] = explode('?', $savedPath)[0];

                } catch (ConnectException | RequestException $e) {
                    // Log the error but ignore saving if a connection error occurs
                    Log::warning('Failed to connect or fetch image: ' . $e->getMessage());

                    // Set a default or null value for photoUrl
                    $NidInfo['photoUrl'] = null; // Or set a placeholder URL if needed
                }


                $CitizenInformation->update($NidInfo);


            }



        }


        // $nationalIdNumberCount =   CitizenInformation::where(['nationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->count();
        $oldNationalIdNumberCount =   CitizenInformation::where(['oldNationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->count();

        if($oldNationalIdNumberCount>0){
            $informations =   CitizenInformation::where(['oldNationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->first();
        }else{
            $informations =   CitizenInformation::where(['nationalIdNumber'=>$nationalIdNumber,'dateOfBirth'=>$dateOfBirth])->first();
        }


        $informations['photoUrl'] = imageBase64('storage/app/public/'.$informations->photoUrl);
        $responseData = [
            'informations'=>$informations,
            'type'=>'NID',
            'message'=>'found',
            'status'=>200,
          ];
          return $responseData;

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
