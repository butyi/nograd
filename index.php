<?php
session_start();//start session to be able to set session variable later
ob_start();//output buffer disable to send any output before setcookie
include("/home/pi/config.php");
include("/home/pi/passcheck.php");//include PasswordCheck definition
if( isset($_POST['logout']) ){
  unset($_SESSION['authorized']);
  setcookie("authorized", false, time()-1 );
  // Redirect page to itself with GET instead of POST, to prevent resend need
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();//no more task in this case
}
if( isset($_POST['login']) ){//if there is filled in login form
  $passcheckresult=PasswordCheck($_POST['login']);
  if(false !== $passcheckresult){//if password was correct
    $_SESSION['authorized']=true;//Set session
    setcookie("authorized", true, $passcheckresult );
  }
  // Redirect page to itself with GET instead of POST, to prevent resend need
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();//no more task in this case
}
//calculate variable which shows the access level
$authorized = ( ( isset( $_SESSION['authorized'] ) && $_SESSION['authorized'] == true ) ||
                ( isset( $_COOKIE['authorized'] ) && $_COOKIE['authorized'] == true ) );
include("lib.php");
if( clientInSameSubnet() )$authorized=true; //allow access for local users
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="author" content="B. János">
<link rel="shortcut icon" href="favicon.ico">
<link rel="stylesheet" type="text/css" href="index.css"/>
<?php if($authorized){ ?>
<script>
var key=<?php echo GetAjaxKey(); ?>;//key for ajax requests
var writedelay_timer=0;//to not write first change, wait for user to set the final value, and write when user seems finished the change
var update_timer=2;//update page every 1 min (if it is in focus)
var writetimeout_timer=0;//timeout function to close not successful write attempt
var tem_timerid;//Timed Error Message timer
var room_temp_demand;//the variable what is displayed and changed by user
var old_room_temp_demand;//value before change to apply relative limit
var disableUpdate = false;//just a fleg for the named function
var doc_was_focus = false;//previous state of hasFocus to detect the become focus event

var everysec_timerid = setInterval('every_sec()', 1000);//to print out time of last update

function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function every_sec(){
  var doc_has_focus = document.hasFocus();

  if(0<writedelay_timer){//if timer is runnung
    if(writedelay_timer==1){//this is the time to write new value
      AjaxWrite('room_temp_demand',room_temp_demand);
    }
    writedelay_timer--;
  }

  if(doc_has_focus == true && doc_was_focus == false){//now get focus
    update_timer=1;//force immediate update
  }
  if(0<update_timer){//if timer is runnung
    if(update_timer==1){//this is the time to update
      if(doc_has_focus)updatepage();//periodical update, only on focus
      update_timer=2;//restart timer
    }
    update_timer--;
  }

  if(0<writetimeout_timer){//if timer is runnung
    if(writetimeout_timer==1){//this is the timeout
      FinishWrite(false);//handle write timeout
    }
    writetimeout_timer--;
  }

  doc_was_focus = doc_has_focus;
}

function inc_room_temp_demand(){
  if(30<(room_temp_demand+1)){
    TimedErrorMessage('Absolute maximum is 30 &deg;C',5000);
    return;//absolute limit
  }
  disableUpdate=true;
  update_timer=0;//stop periodical update during change
  room_temp_demand++;
  document.getElementById('TempDemand').style.fontWeight = 'normal';
  document.getElementById('TempDemand').innerHTML=room_temp_demand;
  document.getElementById('ErrorMessage').style.display = 'none';//no error
  writedelay_timer=3;
}

function dec_room_temp_demand(){
  if((room_temp_demand-1)<0){
    TimedErrorMessage('Absolute minimum is 0 &deg;C',5000);
    return;//absolute limit
  }
  disableUpdate=true;
  update_timer=0;//stop periodical update during change
  room_temp_demand--;
  document.getElementById('TempDemand').style.fontWeight = 'normal';
  document.getElementById('TempDemand').innerHTML=room_temp_demand;
  document.getElementById('ErrorMessage').style.display = 'none';//no error
  writedelay_timer=3;
}

function TimedErrorMessage(message,time){//this informs the user about any error
  document.getElementById('ErrorMessage').innerHTML = message;
  document.getElementById('ErrorMessage').style.display = 'block';
  clearTimeout(tem_timerid);
  tem_timerid = setTimeout(function(){ document.getElementById('ErrorMessage').style.display = 'none'; },time);//hide error message after time ms
}

function FinishWrite(success){//finalize the write new value procedure
  disableUpdate=false;//write finished, enable update again
  document.getElementById('TempDemand').style.fontWeight = 'bold';
  update_timer=2;//restart periodical update
  document.getElementById('decBtn').disable = false;//button can be used again
  document.getElementById('incBtn').disable = false;//button can be used again
  document.getElementById('TempDemand').style.color='black';
  if(success==false){
    room_temp_demand = old_room_temp_demand;//restore original value
    document.getElementById('TempDemand').innerHTML=room_temp_demand;
    TimedErrorMessage('Write was not successfull.',5000);
  } else {
    old_room_temp_demand = room_temp_demand;//update the new value (already before updatepage())
  }
}

function AjaxWrite(parname,parvalue){//initiate the write new value procedure
  var xmlhttp;

  par='key='+key+'&parname='+parname+'&parvalue='+parvalue;

  if(window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
  }

  xmlhttp.onreadystatechange=function(){
    if(xmlhttp.readyState==4){
      writetimeout_timer=0;//cancel write timeout, since ajax request finished
      if(xmlhttp.status==200){//ok
        FinishWrite(true);
      } else {
        FinishWrite(false);
      }
    }
  }

  xmlhttp.open('POST','ajax_write.php',true);
  xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
  xmlhttp.send(par);
  document.getElementById('decBtn').disable = true;//do not play with buttons during writing
  document.getElementById('incBtn').disable = true;//do not play with buttons during writing
  document.getElementById('TempDemand').style.color='lightgray';//lightgray font color during write procedure
  writetimeout_timer=16;//set write timeout
}

function UpdateBtnColor(btnid,value){
  if(value=='Be'){
    document.getElementById(btnid).style.backgroundColor = 'yellow';
  }
  if(value=='Ki'){
    document.getElementById(btnid).style.backgroundColor = 'lightgray';
  }
}

function updatepage(){//read fresh values from server
  var xmlhttp;
  if(disableUpdate)return;//do not update while write is in progress

  if(window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
  }

  xmlhttp.onreadystatechange=function(){
    if(xmlhttp.readyState==4 && !disableUpdate ){
      if(xmlhttp.status==200){
        var pars = xmlhttp.responseText.split('|');
        if(isNumeric(pars[0])){
          room_temp_demand = Number(pars[0]);
          old_room_temp_demand = room_temp_demand;
        }
        document.getElementById('TempDemand').innerHTML=pars[0];
        document.getElementById('InTemp').innerHTML=pars[1];
        document.getElementById('OutTemp').innerHTML=pars[2];
        document.getElementById('HeaterState').innerHTML=pars[3];
        UpdateBtnColor('KitchenBtn',pars[4]);
        UpdateBtnColor('RoomBtn',pars[5]);
        UpdateBtnColor('ShowerBtn',pars[6]);
        UpdateBtnColor('TerraceBtn',pars[7]);
      } else {
        document.getElementById('TempDemand').innerHTML='-';
        document.getElementById('InTemp').innerHTML='-';
        document.getElementById('OutTemp').innerHTML='-';
        document.getElementById('HeaterState').innerHTML='-';
        document.getElementById('KitchenBtn').style.backgroundColor = 'red';
        document.getElementById('RoomBtn').style.backgroundColor = 'red';
        document.getElementById('ShowerBtn').style.backgroundColor = 'red';
        document.getElementById('TerraceBtn').style.backgroundColor = 'red';
      }
    }
  }

  xmlhttp.open('POST','ajax_read.php',true);
  xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
  xmlhttp.send('key='+key);
}

function Pulse(port,btnid){
  var xmlhttp;

  par='key='+key+'&port='+port;

  if(window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
  }

  xmlhttp.onreadystatechange=function(){
    if(xmlhttp.readyState==4){
      document.getElementById(btnid).disable = false;//from now on can be pushed again
      updatepage();
    }
  }

  xmlhttp.open('POST','pulse.php',true);
  xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
  xmlhttp.send(par);
  document.getElementById(btnid).disable = true;//do not play with buttons during writing
}


</script>
<?php } ?>
<title>Nógrád Almáskert</title>
</head>
<body>

