<?php

include("lib.php");
include("/home/pi/config.php");
include("/home/pi/passcheck.php");

//file_put_contents("_POST.arr",print_r($_POST,true));

if( isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST['port']) )
{
  $port = $_POST["port"];
  if(0<=$port && $port<=3){
    //calculate address and bitmask
    $port_bit= 1 << intval($port);
    $address_dir=0x00;
    $address_latch=0x14;

    //set all 8 bits as output
    $call="/usr/sbin/i2cset -y 0 0x20 0x".sprintf("%02X",$address_dir)." 0x00";
    //$debug= "call: '$call'";
    exec($call);

    //read latch register current state
    $call="/usr/sbin/i2cget -y 0 0x20 0x".sprintf("%02X",$address_latch);
    //$debug= "call: '$call'";
    unset($ret);
    exec($call,$ret);
    $byte=hexdec(substr($ret[0],2));

    //set the bit and write
    $byte |= $port_bit;
    $call="/usr/sbin/i2cset -y 0 0x20 0x".sprintf("%02X",$address_latch)." 0x".sprintf("%02X",$byte);
    //$debug= "call: '$call'";
    exec($call);

    //wait pulse lenght
    usleep ( 3000*1000 );

    //clear the bit and write
    $byte &= ~$port_bit;
    $call="/usr/sbin/i2cset -y 0 0x20 0x".sprintf("%02X",$address_latch)." 0x".sprintf("%02X",$byte);
    //$debug= "call: '$call'";
    exec($call);

    http_response_code(200);//OK
  } else {
    http_response_code(400);//Bad Request
  }
} else {
  http_response_code(401);//Unauthorized
}

//file_put_contents("_POST.arr",$debug);


//read: i2cget -y 1 0x20 0x12

?>
