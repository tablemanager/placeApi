<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-08-08
 * Time: 오후 4:38
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/sparo.cc/order_script/lib/SendData_Script.php');

switch ($_POST['go']) {

    case 'get' :
        $list = get_list($_POST['type']);
        echo $list;

        break;

}

function get_list($type)
{
    global $conn_rds;

    $all = array();

    mysqli_set_charset($conn_rds, 'utf8');

    $tpye_text = "";
    if ($type != "") $tpye_text = "and `ORDER_SYNC` = " . $type;

    $sql = "SELECT * FROM cmsdb.`coupang_cancel` WHERE `IN_DATE` like '" . date("Y.m.d") . "%' " . $tpye_text;
    $result = $conn_rds->query($sql);

    $sync_arr = array("2" => "미사용", "1" => "사용", "N" => "확인불가");

    while ($row = mysqli_fetch_assoc($result)) {
        $temp = array();
        $temp['CREATED_TIME'] = trim($row['CREATED_TIME']);
        $temp['UPDATE_TIME'] = trim($row['UPDATE_TIME']);
        $temp['IN_DATE'] = trim($row['IN_DATE']);
        $temp['ORDER_NO'] = trim($row['ORDER_NO']);
        $temp['COUPON_NO'] = trim($row['COUPON_NO']);
        $temp['DEAL_NM'] = trim($row['DEAL_NM']);
        $temp['OPTION_NM'] = trim($row['OPTION_NM']);
        $temp['ORDER_USER'] = trim($row['ORDER_USER']);
        $temp['ORDER_TEL'] = trim($row['ORDER_TEL']);
        $temp['REASON'] = trim($row['REASON']);
        $temp['STATE'] = trim($row['STATE']);
        $temp['ORDER_SYNC'] = $sync_arr[trim($row['ORDER_SYNC'])];

        $all[] = $temp;
    }

    return json_encode($all);
}



?>