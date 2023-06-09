
<?php

use App\Models\User;
use App\Models\ApiToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
function apiLogin($email,$password,$token){


   $tokenCount = ApiToken::where('token',$token)->count();
   if(!$tokenCount){
    return $data = [
        'message' => 'Invalid token',
        'status' => 403,
     ];
   }

   $Apitoken = ApiToken::where('token',$token)->first();


   $tokenUse = $Apitoken->use;
   if($tokenUse){
    return $data = [
        'message' => 'token already used',
        'status' => 300,
     ];
   }

   $nowTime = date('Y-m-d H:i:s');
   $expired = $Apitoken->expired;
   if($nowTime>$expired){
    return $data = [
        'message' => 'token Expired',
        'status' => 402,
     ];
   }


$user = User::where(['email'=>$email])->first();
if (Hash::check($password, $user->password)) {


     tokenUpdate($Apitoken->token);


return $data = [
   'message' => 'valid token',
   'status' => 200,
];
} else {
    return $data =   [
        'message' => 'You are not authorized',
        'status' => 401,
     ];

}

}



    function tokenUpdate($token){
         $apitoken = ApiToken::where('token',$token)->first();
        $apitoken->update(['use'=>1]);
    }

    function nidImageSave($url){


        $FileYear = date('Y');
        $FileMonth = date('m');
        $FileDate = date('d');
        $randomString = Str::random(10);
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if(!$extension){
            $extension = 'jpg';
        }
        $filenameWithEx = time() . '_' . $randomString . '.' . $extension;
        $filename = "public/$FileYear/$FileMonth/$FileDate/$filenameWithEx";

        $returnFilename = "$FileYear/$FileMonth/$FileDate/$filenameWithEx";

        $fileContents = file_get_contents($url);
         Storage::disk('local')->put($filename, $fileContents);
         return $returnFilename;
    }

    function imageBase64($url){

        $imageContent = file_get_contents($url);

$extension = pathinfo($url, PATHINFO_EXTENSION);
$base64Image = base64_encode($imageContent);


return $base64Url = "data:image/$extension;base64," . $base64Image;

    }
