

<br><br><br><br><br>
<b>DECODE</b><br><br><br>


<?php

$json = file_get_contents('file.txt');

var_dump(json_decode($json));



?>
<br><br><br>


<?php

$json = file_get_contents('file.txt');


var_dump(json_decode($json, true));


?>


<br><br><br><br><br>
<b>ENCODE</b><br><br><br>


<?php



?>


<br><br><br><br><br>
<b>FIX Array</b><br><br><br>

<?php



function fixArrayKey(&$arr)
{
    $arr = array_combine(
        array_map(
            function ($str) {
                return str_replace(" ", "_", $str);
            },
            array_keys($arr)
        ),
        array_values($arr)
    );

    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            fixArrayKey($arr[$key]);
        }
    }
}

$content = json_decode($data);
$data = file_get_contents('file.txt');

print_r($data);
fixArrayKey($data);
print_r($data);

json_last_error()


?>

<br><br><br><br><br>
<b>STRUCTURE</b><br><br><br>

<?php


$headers = array('http'=>array('method'=>'GET','header'=>'Content: type=application/json \r\n'.'$agent \r\n'.'$hash'));

$context=stream_context_create($headers);

$str = file_get_contents("file.txt",FILE_USE_INCLUDE_PATH,$context);

$str1=utf8_encode($str);

$str1=json_decode($str1,true);



foreach($str1 as $key=>$value)
{

    echo "key is: $key.\n";

    echo "values are: \t";
        foreach ($value as $k) {

        echo " $k. \t";
        # code...
    }
        echo "<br></br>";
        echo "\n";

}



?>

<br><br><br><br><br>

<?php

$json = file_get_contents('file.txt');
//$json = '{"foo-bar": 12345}';

$obj = json_decode($json);
print $obj->{'model'}; // 12345

?>


