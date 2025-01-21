<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2018-07-02
 * Time: 오후 2:21
 *
 */

// 문자발송테이블 (카카오, sms)
//1. 마지막 시간 체크
//2. 한시간에 에러
//3. 딜별 에러체크..
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$table = "MSG_RESULT_" . date("Ym") . "";
$result = $conn_rds->query("SHOW TABLES IN CMSSMS LIKE '" . $table . "'");

// 타임존이 안맞아서 수정.
date_default_timezone_set("Asia/Seoul");

// 전화번호 배열에 추가하면, 알아서 메세지 전달됨
$send_tel = array(
    //'01026180927', // 서지윤
    '01090901678', // 이정진
    '01067934084', // tony
    );

// 정상적으로 테이블이 조회 되었을때, 진행한다..
if ($result->num_rows == 1) {
    mysqli_set_charset($conn_rds, 'utf8');

    // 최종 전송시간.. check ( 뭐든.. MMS, SMS, LMS )
    Last_Result_Time($conn_rds, $table, $send_tel);

    // 한시간동안 에러가 15건이상..
    Last_hours($conn_rds, $table, $send_tel);

    // 5분동안 문자가 많이 발송되면, 알림문자
    if (date("YmdHi") > "20181123090000" ) {
        Last_5m($conn_rds, $table, $send_tel);
    }

    // SMS 검증 프로세스
    // Last_Order_Time($conn_rds, $table);

} else {
    // 테이블이 정상적으로 조회 되지않았을때.. 이런경우는 극히드믐..
//    $msg = "Tabel : '".$table."' 이 정상적으로 조회되지 않습니다.";
//    send_msg($send_tel, trim($msg));
}


// 한시간내에 에러가 15개 이상이면.. 일단 발송건수가 적어서 갠춘..
function Last_hours($conn_rds, $table, $send_tel)
{
    $sql = @"SELECT `MSG_TYPE`, COUNT(`IDX`) as count FROM  CMSSMS.`" . $table . "`
                WHERE `STAT` = 'E' AND `REQUEST_TIME` BETWEEN '" . date("Y-m-d H:i:s", strtotime("-1 hours")) . "' and NOW()
                GROUP BY `MSG_TYPE`";
    $result = $conn_rds->query($sql);

    // 에러가 있는경우...
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            $rows[$row['MSG_TYPE']] = $row['count'];
        }

        foreach ($rows as $k => $v) {
            if ($v >= 15) {
                $msg = @"Table : CMSSMS." . $table . "\nTpye : " . $k . "\nmsg : " . $v . "건 에러 발생 (1시간 기준)";
//                send_msg('01082085996', trim($msg));
                send_msg('01090901678', trim($msg)); //코너 대신 제이
                send_msg('01067934084', trim($msg)); // tony
            }
        }
    }
}

// 최근 5분내 타입에따라 500건이 문자가 발송되었으면 문자를 받도록함.
function Last_5m($conn_rds, $table, $send_tel)
{
    echo $sql = @"SELECT `MSG_TYPE`, COUNT(`IDX`) as count FROM  CMSSMS.`" . $table . "`
                WHERE `REQUEST_TIME` BETWEEN '" . date("Y-m-d H:i:s", strtotime("-5 minutes")) . "' and NOW()
                GROUP BY `MSG_TYPE`";

    $result = $conn_rds->query($sql);

    // 에러가 있는경우...
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            $rows[$row['MSG_TYPE']] = $row['count'];
        }

        foreach ($rows as $k => $v) {

            if ($v >= 400) {
                // 선언된 Array에 SQL문을 합쳐줌.
                $msg = implode("\n", array_map(
                    function ($k, $v) {
                        return "[" . trim($k) . "] => " . trim($v);
                    },
                    array_keys($rows),
                    $rows
                ));

                $msg = @"Table : CMSSMS." . $table . "\n" . trim($msg) . "\n건수 발생";
                send_msg($send_tel, trim($msg));
                break;
            }
        }
    }
}