<?php if(!$authorized){ ?>
<div class="title box">
  <h2>Nógrád Almáskert</h2>
</div>
<?php } ?>

<div style="overflow:auto">


<?php if($authorized){ ?>
  <div class="box1">
    <h2>Világításvezérlés</h2>
    <div class="center">
    <button onclick="Pulse(7,'KitchenBtn')" id="KitchenBtn" class="btn">Konyha</button>
    <button onclick="Pulse(6,'RoomBtn')" id="RoomBtn" class="btn">Szoba</button>
    <button onclick="Pulse(5,'ShowerBtn')" id="ShowerBtn" class="btn">Fürdő</button>
    <button onclick="Pulse(4,'TerraceBtn')" id="TerraceBtn" class="btn">Terasz</button>
    </div>
  </div>
<?php } ?>



<?php if($authorized){ ?>
  <div class="box2">
<?php } ?>
<?php if(!$authorized){ ?>
  <div class="box1">
<?php } ?>

<?php if(!$authorized){ ?>
    <h2>Hőmérséklet most</h2>( <?php echo date("Y.m.d H:i",time()); ?> )
<?php } ?>
<?php if($authorized){ ?>
    <h2>Fűtésvezérlés</h2>
    <div class="center">
    <span class="temp">Cél:</span> <span class="temp" id="TempDemand"><?php echo GetValueOf("room_temp_demand"); ?></span> <span class="temp">&deg;C</span>
    </div>
    <div class="center">
    <button onclick="dec_room_temp_demand()" id="decBtn" class="btn">-</button>
    <button onclick="inc_room_temp_demand()" id="incBtn" class="btn">+</button>
    </div>
<?php } ?>
    <div class="center">
