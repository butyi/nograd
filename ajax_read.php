<?php

include("lib.php");
include("/home/pi/passcheck.php");

//file_put_contents("POST.arr",print_r($_POST,true));

if(isset($_POST["key"]) && KeyValid($_POST["key"])){
  $ret="";
  $ret.=GetValueOf("room_temp_demand")."|";
  $val = GetValueOf("real_in_temp","time","DESC","value","1 DAY",true);
  if(is_numeric($val)){
    $ret.=round($val/1000,1)."|";
  } else {
    $ret.=$val."|";
  }
  $val = GetValueOf("real_out_temp","time","DESC","value","1 DAY",true);
  if(is_numeric($val)){
    $ret.=round($val/1000,1)."|";
  } else {
    $ret.=$val."|";
  }
  $ret.=(GetValueOf("room_heater_state") ? "Be" : "Ki")."|";
  $ret.=(GetValueOf("kitchen_lamp_state") ? "Be" : "Ki")."|";
  $ret.=(GetValueOf("room_lamp_state") ? "Be" : "Ki")."|";
  $ret.=(GetValueOf("shower_lamp_state") ? "Be" : "Ki")."|";
  $ret.=(GetValueOf("terrace_lamp_state") ? "Be" : "Ki")."|";
  echo $ret;
  http_response_code(200);//OK
} else {
  http_response_code(401);//Unauthorized
}
?>
