<?php

require_once('core.php');
class bot
{
  function __construct($token)
  {

    $this -> session = hash('md5', microtime().openssl_random_pseudo_bytes(6));
    $this -> api_sessions = db('botdata/api_sessions', true);
    $this -> max_api_calls = 10;
    $this -> api_sessions{$this -> session} = array(
      'init_time' => time(),
      'api_calls' => 0,
      'methods' => array()
    );
    foreach($this -> api_sessions as $session => $sesstion_data)
    {
      if((time() - $sesstion_data['init_time']) > 900)
      {
        var_dump("$session unset because of age");
        unset($this -> api_sessions{$session});
      }
    }
    db('botdata/api_sessions', 'write', $this -> api_sessions);
    $this -> raw_update = file_get_contents('php://input');
    /*if(strlen($this -> update) <= 0)
    {
      die("<body bgcolor = 'black'>
    	<font face = 'lucida console' color = #28ff00 size = '4'>
    	<br/>
    	Update data not provided
    	</font>"
      );
    }*/
    $this -> token = $token;
    $hash = hash('sha1', $token);
    if((intervalcheck('getme', 86400)) || !file_exists("botdata/$hash/metadata"))
    {
      $botdata = $this -> api('getMe') -> result;
      db("botdata/$hash/metadata", 'write', $botdata);
    }
    else
    {
      $botdata = db("botdata/$hash/metadata",'read');
    }
    $this -> botname = $botdata -> first_name;
    $this -> botuname = '@'.$botdata -> username;
    $this -> botid = $botdata -> id;
    $this -> boturl = "http://telegram.me/{$botdata -> username}";
    //$this -> error = new stdClass();
    $this -> is_error = false;
    $this -> error_code = 0;
    $this -> error_message = null;
    $this -> command = null;
    $this -> uploadfile = false;
    $this -> handle_api_errors = true;
    $this -> api_debug_level = 1;
    $this -> max_api_calls = 5;
    $this -> params['override'] = false;
  }

  public function parse($update = null)
  {
    if(isset($update))
    {
      $this -> raw_update = $update;
    }
    $this -> update = json_decode($this -> raw_update);
    $this -> updateid = $this -> update -> update_id;
    $this -> updatetype = array_keys((array)$this -> update)[1];



    switch($this -> updatetype)
    {
      case 'edited_message':
        /*//we'll just rename the edited_message property because the contents are the same
        $this -> update -> message = $this -> update -> edited_message;
        unset($this -> update -> edited_message);
        $this -> parse_message();
        var_dump($this -> updatetype);
      break;*/
      case 'message':
        $this -> parse_message();
      break;

      case 'callback_query':
        $this -> update -> message = $this -> update -> callback_query -> message;
        $this -> parse_message();
        unset($this -> update -> message);
        $this -> parse_callback();
      break;

      case 'inline_query':
        $this -> messageid = $this -> update -> inline_query -> id;
        $this -> userid = $this -> update -> inline_query -> from -> id;
        $this -> fname = $this -> update -> inline_query -> from -> first_name;
        isset($this -> update -> inline_query -> from -> username) ? $this -> username = $this -> update -> inline_query -> from -> username : null;
        $this -> name = isset($this -> update -> inline_query -> from -> username) ? $this -> update -> inline_query -> from -> username : $this -> update -> inline_query -> from -> first_name;
        $this -> query = $this -> update -> inline_query -> query;
        $this -> offset = $this -> update -> inline_query -> offset;
      break;
    }
  }

