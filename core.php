<?php
function intervalcheck($f, $interval = 0)
{
	$root = 'preferences/intervalcheck/';
	mkpath($root);
	$lcheck = is_file($root.$f) ? file_get_contents($root.$f) : 0;
  //$lcheck = file_get_contents($root.$f);
  settype($lcheck, 'integer');
  if(time() - $lcheck > $interval)
  {
    file_put_contents($root.$f, time());
    return true;
  }
  else
  {
    return false;
  }
}

function str_addand($array)
{
  $n = count($array);
  {
    switch($n)
    {
      case 1:
      return (string)$array[0];
      break;

      case 2:
      //default can do this too
      return "{$array[0]} and {$array[1]}";
      break;

      default:
      {
        $i = 1;
        $str = '';
        foreach($array as $item)
        {
          if($i != $n-1)
          {//until it's not the last element, keep adding commas and spaces
            $str .= "$item, ";
          }
          else
          {//if it's the last element, add last two elements with an 'and' between them and  return
            $str .= "$item and {$array[$n-1]}";
            return $str;
          }
          $i++;
        }
      }
      break;
    }
  }
}

function mkpath($path)
{//create path
  if(preg_match_all("/(.+?)\//", $path, $k))
  {
    $path = '';
    foreach($k[1] as $dir)
    {
      $path .= "$dir/";
      if(!is_dir($path))
      {
        mkdir($path);
      }
    }

  }
}

function db($path, $action = 'read', $data = null)
{
  switch($action)
  {
    case 'read':
    {
      if(!is_file($path))
      {
				if($action === true)
				{
					$data = array();
				}
				else
				{
					$data = new stdClass();
				}
        return $data;
      }
			if(is_bool($action))
			{
				$out = json_decode(file_get_contents($path), $action);
				return is_null($out) ? $action ? array() : new stdClass() : $out;
			}
      else if(is_bool($data))
      {
				$out = json_decode(file_get_contents($path), $data);
				return is_null($data) ? $action ? array() : new stdClass() : $data;
      }
			else
			{
				return json_decode(file_get_contents($path));
			}
    }
    break;

    case 'write':
    {
      mkpath($path);
      if(!isset($data))
      {
        return false;
      }
      else
      {
        if(is_string($data))
        {
          if(!is_object(json_decode($data)))
          {
            return false;
          }
          else
          {
            file_put_contents($path, $data);
            return true;
          }
        }
        else if((is_object($data)) || (is_array($data)))
        {
          file_put_contents($path, json_encode($data));
          return true;
        }

      }
    }
    break;

    case 'append':
    {
			if(!is_array($data) || !is_object($data))
			{
				$data = [$data];
			}
      if(!is_file($path))
      {
        return db($path, 'write', $data);
      }
			echo 'activated';
      if(is_array($data))
      {
        $raw = db($path);
        foreach ($data as $key => $value)
        {
          if(is_integer($key))
          {
            array_push($raw, $value);
          }
          else if(is_string($key))
          {
            $raw -> $key = $value;
          }
        }
        db($path, 'write', $raw);
        return true;
      }
    }
    break;

    case 'remove':
    {
      $raw = db($path);
      if(is_integer($data))
      {
        unset($raw[$data]);
        $raw = array_values($arr);
        db($path, 'write', $raw);
        return true;
      }
      else if(is_string($data))
      {
        unset($raw -> $data);
        db($path, 'write', $raw);
        return true;
      }
      else if(is_array($data))
      {
        foreach($data as $key => $value)
        {
          unset($raw -> $value);
        }
        db($path, 'write', $raw);
        return true;
      }
    }
    break;

    default:
      return false;
    break;

  }
}

function curlget($url, $headers = null, $post = null)
{ //param1 -> url for cURL, param2 -> pass array to be used as header
	$handle = curl_init();
	curl_setopt($handle, CURLOPT_URL, $url);
  isset($headers) ? curl_setopt($handle, CURLOPT_HTTPHEADER, $headers) : null;//if header params are passed, use them
	isset($post) ? curl_setopt($handle, CURLOPT_POSTFIELDS, $post) : null;//if post params are passed, use them
	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	echo curl_error($handle);
	$response = curl_exec($handle);
	curl_close($handle);
	$json = json_decode($response);
  if(is_object($json))
  {//if the response can be decoded as JSON seralized obj, return it
    return $json;
  }
  else
  {//if it's not an obj, return plain markup
    return $response;
  }
}

function random($arr)
{
	if(!is_array($arr)){
		die('array expected');
	}
	$selected = $arr[array_rand($arr)];
	$selected[0] = strtoupper($selected[0]);
	return $selected;
}

function smartcaps($string)
{
	$string = mb_strtolower($string);
  $string[0] = mb_strtoupper($string[0]);
  return $string;
}//changue $period = 6000 to $period = 1000

function cache_broker($path ,$file, $data = null, $period = 6000, $as_array = false)
{
  $time = time();
  $path = isset($path) ? $path : "botdata/cache/assorted/";
  //make directory tree
  $file = strtoupper(hash('sha1', $file));
  $path .= substr($file, 0, 2).'/';
  $path .= substr($file, 2, 2)."/$file";
  if(!isset($data))
  {
    //function is called for retrieving cached data
    if(!is_file($path))
    {
      echo 'no cache file<br/>';
      return false;
    }
    if($time - filemtime($path) > $period)
    {
      echo 'cache expired<br/>';
      //checking if cache expired
      return false;
    }
    echo "loading cache $path<br/>";
		$rdata = json_decode(file_get_contents($path), $as_array);
    return (is_object($rdata) || is_array($rdata)) ? $rdata : file_get_contents($path);
  }
	var_dump($path);
	mkpath($path);
  if(!is_string($data))
  {
    //convert to string if it already isn't a string
    $data = json_encode($data);
  }
  echo "writing cache $path<br/>";
  file_put_contents($path, $data);
}

?>
