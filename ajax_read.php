<?php

include("lib.php");
include("/home/pi/passcheck.php");

//file_put_contents("POST.arr",print_r($_POST,true));

if(isset($_POST["key"]) && KeyValid($_POST["key"])){
  if(isset($_POST["prevval"])){
    $prev_lampbuttonstates = $_POST["prevval"];
  }
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
  $n = 20; //2s
  while($n--){//wait for state change
    $lampbuttonstates = 0;
    $lampbuttonstates|=(GetValueOf("kitchen_lamp_state") ? 1 : 0);
    $lampbuttonstates|=(GetValueOf("room_lamp_state") ? 2 : 0);
    $lampbuttonstates|=(GetValueOf("shower_lamp_state") ? 4 : 0);
    $lampbuttonstates|=(GetValueOf("terrace_lamp_state") ? 8 : 0);
    if($lampbuttonstates != $prev_lampbuttonstates)break; //answer immediately
    usleep(100 * 1000);//wait 100ms
  }
  $ret.=$lampbuttonstates."|";
  echo $ret;
  http_response_code(200);//OK
} else {
  http_response_code(401);//Unauthorized
}
?>
