<?php
include '/home/sparo.cc/ezwel_script/class/class.crypto.php';

$ar = Array
(
    "cspCd" => "DcFWYg9DKuJYoWb3WweWkg==as",
    "cspGoodsCd" => "gX3BxY8ZYIG6KkWe7B09Sw==",
    "ecpnTrid" => "ItmroJAJLQMKSa1Cj/g5dw==",
    "cancelText" => "3ETFJsDhattRICfYzz2TgA==",
);

$crypto = new Crypto();

foreach ($ar as $k=> $item){
    echo "$k => ".$crypto->decrypt($item);
    echo "\n";
}
?>
