<?php


function GetValueOf($name,$orderby="time",$dir="DESC",$rettype="value",$timelimit=""){//ASC=min, DESC=max
  include "/home/pi/config.php";
  $ret=0;

  $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
  if($mysqli->connect_errno)die( "Valami baj van az adatbazissal (new mysqli)." ); 

  $orderbypattern="".$orderby." $dir";
  if($orderby==="value")$orderbypattern="cast(".$orderby." as signed) $dir";

  $timelimittxt="";
  if(strlen($timelimit))$timelimittxt="AND time > DATE_SUB( NOW( ) , INTERVAL $timelimit ) ";

  $sql="SELECT * FROM history WHERE name = '".$name."' $timelimittxt ORDER BY $orderbypattern LIMIT 1";
  //file_put_contents("sql.txt",$sql."\r\n",FILE_APPEND);
  //echo "<p>".$sql."<p>";
  
  if($result = $mysqli->query($sql)){
    if(1==$result->num_rows){
      if( $value_obj = $result->fetch_array(MYSQLI_ASSOC) ){
        $ret = $value_obj[$rettype];
      } else {
        die( "Valami baj van az adatbazissal. (fetch_array)" );
      }
    } else {
      die( "Valami baj van az adatbazissal. (num_rows != 1)" );
    }
  } else {
    die( "Valami baj van az adatbazissal. query($sql)" );
  }
  return $ret;
}

function NewValueOf($name,$newvalue,$min=false,$max=false){
  include "/home/pi/config.php";
  if( is_numeric($min) && intval($newvalue)<$min )return;//too low, do not store
  if( is_numeric($max) && $max<intval($newvalue) )return;//too high, do not store

  $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
  if($mysqli->connect_errno)die( "Valami baj van az adatbazissal (new mysqli)." ); 

  //parameter to be written only if value is different from the current (latest) value
  if(GetValueOf($name) == $newvalue)return false;

  $sql = 'INSERT INTO `'.$dbname.'`.`history` (`id`, `name`, `value`, `time`) VALUES (NULL, \''.$name.'\', \''.$newvalue.'\', NOW());'; 
  //echo "<p>".$sql."<p>";
  
  if($result = $mysqli->query($sql)){
    return true;
  } else {
    die( "Valami baj van az adatbazissal. query($sql)" );
  }
}


/**
 * Get human readable time difference between 2 dates
 *
 * Return difference between 2 dates in year, month, hour, minute or second
 * The $precision caps the number of time units used: for instance if
 * $time1 - $time2 = 3 days, 4 hours, 12 minutes, 5 seconds
 * - with precision = 1 : 3 days
 * - with precision = 2 : 3 days, 4 hours
 * - with precision = 3 : 3 days, 4 hours, 12 minutes
 * 
 * From: http://www.if-not-true-then-false.com/2010/php-calculate-real-differences-between-two-dates-or-timestamps/
 *
 * @param mixed $time1 a time (string or timestamp)
 * @param mixed $time2 a time (string or timestamp)
 * @return string time difference
 * 
 * Usage:
 *  $t  = '2013-12-29T00:43:11+00:00';
 *  $t2 = '2013-11-24 19:53:04 +0100';
 *  var_dump( get_date_diff_human( $t, $t2, 1 ) ); // string '1 month' (length=7)
 *  var_dump( get_date_diff_human( $t, $t2, 2 ) ); // string '1 month, 4 days' (length=15)
 *  var_dump( get_date_diff_human( $t, $t2, 3 ) ); // string '1 month, 4 days, 5 hours' (length=24)
 * 
 */
function get_date_diff_human( $time1, $time2 ) {
	global $intervals_hu;
	// If not numeric then convert timestamps
	if( !is_int( $time1 ) ) {
		$time1 = strtotime( $time1 );
	}
	if( !is_int( $time2 ) ) {
		$time2 = strtotime( $time2 );
	}

	// If time1 > time2 then swap the 2 values
	if( $time1 > $time2 ) {
		list( $time1, $time2 ) = array( $time2, $time1 );
	}

	// Set up intervals and diffs arrays
	$intervals = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );
	$diffs = array();

	foreach( $intervals as $interval ) {
		// Create temp time from time1 and interval
		$ttime = strtotime( '+1 ' . $interval, $time1 );
		// Set initial values
		$add = 1;
		$looped = 0;
		// Loop until temp time is smaller than time2
		while ( $time2 >= $ttime ) {
			// Create new temp time from time1 and interval
			$add++;
			$ttime = strtotime( "+" . $add . " " . $interval, $time1 );
			$looped++;
		}

		$time1 = strtotime( "+" . $looped . " " . $interval, $time1 );
		$diffs[ $interval ] = $looped;
	}

	$times = array();
	foreach( $diffs as $interval => $value ) {
		// Break if we have needed precission
		// Add value and interval if value is bigger than 0
		if( $value > 0 ) {
			// Add value and interval to times array
			$times[] = $value+1 . " " . $interval;
			break;
		}
	}
	if(!count($times))$times[] = "-";

	// Return string with times
	return str_replace( $intervals, $intervals_hu, $times[0] );
}

function GetTimeLengthOfName($name,$timelength){
  include("/home/pi/config.php");
  $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
  $sql="SELECT * FROM history WHERE name = '".$name."' AND time > DATE_SUB( NOW( ) , INTERVAL ".$timelength." ) ORDER BY time DESC";

  $on_time_sec = 0;
  if(!$mysqli->connect_errno){
    $result = $mysqli->query($sql);
    if(0<$result->num_rows){
      while( $a = $result->fetch_array(MYSQLI_ASSOC) ){
        $arr[]=$a;
      }
      $on_time_stamp=false;
      foreach(array_reverse($arr) as $item){
        if( $prev_item["value"]==0 && $item["value"]==1 ){//switch on
          $on_time_stamp = strtotime($item["time"]);
          //echo "On $on_time_stamp; ";
        }
        if( $prev_item["value"]==1 && $item["value"]==0 ){//switch off
          $off_time_stamp = strtotime($item["time"]);
          //echo "Off $off_time_stamp; ";
          if($on_time_stamp !== false){//was on time
            $length_in_sec = $off_time_stamp - $on_time_stamp;
            //echo "Length ".get_date_diff_human($off_time_stamp,$on_time_stamp, 6)."; ";
            $on_time_sec += $length_in_sec;
          }
          $on_time_stamp=false;
          //echo "\n";
        }
        $prev_item=$item;
      }
    }  
  }
  $ft_per_sec = $ft_per_kwh * $load_powers[$name] / 60. / 60.;
  $ft = (float)$on_time_sec * $ft_per_sec;
  if(0<$ft && $ft<=1)$ft=1;// below 1 Ft, it is 1 Ft
  if(1<$ft && $ft<1000)$ft=intval($ft); //between 1Ft and 1000Ft, only integer is printed 
  if(1000<=$ft && $ft<1)$ft=intval($ft/1000)."e"; //over 1000Ft, it is 1eFt
  return array("time"=>get_date_diff_human(0, $on_time_sec),"price"=>$ft );
}






?>
