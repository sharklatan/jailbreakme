











<center>
<h1>
JonaiPhone Bot
</h1>
© 2017-2018 Shark-Design 
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

$bot = new bot('661444225:AAHpvk2dP0sntGT05Cdkpc8x5O4XPjjvTuA');
$bot -> uploadfile = true;
//my id
$bot -> chatid = 15310317;
// group id
//$bot -> chatid = -1001092935707;
//Jona Grupo
//$bot -> chatid = -1001103910406; 



// load json content
$content = file_get_contents('test.json');
$new_hash = sha1($content.json);


// check if an update is available

$old_hash = is_file('json_hash') ? file_get_contents('json_hash') : '';
if($old_hash === $new_hash)
{
  return false;
}



// make response body
$content = json_decode($content);

$str = "<b> Hola a todos ^_^ soy JonaiPhone Bot.\n</b>\n<b>Suscribe te a mi canal para que no te pierdas ningunos de mis vídeos.\n</b>\n{$content -> firmwares -> version}\n";
$str .= "\n JONAIPHONE COMUNIDAD  📲\n\n{$content -> firmwares ->  version} {$content -> firmwares -> version} {$content -> firmwares -> version}\n\n";
foreach($content -> firmwares -> files as $file)
{
  $str .= " {$file -> id} {$file -> version} ". ($file -> signed ? '✅' : '❌') . " {$file -> buildid}\n {$file -> version}\n\n";
}
// send message to user/channel
$bot -> sendMessage($str, 'HTML');
switch($bot -> command)
{
  case 'rules':
//    $bot -> sendMessage("response 1");
    $bot -> sendMessage($str, 'HTML');
    // make response body
$content = json_decode($content);
$str = "<b> Suscribe te a mi canal para que no te pierdas ningunos de mis vídeos.</b>\n{$content -> firmwares -> version}\n";
$str .= "\n JONAIPHONE COMUNIDAD  📲\n{$content -> firmwares -> version} {$content -> firmwares -> version} {$content -> firmwares -> version}\n\n";
foreach($content -> firmwares -> firmwares as $file)
{
  $str .= " {$file -> firmwares} {$file -> firmwares} ". ($file -> signed ? '✅' : '❌') . " {$file -> firmwares}\n {$file -> veraion}\n\n";
}
  break;
  case 'test':
    $bot -> sendMessage("response 2");
  break;
  
}


// write new hash
file_put_contents('json_hash', $new_hash)
?>


