<?php
require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once ('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
$connSparo2 = new ConnSparo2();

$data['ch_orderno'] = '346491251_1';

            $pkgcoupon_id = $connSparo2->get_phoenix_pkgcoupon_ch_orderno_last($data['ch_orderno']);
print_r($pkgcoupon_id);

print($pkgcoupon_id['id']);
?>
