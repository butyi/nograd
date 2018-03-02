<?php

include("lib.php");
include("/home/pi/config.php");
include("/home/pi/passcheck.php");

//file_put_contents("_POST.arr",print_r($_POST,true));

if( isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST['port']) )
{
  $port = $_POST["port"];
  if(0<=$port && $port<=15){
    $port_bit= 1 << intval($port % 8);
    $address=0x00 + intval($port / 8);
    $call="/usr/sbin/i2cset -y 1 0x20 0x".sprintf("%02X",$address)." 0x00";//set all 8 bits as output
    //echo "<br/>call: '$call'";
    exec($call);
    $address=0x14 + intval($port / 8);
    $call="/usr/sbin/i2cset -y 1 0x20 0x".sprintf("%02X",$address)." 0x".sprintf("%02X",$port_bit);
    //echo "<br/>call: '$call'";
    exec($call);
    usleep ( 500*1000 );
    $call="/usr/sbin/i2cset -y 1 0x20 0x".sprintf("%02X",$address)." 0x00";
    //echo "<br/>call: '$call'";
    exec($call);

    http_response_code(200);//OK    
  } else {
    http_response_code(400);//Bad Request
  }
} else {
  http_response_code(401);//Unauthorized
}

//read: i2cget -y 1 0x20 0x12

?>
