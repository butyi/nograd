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

//set bit
$ret = "Pulse up port ".$port.PHP_EOL;
file_put_contents($out_dat.$port,'1');
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

//wait pulse lenght 250ms
usleep ( 250*1000 );

//clear bit
$ret = "Pulse down port ".$port.PHP_EOL;
file_put_contents($out_dat.$port,'0');
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

http_response_code(200);//OK

?>
