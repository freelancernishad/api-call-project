
<?php

use App\Models\User;
use GuzzleHttp\Client;
use App\Models\ApiToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

   // Function to save base64 image to storage
    function nidImageSave($base64Image) {
        $FileYear = date('Y');
        $FileMonth = date('m');
        $FileDate = date('d');
        $randomString = Str::random(10);

        // Extract the extension from the base64 URL
        preg_match('/data:image\/(.*?);base64/', $base64Image, $matches);
        $extension = $matches[1] ?? 'jpg';

        $filenameWithEx = time() . '_' . $randomString . '.' . $extension;
        $path = "public/$FileYear/$FileMonth/$FileDate/$filenameWithEx";
        $returnFilename = "$FileYear/$FileMonth/$FileDate/$filenameWithEx";

        // Decode the base64 content
        $fileContents = base64_decode(str_replace("data:image/$extension;base64,", '', $base64Image));

        // Store the image file
        Storage::put($path, $fileContents);

        return $returnFilename;
    }

    function imageBase64($url) {
        // Check if the file exists
        if (!file_exists($url)) {
            // Return a default placeholder or null if the file doesn't exist
            return null;
        }

        // Attempt to get the file contents
        try {
            $imageContent = file_get_contents($url);
        } catch (Exception $e) {
            // Log the error or return null if an exception occurs
            Log::error("Failed to open stream for $url: " . $e->getMessage());
            return null;
        }

        // Get the file extension
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        // Encode the image in base64
        $base64Image = base64_encode($imageContent);

        // Return the base64 image URL
        return "data:image/$extension;base64," . $base64Image;
    }











