<?php
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")){
  @date_default_timezone_set(@date_default_timezone_get());//set timezone to be able to use date/time
}


function GetAjaxKey(){
  //Here you have to write your own code. Of course I do not share my code.
  return intval(rand(9999999,999999999));
}

function KeyValid($key){
  //Here you have to write your own code. Of course I do not share my code.
  return true;
}

function PasswordCheck($password){
  //Here you have to write your own code. Of course I do not share my code.
  return true;
}

?>
