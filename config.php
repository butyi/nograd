<?php

$dbhost = "xxxxxxxxx";
$dbuser = "xxxx";
$dbpass = "xxxxx";
$dbname = "nograd";

$ft_per_kwh = 30.;
$intervals_hu = array( 'év', 'hónap', 'nap', 'óra', 'perc', 'másodperc' );

$digins = array(
  array("dbname"=>"kitchen_lamp_state","bit"=>7),
  array("dbname"=>"room_lamp_state","bit"=>6),
  array("dbname"=>"shower_lamp_state","bit"=>5),
  array("dbname"=>"terrace_lamp_state","bit"=>4),
);

$load_powers = array( "room_heater_state"=>1.0 );



?>