  private function parse_message()
  {
    if($this -> updatetype === 'edited_message')
    {
      //temporarily rename the property for processing edited messages
      $this -> update -> message = $this -> update -> edited_message;
      unset($this -> update -> edited_message);
    }
    $this -> updateid = $this -> update -> update_id;
    $this -> messageid = $this -> update -> message -> message_id;
    $this -> userid = $this -> update -> message -> from -> id;
    $this -> name = isset($this -> update -> message -> from -> username) ? '@'.$this -> update -> message -> from -> username : $this -> update -> message -> from -> first_name;
    isset($this -> update -> message -> from -> username)? $this -> username = '@'.$this -> update -> message -> from -> username : null;
    $this -> fname = $this -> update -> message -> from -> first_name;
    isset($this -> update -> message -> from -> last_name) ? $this -> lname = $this -> update -> message -> from -> last_name : null;
    $this -> chatid = $this -> update -> message -> chat -> id;
    $this -> chattype = $this -> update -> message -> chat -> type;
    $this -> time = $this -> update -> message -> date;
    $this -> replytome = false;

    if(($this -> chattype == 'group') || ($this -> chattype == 'supergroup'))
    {
      $this -> domain = 'group';
      $this -> grouptitle = $this -> update -> message -> chat -> title;
      $this -> groupchatid = $this -> update -> message -> chat -> id;
    }

    else if($this -> chattype == 'private')
    {
      $this -> domain = 'PM';
      $this -> chatid = $this -> update -> message -> chat -> id;
      $this -> chatfname = $this -> update -> message -> chat -> first_name;
      isset($this -> update -> message -> chat -> last_name) ? $this -> chatlname = $this -> update -> message -> chat -> last_name : null;
    }
    $messagetypes = array(
      'migrate_to_chat_id',
      'new_chat_participant',
      'left_chat_participant',
      'new_chat_title',
      'new_chat_photo',
      'group_chat_created',
      'supergroup_chat_created',
      'channel_chat_created',
      'photo',
      'voice',
      'audio',
      'video',
      'document',
      'contact',
      'location',
      'sticker',
      'text'
    );
    $keys = array_keys((array)$this -> update -> message);
    $this -> messagetype = current(array_intersect($keys, $messagetypes));
    if(gettype($this -> messagetype) == 'array' || strlen($this -> messagetype) < 2)
    {
      $this -> messagetype = in_array('forward_from', $keys) ? $this -> messagetype = $keys[6] : $this -> messagetype = $keys[4];
    }
    if(in_array('reply_to_message', $keys))
    {
      $this -> isreply = true;
      $this -> reply = $this -> update -> message -> reply_to_message;
      $this -> replytomessageid = $this -> update -> message -> reply_to_message -> message_id;
      $this -> replytomessagefromid = $this -> update -> message -> reply_to_message -> from -> id;
      $this -> replytomessagefromfname = $this -> update -> message -> reply_to_message -> from -> first_name;
      isset($this -> update -> message -> reply_to_message -> from -> last_name) ? $this -> replytomessagefromlname = $this -> update -> message -> reply_to_message -> from -> last_name : null;
      isset($this -> update -> message -> reply_to_message -> from -> username) ? $this -> replytomessagefromusername = '@'.$this -> update -> message -> reply_to_message -> from -> username : null;
      $this -> replytome = $this -> replytomessagefromid == $this -> botid ? true : false;
    }
    else
    {
      $this -> isreply = false;
    }
    if(in_array('forward_date', $keys))
    {
      $this -> isforwarded = true;
      if(isset($this -> update -> message -> forward_from))
      {
        $key = 'forward_from';
        $this -> forwardfromfname = $this -> update -> message -> $key -> first_name;
        isset($this -> update -> message -> $key -> last_name) ? $this -> forwardfromlname = $this -> update -> message -> $key -> last_name : null;
      }
      else
      {
        $key = 'forward_from_chat';
        $this -> forwardfromtitle = $this -> update -> message -> $key -> title;
        $this -> forwardfromtchannel = $this -> update -> message -> $key -> type == 'channel' ? true : false;

      }
      isset($this -> update -> message -> $key -> username) ? $this -> forwardfromuname = $this -> update -> message -> $key -> username : null;
      $this -> forwardfromid = $this -> update -> message -> $key -> id;
    }
    else
    {
      $this -> isforwarded = false;
    }

    /*switch($this -> messagetype)
    {
      case 'reply_to_message':
      break;

      case 'forward_from':
      break;
    }*/

    switch($this -> messagetype)
    {
      case 'migrate_to_chat_id':
        $this -> newchatid = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'new_chat_participant':
      case 'left_chat_participant':
        $this -> memberid = $this -> update -> message -> {$this-> messagetype} -> id;
        $this -> membername = $this -> update -> message -> {$this-> messagetype} -> first_name;
        if(isset($this -> update -> message -> {$this-> messagetype} -> username))
        {
          $this -> memberuname = '@'.$this -> update -> message -> {$this-> messagetype} -> username;
        }
      break;

      case 'new_chat_title':
        $this -> newtitle = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'new_chat_photo':
        $this -> newchatphoto = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'group_chat_created':
        $this -> newgroup = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'supergroup_chat_created':
        $this -> newgroup = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'channel_chat_created':
        $this -> newchannel = $this -> update -> message -> {$this -> messagetype};
      break;

      case 'photo':
        $this -> fileid = $this -> update -> message -> photo[count($this -> update -> message -> photo) -1] -> file_id;
        $this -> filesize = $this -> update -> message -> photo[count($this -> update -> message -> photo) -1] -> file_size;
        $this -> height = $this -> update -> message -> photo[count($this -> update -> message -> photo) -1] -> height;
        $this -> width = $this -> update -> message -> photo[count($this -> update -> message -> photo) -1] -> width;
        isset($this -> update -> message -> caption) ? $this -> caption = $this -> update -> message -> caption : null;
      break;

      case 'voice':
        $this -> media = $this -> update -> message -> {$this -> messagetype};
        $this -> fileid = $this -> media -> file_id;
        $this -> filesize = $this -> media -> file_size;
      break;

      case 'audio':
        $this -> media = $this -> update -> message -> {$this -> messagetype};
        $this -> mediaduration = $this -> media -> duration;
        $this -> fileid = $this -> media -> file_id;
        $this -> filesize = $this -> media -> file_size;
      break;

      case 'video':
        $this -> media = $this -> update -> message -> {$this -> messagetype};
        $this -> mediaduration = $this -> media -> duration;
        $this -> fileid = $this -> media -> file_id;
        $this -> filesize = $this -> media -> file_size;
        $this -> width = $this -> media -> width;
        $this -> height = $this -> media -> height;
        $this -> videothumbnail = $this -> media -> thumb;
      break;

      case 'document':
        $this -> media = $this -> update -> message -> {$this -> messagetype};
        $this -> fileid = $this -> media -> file_id;
        $this -> filesize = $this -> media -> file_size;
        $this -> filename = $this -> media -> file_name;
      break;

      case 'sticker':
        $this -> media = $this -> update -> message -> {$this -> messagetype};
        $this -> fileid = $this -> media -> file_id;
        $this -> filesize = $this -> media -> file_size;
        $this -> stickerthumbnail = $this -> media -> thumb;
        $this -> height = $this -> media -> height;
        $this -> width = $this -> media -> width;
        $this -> emoji = $this -> media -> emoji;
        $this -> sticker = $this -> media -> file_id;
      break;

      case 'contact':
        $this -> contact = $this -> update -> message -> {$this -> messagetype};
        $this -> contactfname = $this -> contact -> first_name;
        isset($this -> contact -> last_name) ? $this -> contactlname = $this -> contact -> last_name : null;
        $this -> contactnumber = $this -> contact -> phone_number;
      break;

      case 'location':
        $this -> longitude = $this -> update -> message -> {$this -> messagetype} -> longitude;
        $this -> latitude = $this -> update -> message -> {$this -> messagetype} -> latitude;
      break;

      case 'text':
        $this -> message = $this -> update -> message -> text;
        $this -> messagetome = true;
        if($this -> domain == 'group')
        {
          if(preg_match("/({$this -> botuname}|\/.+)/i", $this -> message, $k))
          {
            $this -> messagetome = true;
          }
          else
          {
            $this -> messagetome = false;
          }
        }
      break;


    }
    {
      if($this -> messagetype == 'text')
      {
        if(strpos($this -> message, $this -> botuname) !== false)
        {
          $this -> botmention = true;
          $this -> message = trim(str_replace($this -> botuname, '', $this -> message));
        }
        else
        {
          $this -> botmention = false;
        }
      }
    }
    if($this -> updatetype === 'edited_message')
    {
      //temporarily rename the property for processing edited messages
      $this -> update -> edited_message = $this -> update -> message;
      unset($this -> update -> message);
    }
  }

