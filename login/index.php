<?php
session_start();//start session to be able to set session variable later
//calculate variable which shows the access level
$authorized = ( ( isset( $_SESSION['authorized'] ) && $_SESSION['authorized'] == true ) ||
                ( isset( $_COOKIE['authorized'] ) && $_COOKIE['authorized'] == true ) );
ob_end_clean();//drop out content of output buffer
include("../lib.php");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="shortcut icon" href="../favicon.ico">
<link rel="stylesheet" type="text/css" href="../index.css"/>
<title>Nógrád Belépés</title>
</head>
<body>

<div class="title">
  <h1>Nógrád Almáskert Belépés</h1>
</div>

  <div>
<?php if(!$authorized){ ?>
    <h2>Belépés</h2>
<?php } ?>
<?php if($authorized){ ?>
    <h2>Kilépés</h2>
<?php } ?>
      <form action="../" method="post">
<?php if(!$authorized){ ?>
        <input class="login" type="password" name="login" placeholder="Jelszó?"><br/>
        <input class="btn" type="submit" value="Belépés" style="float:none;">
<?php } ?>
<?php if($authorized){ ?>
        <input type="hidden" name="logout" value="logout">
        <input class="btn" type="submit" value="Kilépés" style="float:none;">
<?php } ?>
      </form> 
  </div>
<div class="footer">
  <p>(c) <a href="http://butyi.hu/" target="_blank">Bütyi</a></p>
</div>
</body>
</html>
