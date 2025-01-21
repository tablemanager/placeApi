<?php
require_once ('./mcrypt.php');

$enstr = "rFP5DtOV/5LE/rOc3EPqug==";
echo $enstr."\n";
echo $de = evdecrypt($enstr);
echo "\n";
echo $en = evencrypt($de);
echo "\n";
echo evdecrypt($en);
echo "\n";
?>
