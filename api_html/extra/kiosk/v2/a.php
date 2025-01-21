<?php
$a['result']= 1000;
$a['msg']="정상";
$a['info'][] = array("code"=>100) ;
$a['info'][] = array("code"=>200) ;
$a['info'][] = array("code"=>300) ;

print_r( json_encode($a));
?>
