<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-07-12
 * Time: 오후 2:27
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/sparo.cc/api_html/internal/messages/lib/sms_lib.php');

$ip = get_ip();
if ($ip != "106.254.252.100" && $ip != "118.131.208.123" && $ip != "115.89.22.27"  && $ip != "218.39.39.190" && $ip != "115.92.242.18" && $ip != "115.92.242.187") {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// character_set 을 우선적으로 변경해줘야함..
mysqli_query($conn_rds, "set session character_set_connection=utf8");
mysqli_query($conn_rds, "set session character_set_results=utf8");
mysqli_query($conn_rds, "set session character_set_client=utf8");

parse_str($_POST['data'], $data);

$data['tel'] = str_replace("-","",$data['tel']);
if ($data['date_select'] == '') $data['date_select'] = $_POST['date_select'];

$sql_array = array();

// 쿼리문들을 알아서 추가할끄다ㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏㅏ
// 단, IDX 가 들어오면 IDX만!!!!!!!!!!!!!!!!!!!!!!!
if ($data['IDX'] != '') $sql_array[] = "`IDX` = '".$data['IDX']."'";
else {
//    if ($data['sdate'] != '' && $data['edate'] != '') $sql_array[] = "`REQUEST_TIME` between '" . $data['sdate'] . " 00:00:00' and '" . $data['edate'] . " 23:59:59'";
    if ($data['msg_type'] != 'ALL') $sql_array[] = "`MSG_TYPE` = '" . $data['msg_type'] . "'";
    if ($data['tel']) $sql_array[] = "`DSTADDR` like '%" . $data['tel'] . "%'";
    if ($data['order_Num']) $sql_array[] = "`ORDERNO` like '%" . $data['order_Num'] . "%'";
    if ($data['msg_res'] != 'ALL') $sql_array[] = "`STAT` = '" . $data['msg_res'] . "'";
}


if ($data['date_select'] == '') $data['date_select'] = date("Ym");

//초기 정보 조회 관련 내용임...
if (count($sql_array) == 0) {
    $sql_array[] = "`REQUEST_TIME` between '" . date("Y-m-d") . " 00:00:00' and '" . date("Y-m-d") . " 23:59:59'";
    $sql_array[] = "";
}

// 그래도 없으면 1~
//if (count($sql_array) == "0") $sql_array[] = "1";


$sql = @"SELECT * FROM CMSSMS.MSG_RESULT_".str_replace("-","", $data['date_select'])."
        Where ".implode(" and ",$sql_array);

$result = $conn_rds->query($sql);

// 쿼리 조회되었던 데이터.. 다시 가공
$res = array();
$msg_type_arr = array("K" => "KAKAO", "S" => "SMS", "L" => "LMS", "M" => "MMS");
$stat_type_arr = array("S" => "성공", "E" => "실패");

while ($row = mysqli_fetch_assoc($result)) {
    $msg_err = "";

    $temp = array();
    $temp['IDX'] = $row['IDX'];
    $temp['DATE'] = $row['REQUEST_TIME'];
    $temp['TYPE'] = $msg_type_arr[$row['MSG_TYPE']];
    $temp['TEL'] = $row['DSTADDR'];
    $temp['ORDERNO'] = $row['ORDERNO'];
    $temp['COUPONNO'] = $row['COUPONNO'];
    $temp['COUPON_TYPE'] = $row['COUPON_TYPE'];
    $temp['STAT'] = $stat_type_arr[$row['STAT']];

    if ($data['IDX'] != '') {
        $detil = json_decode($row['RESULTS'],true);

        // 에러가 발생된 경우, 아래와 같이 에러메세지를 한글로 변경함.
        if ($row['MSG_TYPE'] == "K") {
            if ($detil[0]['result'] == 'N') $msg_err = KAKAO_ErrMsg($detil[0]['code']);
        }else {
            if ($detil['header']['isSuccessful'] != TRUE) $msg_err = TOAST_ErrMsg($detil['header']['resultCode']);
        }

        $temp['MSG_SUBJECT'] = $row['MSG_SUBJECT'];
        $temp['MSG_TEXT'] = $row['MSG_TEXT'];
        $temp['RESULTS'] = $row['RESULTS'];
        $temp['ERR_MSG'] = $msg_err;
        $temp['CALLBACK'] = $row['CALLBACK'];
        $temp['PROFILE'] = $row['PROFILE'];
        $temp['EXTVAL1'] = $row['EXTVAL1'];
        $temp['EXTVAL2'] = $row['EXTVAL2'];
        $temp['EXTVAL3'] = $row['EXTVAL3'];
        $temp['EXTVAL4'] = $row['EXTVAL4'];
        $temp['FILELOC1'] = $row['FILELOC1'];
    }

    $res[] = $temp;
}

echo json_encode($res);
exit;
?>