<?php if($authorized){ ?>
    <span id="ErrorMessage"></span>
    <br/>
    <span class="temp">Bent:</span>
    <span class="temp" id="InTemp"><?php $val=GetValueOf("real_in_temp","time","DESC","value","1 DAY",true); if(is_numeric($val))echo round($val/1000,1); else echo $val; ?></span> <span class="temp">&deg;C</span>
<?php } ?>
    <br/>
<?php if($authorized){ ?>
      <span class="temp">Kint:</span>
<?php } ?>
      <span class="temp" id="OutTemp"><?php $val=GetValueOf("real_out_temp","time","DESC","value","1 DAY",true); if(is_numeric($val))echo round($val/1000,1); else echo $val; ?></span> <span class="temp">&deg;C</span>
<?php if($authorized){ ?>
    <br/>
    <span class="temp">Fűtés:</span> <span class="temp" id="HeaterState"><?php echo (GetValueOf("room_heater_state") ? "Be" : "Ki"); ?></span>
<?php } ?>
    </div>
  </div>



<?php if($authorized){ ?>
  <div class="box3">
<?php } ?>
<?php if(!$authorized){ ?>
  <div class="box2">
<?php } ?>
    <h2>Statisztika</h2>
<?php if($authorized){ ?>
    <h3>Belső hőmérséklet</h3>
    <table>
      <tr>
        <th>Leg-</th>
        <th>hidegebb</th>
        <th>melegebb</th>
      </tr>
      <tr>
        <td>Elmúlt nap</td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","ASC","time","1 DAY"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","ASC","value","1 DAY")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","DESC","time","1 DAY"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","DESC","value","1 DAY")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt hét</td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","ASC","time","1 WEEK"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","ASC","value","1 WEEK")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","DESC","time","1 WEEK"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","DESC","value","1 WEEK")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt hónap</td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","ASC","time","1 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","ASC","value","1 MONTH")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","DESC","time","1 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","DESC","value","1 MONTH")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt szezon</td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","ASC","time","6 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","ASC","value","6 MONTH")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_in_temp","value","DESC","time","6 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_in_temp","value","DESC","value","6 MONTH")/1000,1); ?> &deg;C</p></td>
      </tr>
    </table>
    <h3>Külső hőmérséklet</h3>