  private function parse_callback()
  {
    $this -> messagetype = 'callback_query';
    $this -> callbackid = $this -> update -> callback_query -> id;
    $this -> messageid = isset($this -> update -> callback_query -> inline_message_id) ? $this -> update -> callback_query -> inline_message_id : $this -> update -> callback_query -> message -> message_id;
    isset($this -> update -> callback_query -> message -> chat -> id) ? $this -> chatid = $this -> update -> callback_query -> message -> chat -> id : null;
    isset($this -> update -> callback_query -> message -> chat -> type) ? $this -> chattype = $this -> update -> callback_query -> message -> chat -> type : null;
    $this -> userid = $this -> update -> callback_query -> from -> id;
    $this -> fname = $this -> update -> callback_query -> from -> first_name;
    isset($this -> update -> callback_query -> from -> last_name) ? $this -> lname = $this -> update -> callback_query -> from -> last_name : null;
    if(isset($this -> update -> callback_query -> from -> username))
    {
      $this -> username = '@'.$this -> update -> callback_query -> from -> username;
    }
    else
    {
      if(isset($this -> username))
      {
        unset($this -> username);
      }
    }
    $this -> name = isset($this -> update -> callback_query -> from -> username) ? '@'.$this -> update -> callback_query -> from -> username : $this -> update -> callback_query -> from -> first_name;
    isset($this -> update -> callback_query -> message -> date) ? $this -> time = $this -> update -> callback_query -> message -> date : null;
    isset($this -> update -> callback_query -> message) ? $this -> contextmessage = $this -> update -> callback_query -> message -> {array_keys((array)$this -> update -> callback_query -> message)[4]} : null;
    $this -> callbackdata  = $this -> update -> callback_query -> data;
    isset($this -> update -> callback_query -> inline_message_id) ? $this -> inline_message_id = $this -> update -> callback_query -> inline_message_id : null;
    if(isset($this -> chattype))
    {
      if(($this -> chattype == 'group') || ($this -> chattype == 'supergroup'))
      {
        $this -> domain = 'group';
        $this -> grouptitle = $this -> update -> callback_query -> message -> chat -> title;
      }
      else
      {
        $this -> domain = 'PM';
      }
    }
  }

