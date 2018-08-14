<?php
// ----------------------------------------------------------------------------------
// Lyani++ (c)2018
// This is responsible to read temp sensors. 
// It is needed every 1-5 minutes. 
// To be called by crontab.
// ----------------------------------------------------------------------------------

date_default_timezone_set('UTC');
include("lib.php");

// ----------------------------------------------------------------------------------
// Config
// ----------------------------------------------------------------------------------
$sensor_id_out = "28-041721c5faff";
$sensor_id_in =  "28-041721d908ff";



// ----------------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------------
echo "Temp Sensors read at ".date("Y.m.d H:i:s",time());

UpdateTemp($sensor_id_out,"real_out_temp");
UpdateTemp($sensor_id_in,"real_in_temp");


$room_temp_demand = GetValueOf("room_temp_demand")*1000;
$real_in_temp = GetValueOf("real_in_temp");
if($real_in_temp < $room_temp_demand){
  NewValueOf("room_heater_state",1);
} else {
  NewValueOf("room_heater_state",0);
}


echo "\r\n";




// ----------------------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------------------

function UpdateTemp($sensor_id,$name){
  $newval = ReadTemp($sensor_id);
  if($newval===false){
    echo ", $name read error!";
  } else {
    $lastval=GetValueOf($name);
    if($lastval === "?"){//not yet old data
      $lastval=99999;//extreme value to force write by large diff
    } else {
      $lastval=(float)$lastval;
    }
    $diff=abs($newval-$lastval);
    //echo "lastval = $lastval, newval = $newval, diff = $diff\n";
    if(500 < $diff){//if change is larger than ...
      NewValueOf($name,$newval);
      echo ", Write $name = $newval";
    }
  }
  return $newval;
}


function ReadTemp($sensor_id){
  $file="/sys/bus/w1/devices/$sensor_id/w1_slave";
  if(!file_exists($file))return false;
  foreach( file($file) as $linenum => $line){
    //cf 00 4b 46 7f ff 0c 10 e9 : crc=e9 YES
    if($linenum==0 && false===strpos($line,"YES"))break;
    //cf 00 4b 46 7f ff 0c 10 e9 t=12937
    //fb ff 4b 46 7f ff 0c 10 06 t=-312
    if($linenum==1 && preg_match('/t=([-\d]+)/',$line,$regs)){
      return (intval($regs[1]));
    }
  }
  return false;
}



?>