// 딜별로.. 마지막 문자시간을 측정하는것.. 하지만 지금 이건... 문제가있는게 딜코드가 넘어오지않아서
// 만료기간을 확인할 수 없다. (당장은 불가능.. SMS 나 이런쪽으로 생각해봐야할듯함.)
function Last_Order_Time($conn_rds, $table, $send_tel)
{
    $sql = @"SELECT MAX(`REQUEST_TIME`), `MSG_SUBJECT`, `MSG_TEXT` FROM CMSSMS.`" . $table . "`
                WHERE `MSG_TYPE` = 'K'
                GROUP BY `MSG_SUBJECT`
                ORDER BY `IDX` DESC";

    $result = $conn_rds->query($sql);


    if ($result->num_rows > 0) {

        // adssda
        while ($row = $result->fetch_array()) {
            $rows[] = $row;
        }
    }
    xmp($rows);
    exit;

    xmp($result);
    exit;
}

// 마지막 전송시간을 체크하여, 30분 넘어갔을 경우... 문자를 발송 할 수 있도록 한다.
function Last_Result_Time($conn_rds, $table, $send_tel)
{
    $sql = @"SELECT * FROM  CMSSMS.`" . $table . "` ORDER BY `REQUEST_TIME` DESC limit 1";
    $result = $conn_rds->query($sql);
    $row = mysqli_fetch_array($result);

    // 현재 시각과, 마지막 데이터 시간을 비교한다.
    $now = strtotime(date('Y-m-d H:i:s'));
    $db_time = strtotime($row['REQUEST_TIME']);

    // 시간차이 계산 구하기
    $diff = $now - $db_time;

    // 몇분 정도 차이가 나는지..
    $minutes = floor($diff / 60);

    // date("H" , strtotime("-4 hours")) >= "08"
    // 08:00 ~ 23:59:59 => 30분 | 24:00 ~ 07 : 59 => 4시간
    $time_chk = date("H") >= "08" ? 30 : 240;

    // 이렇게 되면.. 문제가 있는것으로 인지하여 문자를 발송하도록 한다.
    if ($minutes > $time_chk) {
        $msg = @"Table : CMSSMS." . $table . "\nmsg : ".$minutes."분 이상 발송 데이터가 insert 되고 있지 않습니다.";
        send_msg($send_tel, trim($msg));
    }
}

// 문자 발송 함수ㅜ우우우우
function send_msg($tel, $msg){

    foreach ($tel as $val) {
        $Subject = "";                                                    // 제목
        $orderno = date("YmdHis");                                // 주문번호

        $msgarr = array(
            "dstAddr" => $val,
            "callBack" => "0221563080",
            "msgSubject" => $Subject,
            "msgText" => $msg,
            "mmsFile" => "",
            "orderNo" => $orderno,
            "pinType" => "",
            "pinNo" => "",
            "extVal1" => "",
            "extVal2" => "",
            "extVal3" => "",
            "extVal4" => ""
        );
        $jsonreq = json_encode($msgarr);

        send_url("http://gateway.sparo.cc/internal/messages/sms", "POST", $jsonreq,null,null);
    }
}

function send_url($url, $method, $data, &$http_status = null, &$header = array()) {

    //Log::debug("Curl $url JsonData=" . $post_data);
    $ch=curl_init();

    //curl_setopt($ch, CURLOPT_HEADER, true);				// 헤더 출력 옵션..
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // 메세지의 따른..
    switch(strtoupper($method))
    {
        case 'GET':
            curl_setopt($ch, CURLOPT_URL, $url);
            break;

        case 'POST':
            $info = parse_url($url);

            $url = $info['scheme'] . '://' . $info['host'] . $info['path'];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            $str = "";
            if (is_array($data)) {
                $req = array();
                foreach ($data as $k => $v) {
                    $req[] = $k . '=' . urlencode($v);
                }

                $str = @implode($req);
            }else {
                $str = $data;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            break;

        default:
            return false;
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);						// TimeOut 값
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				// 결과를 받을것인가.. ( False로 하면 자동출력댐.. ㅠㅠ )
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $body = null;

    // error
    if (!$response) {
        $body = curl_error($ch);
        // HostNotFound, No route to Host, etc  Network related error
        $http_status = -1;
    } else {
        //parsing http status code
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = $response;
        /*
        if (!is_null($header)) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        } else {
            $body = $response;
        }
        */
    }

    curl_close($ch);

    return $body;
}

// 디버깅용
function xmp($text) {
    echo "<xmp>";
    print_r($text);
    echo "</xmp>\n";
}

?>
