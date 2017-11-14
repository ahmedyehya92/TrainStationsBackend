<?php

class DecodeImage 
{
    public static function upload($image){
    //create unique image file name based on micro time and date
    $now = DateTime::createFromFormat('U.u', microtime(true));
    $id = $now->format('YmdHisu');
    
    $upload_folder = "UploadedImages"; //DO NOT put url (http://example.com/upload)
    $path = "$upload_folder/$id.jpeg";
    
    //Cannot use "== true"
    if(file_put_contents($path, base64_decode($image)) != false){
        echo "uploaded_success";
    }
    else{
        echo "uploaded_failed";
    }
    return $path;   
}
}
?>

