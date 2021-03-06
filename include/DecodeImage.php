<?php

class DecodeImage 
{
    public static function upload($image){
    //create unique image file name based on micro time and date
    $now = DateTime::createFromFormat('U.u', microtime(true));
    $id = $now->format('YmdHisu');
    
    $upload_folder = "UploadedImages"; //DO NOT put url (http://example.com/upload)
    $path = "$upload_folder/$id.jpeg";
    $real_path = "http://localhost/station_manager/v1/$upload_folder/$id.jpeg";
    
    //Cannot use "== true"
    if(file_put_contents($path, base64_decode($image)) != false){
        return $real_path;
    }
    else{
        return FALSE; 
    }
      
}
}
?>

