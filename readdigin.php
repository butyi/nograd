<?php
// ----------------------------------------------------------------------------------
// Lyani++ (c)2018
// This is responsible to monitor digital inputs is changed. 
// This is needed more times a second, so this is a continuous activity. 
// To be executed as a background service.
// ----------------------------------------------------------------------------------
include("/home/pi/config.php");
include("lib.php");

while(1)
{
  usleep(500*1000);//wait 500ms

  //read digin from i2c
  $call="/usr/sbin/i2cget -y 1 0x21 0x12";//get all 8 bits
  unset($ret);
  exec($call,$ret);
  $byte=hexdec(substr($ret[0],2));
  //echo $byte;

  foreach($digins as $digin){
    if( $byte & (1<<$digin["bit"]) ){//real 1
      if(GetValueOf($digin["dbname"])==0){//if 0 is stored
        NewValueOf($digin["dbname"],1,0,1);//update it to 1
      }   
    } else {//0
      if(GetValueOf($digin["dbname"])!=0){//if 1 is stored
        NewValueOf($digin["dbname"],0,0,1);//update it to 0
      }   
    }
  }  

}


?>
