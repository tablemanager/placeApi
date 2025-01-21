<?php
$orderid = $_GET["orderid"];
$today = date("Ymd");
?>

<?xml version="1.0" encoding="euc-kr"?>
<Shoplinker>
	<Delivery>
		<customer_id>a0023390</customer_id>
		<order_id><?=$orderid?></order_id>
		<delivery_name>업체직송</delivery_name>
		<delivery_invoice><?=$today?></delivery_invoice>
	</Delivery>
</Shoplinker>