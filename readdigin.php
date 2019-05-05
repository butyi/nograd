<?php
// ----------------------------------------------------------------------------------
// Lyani++ (c)2018
// This is responsible to monitor digital inputs is changed.
// This is needed more times a second, so this is a continuous activity.
// To be executed as a background service.
// ----------------------------------------------------------------------------------
include("/home/pi/config.php");
include("lib.php");

//wait 15s to init mysql database
usleep(15*1000*1000);

//create my shared memory
SharedMemory(
  $SHARED_MEMORY_KEY, //My mem key
  $SEMAPHORE_KEY_I2C, //Semaphore key
  "c", //create
  0644, //file access mode
  10, //number of bytes
  "InitMyMemory" //clear all bytes
);

function InitMyMemory($shared_memory_array){
  for($i=0;$i<count($shared_memory_array);$i++){
    $shared_memory_array[$i]=0;
  }
  return $shared_memory_array;//give back the modified array
}



//endless task: demon
while(1){

  //100ms wait
  usleep(100 * 1000); 

  //i2c <-> memory  data copy
  SharedMemory(
    $SHARED_MEMORY_KEY, //My mem key
    $SEMAPHORE_KEY_I2C, //Semaphore key
    "w", //read and write
    0, //don't care
    0, //don't care
    "I2C_MyMemory" //1. looking for output changes, write new value to output latch when changed, 2. read inputs
  );

}


function I2C_MyMemory($shared_memory_array){

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
    foreach($digins as $digin){//for all inputs
      if( $shared_memory_array[2] & (1<<$digin["bit"]) ){//real 1
        $newvalue=1;
      } else {//0
        $newvalue=0;
      }
      NewValueOf($digin["dbname"],$newvalue,0,1);//update it to 1
    }
    $shared_memory_array[3] = $shared_memory_array[2];
  }
  
  // -- OUTPUTS --
  if($shared_memory_array[1] != $shared_memory_array[0]){//if changed
    //write new value to MCP23017

    //write new value
    $call="/usr/sbin/i2cset -y 0 0x20 0x14 0x".sprintf("%02X",$shared_memory_array[0]);
    exec($call);

    $shared_memory_array[1] = $shared_memory_array[0];
  }
  
  return $shared_memory_array;//give back the modified array
}



?>
