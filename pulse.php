<?php

include("lib.php");
include("/home/pi/config.php");
include("/home/pi/passcheck.php");

//file_put_contents("_POST.arr",print_r($_POST,true));

if( isset($_POST["key"]) && KeyValid($_POST["key"]) && isset($_POST['port']) )
{
  $port = $_POST["port"];
  if(0<=$port && $port<=3){
    $port_bit= 1 << intval($port);

    //set bit
    SharedMemory(
      $SHARED_MEMORY_KEY, //My mem key
      $SEMAPHORE_KEY_BTN, //Semaphore key
      "w", //read and write
      0, //don't care
      0, //don't care
      "PulseSet_MyMemory" //set bit defined by $port_bit
    );

    //wait pulse lenght
    usleep ( 500*1000 );

    //clear bit
    SharedMemory(
      $SHARED_MEMORY_KEY, //My mem key
      $SEMAPHORE_KEY_BTN, //Semaphore key
      "w", //read and write
      0, //don't care
      0, //don't care
      "PulseClear_MyMemory" //set bit defined by $port_bit
    );

    http_response_code(200);//OK
  } else {
    http_response_code(400);//Bad Request
  }
} else {
  http_response_code(401);//Unauthorized
}


function PulseSet_MyMemory($shared_memory_array){
  global $port_bit;
  $shared_memory_array[0] |= $port_bit;
  return $shared_memory_array;//give back the modified array
}

function PulseClear_MyMemory($shared_memory_array){
  global $port_bit;
  $shared_memory_array[0] &= $port_bit;
  return $shared_memory_array;//give back the modified array
}


?>