  public function api($method, $params = null)
  {
    $this -> last_method = $method;
    if(!isset($this -> session))
    {
      die('Illegal api handler call; initiate a session id first');
    }

    if($this -> api_sessions{$this -> session}{'api_calls'} >= $this -> max_api_calls)
    {
      die('API rate limit exceeded');
    }

    if(isset($params))
    {
      foreach ($params as $key => $value)
      {//add params from method-functions to global params
        if($this -> params['override'])
        {
          if(isset($this -> params['override']))
          {//if override is set, do not adopt from method functions(prefer params from params function)
            if((isset($this -> params[$key])))
            {
              continue;
            }
          }
        }
        //below line is a workaround for cURL throwing an error for sending post data with @ as the first char in a post-field
        $value = is_string($value) ? $value[0] == '@' ? $value = ' '.$value : $value : $value;

        $this -> params[$key] = $value;
      }
      var_dump($this -> params);
    }

    $url = "https://api.telegram.org/bot" . $this -> token . "/$method";
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    isset($this -> params) ? curl_setopt($handle, CURLOPT_POSTFIELDS, $this -> params) : null;
    $response = curl_exec($handle);
    $this -> api_sessions{$this -> session}{'api_calls'}++;
    echo curl_error($handle);
  	curl_close($handle);
  	$json = json_decode($response);
    var_dump($json);

  	if(is_object($json))
  	{//if the response can be decoded as JSON seralized obj, return it
      $this -> is_error = $json -> ok;
      if(!$json -> ok)
      {
        $this -> error_code = $json -> error_code;
        isset($json -> description) ? $this -> error_message = $json -> description : null;
        if($this -> handle_api_errors)
        {
          $this -> handle_api_error();
        }
      }
      if(isset($this -> params))
      {
        unset($this -> params);
        $this -> params['override'] = false;
      }
  		return $json;
  	}
  	else
  	{//if it's not an obj, return plain markup
  		return $response;
  	}
  }


  public function getMe($token = null)
  {
    if(!isset($token))
    {
      $token = $this -> token;
    }
    return $this -> api('getMe');

  }


  public function sendMessage($text, $mode = null, $preview = false)
  {
    return $this -> api('sendMessage', array(
      'text' => $text,
      'chat_id' => $this -> chatid,
      'parse_mode' => $mode,
      'disable_web_page_preview' => $preview
    ));
  }

  public function reply($text = true, $id = null)
  {
    if(is_string($text))
    {
      return $this -> api('sendMessage', array('text' => $text, 'chat_id' => $this -> chatid, 'reply_to_message_id' => (isset($id) ? $id : $this -> messageid)));
    }
    else if(is_bool($text))
    {
      if(isset($id) && $text === true)
      {
        $this -> params['reply_to_message_id'] = $id;
        return true;
      }
      else
      {
        $this -> params['reply_to_message_id'] = $this -> messageid;
        return true;
      }
    }

  }



  private function handle_api_error()
  {
    //avoid looping of methods
    $called = -1;
    $x = debug_backtrace();
    foreach($x as $y)
    {
      if($y['function'] == 'handle_api_error')
      {
        if(++$called > $this -> api_debug_level)
        {
          return false;
        }
      }
    }
    switch($this -> error_code)
    {
      case 400:
      {
        switch($this -> error_message)
        {
          case 'MEDIA_CAPTION_TOO_LONG':
          {
            if(isset($this -> params{'caption'}))
            {
              $this -> params{'caption'} = substr($this -> params{'caption'}, 0, 200);
              return $this -> api($this -> last_method, $this -> params);
            }
            die(FLAG);
          }
          break;
        }
      }
      break;

      case 403:
      {
        switch($this -> error_message)
        {
          case 'Bad Request: chat not found':
          break;
          default:
          {
            if($this -> domain == 'group')
            {
              $this -> chatid = $this -> groupchatid;
              $this -> reply();
              $this -> ask_to_init_pm_chat();
              die();
            }
          }
          break;
        }

      }
      break;

      default:
      error_log($this -> error_message);
      break;
    }

  }

}




?>