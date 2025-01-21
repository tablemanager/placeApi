<?php
$a=
array('Code' => 1000,
    'Msg' => '성공'
);

$a['Result'][] = array('OrderNo' => '20230613_Cuyt1YMIrE67',
'CouponNo' => 'askdjflaksjdf');

$en = json_encode($a);
$de = json_decode($en);
print_r($de);


if(isset($de->Result[0]->CouponNo)){
    echo "{$de->Result[0]->CouponNo}\n";
}
?>
