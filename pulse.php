<?php

include("lib.php");
include("/home/pi/config.php");
include("/home/pi/passcheck.php");

if( isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST["port"])){//call from ajax
  $port = $_POST["port"];
  $logfile = "pulse.log";
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
$outdata=file_get_contents($out_dat);
$byte=hexdec(substr($outdata,0,2));

$byte |= $port_bit;
$outdata = sprintf("%02X",$byte);
$ret = "Pulse up: ".$outdata."\n";
echo $ret;
file_put_contents($out_dat,$outdata);
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

//wait pulse lenght 250ms
usleep ( 250*1000 );

//clear bit
//$outdata=file_get_contents("out.dat");
//$byte=hexdec(substr($outdata,0,2));
$byte &= ~$port_bit;
$outdata = sprintf("%02X",$byte);
$ret = "Pulse up: ".$outdata."\n";
echo $ret;
file_put_contents($out_dat,$outdata);
if(isset($logfile))file_put_contents($logfile,$ret,FILE_APPEND);

http_response_code(200);//OK

?>
