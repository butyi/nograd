<?php
// ----------------------------------------------------------------------------------
// Lyani++ (c)2018
// This is responsible to monitor digital inputs is changed.
// This is needed more times a second, so this is a continuous activity.
// To be executed as a background service.
// ----------------------------------------------------------------------------------
include("/home/pi/config.php");
include("lib.php");

$logfile="/home/pi/readdigin.log";
file_put_contents($logfile,"Demon readdigin started. ".date("Y.m.d H:i:s")."\r\n");

//wait 30s to init mysql database
usleep(30*1000*1000);
file_put_contents($logfile,"Delay (30s) elapsed, I2C task is starting. ".date("Y.m.d H:i:s")."\r\n",FILE_APPEND);

if(!file_exists($out_dat)){ //if file is not yet exists
  file_put_contents($out_dat,"00");  //create it with off state outputs
}

//endless task: demon
while(1){

  //50ms wait (five times in a second)
  usleep(50 * 1000);

  //init direstion registers for both input and output
  $call="/usr/sbin/i2cset -y 0 0x20 0x00 0x00 0xFF i";
  exec($call);

  // -- INPUTS --
  //read digin from MCP23017 through i2c, if changed, update mySQL database
  $call="/usr/sbin/i2cget -y 0 0x20 0x13";//get all 8 bits
  unset($ret);
  exec($call,$ret);
  $inbyte=substr($ret[0],2);
  file_put_contents($in_dat,$inbyte);  //create it with off state outputs
  $inbyte=hexdec($inbyte);

  foreach($digins as $digin){
    if( $inbyte & (1<<$digin["bit"]) ){//real 1
      if(GetValueOf($digin["dbname"])==0){//if 0 is stored
        NewValueOf($digin["dbname"],1,0,1);//update it to 1
      }
    } else {//0
      if(GetValueOf($digin["dbname"])!=0){//if 1 is stored
        NewValueOf($digin["dbname"],0,0,1);//update it to 0
      }
    }
  }

  // -- OUTPUTS --
  //write output values to MCP23017 (always, even not changed)
  $outdata="00";
  if(file_exists($out_dat)){ //if file is not yet exists
    $outdata=file_get_contents($out_dat);
  }
  $call="/usr/sbin/i2cset -y 0 0x20 0x14 0x".substr($outdata,0,2);
  exec($call);

}



?>
