<?php

include("lib.php");
include("/home/pi/config.php");
include("/home/pi/passcheck.php");


if( isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST["port"])){//call from ajax
  $port = $_POST["port"];
  //$logfile = "pulse.log";
  if(isset($logfile))file_put_contents($logfile,print_r($_POST,true));
} else if($argc==2){//call from command line
  $port = $argv[1];
  $logfile = "/home/pi/pulse.log";
  if(isset($logfile))file_put_contents($logfile,"CLI, port: $port\r\n");
} else {
  http_response_code(401);//Unauthorized
  exit;
}

if($port<0 || 3<$port){
  http_response_code(400);//Bad Request
  exit;
}

$port_bit= 1 << intval($port);

//set bit
$ret=SharedMemory(
  $SHARED_MEMORY_KEY, //My mem key
  $SEMAPHORE_KEY_BTN, //Semaphore key
  "w", //read and write
  0, //don't care
  0, //don't care
  "PulseSet_MyMemory" //set bit defined by $port_bit
);

if($ret === true){
  $ret="Set OK\r\n";
}
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

//wait pulse lenght 1s
usleep ( 1000*1000 );

//clear bit
$ret=SharedMemory(
  $SHARED_MEMORY_KEY, //My mem key
  $SEMAPHORE_KEY_BTN, //Semaphore key
  "w", //read and write
  0, //don't care
  0, //don't care
  "PulseClear_MyMemory" //set bit defined by $port_bit
);

if($ret === true){
  $ret="Clear OK\r\n";
}
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

http_response_code(200);//OK



function PulseSet_MyMemory($shared_memory_array){
  global $port_bit;
  $shared_memory_array[0] |= $port_bit;
  return $shared_memory_array;//give back the modified array
}

function PulseClear_MyMemory($shared_memory_array){
  global $port_bit;
  $shared_memory_array[0] &= ~$port_bit;
  return $shared_memory_array;//give back the modified array
}


?>