<?php } ?>
    <table>
      <tr>
        <th>Leg-</th>
        <th>hidegebb</th>
        <th>melegebb</th>
      </tr>
      <tr>
        <td>Elmúlt nap</td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","ASC","time","1 DAY"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","ASC","value","1 DAY")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","DESC","time","1 DAY"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","DESC","value","1 DAY")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt hét</td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","ASC","time","1 WEEK"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","ASC","value","1 WEEK")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","DESC","time","1 WEEK"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","DESC","value","1 WEEK")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt hónap</td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","ASC","time","1 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","ASC","value","1 MONTH")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","DESC","time","1 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","DESC","value","1 MONTH")/1000,1); ?> &deg;C</p></td>
      </tr>
      <tr>
        <td>Elmúlt szezon</td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","ASC","time","6 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","ASC","value","6 MONTH")/1000,1); ?> &deg;C</p></td>
        <td><p title="<?php echo GetValueOf("real_out_temp","value","DESC","time","6 MONTH"); ?>-kor"><?php echo round(GetValueOf("real_out_temp","value","DESC","value","6 MONTH")/1000,1); ?> &deg;C</p></td>
      </tr>
    </table>
<?php if($authorized){ ?>
    <h3>Fűtés</h3>
    <table>
      <tr>
        <th></th>
        <th>Hossza</th>
        <th>Ára [Ft]</th>
      </tr>
      <tr>
        <td>Elmúlt nap</td>
        <td><p><?php $stat=GetTimeLengthOfName("room_heater_state","1 DAY"); echo $stat["time"]; ?></p></td>
        <td><p><?php                                                         echo $stat["price"]; ?></p></td>
      </tr>
      <tr>
        <td>Elmúlt hét</td>
        <td><p><?php $stat=GetTimeLengthOfName("room_heater_state","1 WEEK"); echo $stat["time"]; ?></p></td>
        <td><p><?php                                                          echo $stat["price"]; ?></p></td>
      </tr>
      <tr>
        <td>Elmúlt hónap</td>
        <td><p><?php $stat=GetTimeLengthOfName("room_heater_state","1 MONTH"); echo $stat["time"]; ?></p></td>
        <td><p><?php                                                           echo $stat["price"]; ?></p></td>
      </tr>
      <tr>
        <td>Elmúlt szezon</td>
        <td><p><?php $stat=GetTimeLengthOfName("room_heater_state","6 MONTH"); echo $stat["time"]; ?></p></td>
        <td><p><?php                                                           echo $stat["price"]; ?></p></td>
      </tr>
    </table>
<?php } ?>
  </div>


<?php if(!$authorized){ ?>
  <div class="box3">
    <h2>Jó, de mi ez?</h2>
    <p class="text">
      Ez az oldal a felhasználói felülete egy épületfelügyeleti rendszernek, ami <a href="https://www.google.hu/maps/place/N%C3%B3gr%C3%A1d,+2642/@47.9007942,19.0103268,13z/data=!3m1!4b1!4m5!3m4!1s0x476a8492a3c17dcf:0x400c4290c1e52f0!8m2!3d47.9041031!4d19.0498504?dcr=0" target="_blank">Nógrádon</a> az Almáskert egyik házában üzemel.
      Egy <a href="https://hu.wikipedia.org/wiki/Raspberry_Pi" target="_blank">Raspberry Pi</a> <a href="https://www.raspbian.org/" target="_blank">Raspbian</a> linux-al méri két DS18B20 érzékelővel a külső és belső hőmérsékletet és vezérli a ház fűtését. A felület a <a href="https://freedns.afraid.org/" target="_blank">Free DNS</a> segítségével érhető el bárhonnan.
      <br/>Jogosult felhasználó ezen felületen távolról ellenőrizheti és állíthatja a kívánt belső hőmérsékletet. Továbbá a ház világításait is tudja innen vezérelni.
      <br/>A többiekkel a felület megosztja az aktuális külső hőmérsékletet és annak az elmult fél évi rekordjait, hátha hasznát veszik az almáskerti lakosok.
    </p>
  </div>
<?php } ?>

</div>
<div class="footer">
  <p>(c) <a href="http://butyi.hu/" target="_blank">Bütyi</a></p>
</div>
</body>
</html>
