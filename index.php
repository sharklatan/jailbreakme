











<center>
<h1>
JailBreakMe Bot
</h1>
© 2017 Shark-Design 
</center>
<br><br><br>


<?php

ini_set('date.timezone', 'Asia/Calcutta');
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
define('ROOT', __DIR__.DIRECTORY_SEPARATOR);
$ROOT = __DIR__;
ini_set('error_log', ROOT.'error_log');


require('bot.php');

$bot = new bot('373936379:AAGbYbnKHWzVLLoTOtBdkphB_a6GeROHHjE');
$bot -> uploadfile = true;
$bot -> chatid = 15310317;
//$bot -> chatid = -1001092935707;


// load json content
$content = file_get_contents('content.json');
$new_hash = sha1($content.json);


// check if an update is available

$old_hash = is_file('json_hash') ? file_get_contents('json_hash') : '';
if($old_hash === $new_hash)
{
  return false;
}



// make response body
$content = json_decode($content);
$str = "<b>iOS Signing</b>\n\n";
$str .= "";
foreach($content -> iPhone7 -> firmwares as $file)
{
  $str .= " {$file -> version} ". ($file -> signing ? '✅' : '❌') . " {$file -> stopped}\n\n";
}
// send message to user/channel
switch($bot -> command)
{
  case '/version':
    $bot -> sendMessage($str, 'HTML');
  break;
  case '/test':
    $bot -> sendMessage("response 2");
  break;
  
}

//$bot -> sendMessage($str, 'HTML');
// write new hash
file_put_contents('json_hash', $new_hash)
?>


