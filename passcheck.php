<?php
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")){
  @date_default_timezone_set(@date_default_timezone_get());//set timezone to be able to use date/time
}


function GetAjaxKey(){
  return intval(rand(9999999,999999999));
}

function KeyValid($key){
  return true;
}

function PasswordCheck($password){
  return true;
}

?>
