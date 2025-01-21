<?php
/*
* brief 샵링커 api에 주문조회 요청을 보내기 위한 정보 (요청하는 파일은 223@/home/batch_works/shoplinker_script/prc_getorder.php)
* 참고 : 샵링커 연동문서 http://apiweb.shoplinker.co.kr/ShoplinkerApi/ver3/Order/OrderInfo.html?spec_009=ov&guTwo=t
* author Jay
* modify Jason
* date 22.01.24(updated)
*/

$sdate = date("Ymd", strtotime("-5 days"));
$edate = date("Ymd");

?>
<?xml version="1.0" encoding="euc-kr"?>
<Shoplinker>
	<OrderInfo>
		<Order>
			<customer_id>a0023390</customer_id>
			<shoplinker_id><![CDATA[placem1015]]></shoplinker_id>
			<order_flag>000</order_flag>
			<st_date><?=$sdate?></st_date>
			<ed_date><?=$edate?></ed_date>
			<mall_order_code>APISHOP_0148</mall_order_code>
		</Order>
	</OrderInfo>
</Shoplinker>

