











<center>
<h1>
JonaiPhone Bot
</h1>
© 2017-2018 Shark-Design 
</center>
<br><br><br>


<?php

ini_set('date.timezone', 'Asia/Calcutta');
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 0);
define('ROOT', __DIR__.DIRECTORY_SEPARATOR);
$ROOT = __DIR__;
ini_set('error_log', ROOT.'error_log');


require('bot.php');

$bot = new bot('674165945:AAGSB3fpt1bN2kho_rHHUMORUpeIg0eKzD0');
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
$str = "<b> Suscribe te a mi canal para que no te pierdas ningunos de mis vídeos.</b>\n{$content -> Client -> upload}\n";
$str .= "\n JONAIPHONE COMUNIDAD  📲\n{$content -> Client -> category} {$content -> Client -> type} {$content -> Client -> changes}\n\n";
foreach($content -> Client -> files as $file)
{
  $str .= " {$file -> id} {$file -> description} ". ($file -> tested ? '✅' : '❌') . " {$file -> update}\n {$file -> comment}\n\n";
}
// send message to user/channel
switch($bot -> command)
{
  case 'rules':
//    $bot -> sendMessage("response 1");
    $bot -> sendMessage($str, 'HTML');
  break;
  case 'test':
    $bot -> sendMessage("response 2");
  break;
  
}

//$bot -> sendMessage($str, 'HTML');
// write new hash
file_put_contents('json_hash', $new_hash)
?>


