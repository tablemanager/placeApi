<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$now = date('Ymd');
$i=0;
	echo "<BR><BR>## 롯데통합 쿠폰 네이버 캐시 ##";
$ordqry="select EventEndDate,EventNm,SellCode,EventCd,count(CouponNo) as cnt from cmsdb.lotte_pincode where EventEndDate >= '$now' and SyncResult ='0' GROUP BY SellCode ";
$res = $conn_rds->query($ordqry);
	echo "<BR><BR>===== 구매가능 =====<BR>";
echo "<table>";
while($row = $res->fetch_object()){

	echo "<TR><TD>$row->EventEndDate $row->EventCd </TD><TD>$row->EventNm</TD><TD><b>$row->SellCode</b></TD><TD>$row->cnt</TD></TR>";
}
echo "</table><br><table>";

$ordqry="select EventNm,SellCode,count(CouponNo) as cnt from cmsdb.lotte_pincode where SyncResult ='P' GROUP BY SellCode ";
$res = $conn_rds->query($ordqry);
	echo "<BR><BR>===== 복구 가능 쿠폰 =====<BR>";
while($row = $res->fetch_object()){

	echo "<TR><TD>$row->EventNm</TD><TD><b>$row->SellCode</b></TD><TD>$row->cnt</TD></TR>";

}
echo "</table>";
?>
