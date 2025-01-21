<?php
/*
 *
 * 천안상록 키오스크 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 *
 *
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$v_ch = 488; // 상록

$sitem = "SELECT * from CMSDB.CMS_ITEMS where item_cpid = '488'
and item_sdate < now()
and item_edate > now()";

$ires = $conn_cms->query($sitem);
$items = array();
while($irow = $ires->fetch_object()){
    $items[] = $irow->item_id;   
}

print_r($items);

?>
