<?php

include("lib.php");
include("/home/pi/passcheck.php");

//file_put_contents("_POST.arr",print_r($_POST,true));

if(isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST['parname']) && isset($_POST['parvalue'])){
  $parname=$_POST['parname'];
  $parvalue=$_POST['parvalue'];
  NewValueOf($parname,$parvalue,0,30);//update value in database
  http_response_code(200);//OK
} else {
  http_response_code(401);//Unauthorized
}

?>
