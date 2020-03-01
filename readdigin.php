<?php
// ----------------------------------------------------------------------------------
// Lyani++ (c)2018
// This is responsible to monitor digital inputs is changed.
// This is needed more times a second, so this is a continuous activity.
// To be executed as a background service.
//   crontab -e
//     add this line to start this script as a daemon at startup:
//   "@reboot php /var/www/html/readdigin.php &"
// ----------------------------------------------------------------------------------
include("/home/pi/config.php");
include("lib.php");

$logfile="/home/pi/readdigin.log";
file_put_contents($logfile,"Demon readdigin started. ".date("Y.m.d H:i:s")."\r\n");

//wait 30s to init mysql database
usleep(30*1000*1000);
file_put_contents($logfile,"Delay (30s) elapsed. ".date("Y.m.d H:i:s")."\r\n",FILE_APPEND);

//create my shared memory
$ret = SharedMemory(
  $SHARED_MEMORY_KEY, //My mem key
  $SEMAPHORE_KEY_I2C, //Semaphore key
  "c", //create
  0777, //file access mode   !!!Todo!!! This shall be investigated which user to be added to which group to not need full access here.
  10, //number of bytes
  "InitMyMemory" //clear all bytes
);

if($ret !== true){
  file_put_contents($logfile,"Create problem: $ret \r\n",FILE_APPEND);
  exit;
}
file_put_contents($logfile,"Shared memory created. I2C task is starting. ".date("Y.m.d H:i:s")."\r\n",FILE_APPEND);

function InitMyMemory($shared_memory_array){
  for($i=0;$i<count($shared_memory_array);$i++){
    $shared_memory_array[$i]=0;
  }
  return $shared_memory_array;//give back the modified array
}



//endless task: demon
while(1){

  //250ms wait (four times in a second)
  usleep(250 * 1000);

  //i2c <-> memory  data copy
  $ret = SharedMemory(
    $SHARED_MEMORY_KEY, //My mem key
    $SEMAPHORE_KEY_I2C, //Semaphore key
    "w", //read and write
    0, //don't care
    0, //don't care
    "I2C_MyMemory" //1. looking for output changes, write new value to output latch when changed, 2. read inputs
  );

  if($ret !== true){
    file_put_contents($logfile,"Read/write problem: $ret \r\n",FILE_APPEND);
  }

}


function I2C_MyMemory($shared_memory_array){
  global $digins;

  //init direstion registers for both input and output
  $call="/usr/sbin/i2cset -y 0 0x20 0x00 0x00 0xFF i";
  exec($call);

  // -- INPUTS --
  //read digin from MCP23017 through i2c
  $call="/usr/sbin/i2cget -y 0 0x20 0x13";//get all 8 bits
  unset($ret);
  exec($call,$ret);
  $shared_memory_array[2]=hexdec(substr($ret[0],2));
  if($shared_memory_array[3] != $shared_memory_array[2]){//if changed

    foreach($digins as $digin){
      if( $shared_memory_array[2] & (1<<$digin["bit"]) ){//real 1
        if(GetValueOf($digin["dbname"])==0){//if 0 is stored
          NewValueOf($digin["dbname"],1,0,1);//update it to 1
        }
      } else {//0
        if(GetValueOf($digin["dbname"])!=0){//if 1 is stored
          NewValueOf($digin["dbname"],0,0,1);//update it to 0
        }
      }
    }
    //echo $shared_memory_array[2]."\n";
    $shared_memory_array[3] = $shared_memory_array[2];
  }

  // -- OUTPUTS --
  if($shared_memory_array[1] != $shared_memory_array[0]){//if changed
    //write new value to MCP23017

    //write new value
    $call="/usr/sbin/i2cset -y 0 0x20 0x14 0x".sprintf("%02X",$shared_memory_array[0]);
    exec($call);

    //administrate that change was written
    $shared_memory_array[1] = $shared_memory_array[0];
  }

  return $shared_memory_array;//give back the modified array
}



?>
