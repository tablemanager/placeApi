<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2018-05-03
 * Time: 오전 9:54
 */

function setting_table()
{
    global $conn_rds;

    $table = "MSG_RESULT_" . date("Ym") . "";

    // DB 테이블 일단 확인함..
    $result = $conn_rds->query("SHOW TABLES IN CMSSMS LIKE '" . $table . "'");

    //=================================================
    // 테이블이 조회가 되지 않았을때.. 일단 생성함.
    //=================================================
    if ($result->num_rows == 0) {

        mysqli_set_charset($conn_rds, 'utf8');

        // =========================================================
        // 테이블 생성
        // =========================================================
        $Create_sql = @"
        CREATE TABLE IF NOT EXISTS CMSSMS.`" . $table . "` (
          `IDX` int(11) NOT NULL,
          `AUTH_ID` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ID',
          `REQUEST_TIME` datetime NOT NULL COMMENT '발송시간',
          `MSG_TYPE` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT 'sms, lms, mms, kakao',
          `DSTADDR` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '수신주소',
          `CALLBACK` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '발송주소',
          `ORDERNO` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '주문번호',
          `COUPONNO` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '쿠폰번호',
          `COUPON_TYPE` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '쿠폰타입',
          `MSG_SUBJECT` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '제목',
          `MSG_TEXT` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '메세지 내용',
          `FILELOC1` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '파일명',
          `STAT` enum('S','E','R') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'R재발송, S발송성공, E에러',
          `RESULTS` text COLLATE utf8_unicode_ci COMMENT '결과',
          `DCNT` int(11) NOT NULL DEFAULT '0' COMMENT '발송 카운트',
		  `EXTVAL1` text COLLATE utf8_unicode_ci COMMENT '기타1',
		  `EXTVAL2` text COLLATE utf8_unicode_ci COMMENT '기타2',		  
		  `EXTVAL3` text COLLATE utf8_unicode_ci COMMENT '기타3',
		  `EXTVAL4` text COLLATE utf8_unicode_ci COMMENT '기타4',
          `PROFILE` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '프로필키'	            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ";

        if (!$conn_rds->query($Create_sql)) {
            return array("result" => false, "msg" => "CREATE TABLE ERROR : %s\n" . $conn_rds->error);
        }

        // =========================================================
        // 인덱스 설정
        // =========================================================
        $index_sql = @"
            --
            -- 테이블의 인덱스 `" . $table . "`
            --
            ALTER TABLE CMSSMS.`" . $table . "`
              ADD PRIMARY KEY (`IDX`),
              ADD KEY `ORDERNO` (`ORDERNO`);
            --
        ";

        if (!$conn_rds->query($index_sql)) {
            return array("result" => false, "msg" => "ALTER INDEX ERROR : %s\n" . $conn_rds->error);
        }

        // =========================================================
        // 그외.. (AUTO_INCREMENT)
        // =========================================================

        $Etc_sql = @"
            --
            -- 테이블의 AUTO_INCREMENT `" . $table . "`
            --
            ALTER TABLE CMSSMS.`" . $table . "`
              MODIFY `IDX` int(11) NOT NULL AUTO_INCREMENT;
        ";

        if (!$conn_rds->query($Etc_sql)) {
            return array("result" => false, "msg" => "AUTO_INCREMENT ERROR : %s\n" . $conn_rds->error);
        }
    }

    return array("result" => true, "msg" => "정상적으로 생성 완료");
}

// ======================================================
// 해당 테이블에 맞춰 데이터를 넣을 준비를함..
// 테이블 : MSG_RESULT_날짜
// $res[0] = 결과값 | $res[1] = 데이터 응답값 | $res[2] = 실패된 위치 (성공시 미노출)
// ======================================================
function insert_MSG_RESULT($item, $type, $res)
{
    global $conn_rds;

    $type_arr = array("LMS" => "L", "MMS" => "M", "SMS" => "S", "KAKAO" => "K", "KAKAOV2" => "K");
    $table = "MSG_RESULT_" . date("Ym") . "";

    // 혹시몰라.. DB escape 처리
    $arr = array();
    foreach ($item as $k => $v) {
        $arr[$k] = mysqli_real_escape_string($conn_rds, $v);
    }

    // 프로필키가 없으면 하마톡톡키를 넣는걸루..
    if ($arr['kakao_profile'] == '') $arr['kakao_profile'] = "hamac";

    $insert = array();
    $insert['MSG_TYPE'] = $type_arr[$type];                 // 메세지 타입
    $insert['DSTADDR'] = $arr['dstAddr'];                   // 수신번호
    $insert['CALLBACK'] = $arr['callBack'];                 // 발신번호
    $insert['ORDERNO'] = $arr['orderNo'];                   // 주문번호
    $insert['COUPONNO'] = $arr['pinNo'];                    // 쿠폰번호(핀번호)
    $insert['COUPON_TYPE'] = $arr['pinType'];               // 수신번호
    $insert['MSG_SUBJECT'] = $arr['msgSubject'];            // 제목
    $insert['MSG_TEXT'] = $arr['msgText'];                  // 메세지 내용
    $insert['FILELOC1'] = $arr['mmsFile'];                  // 파일명
    $insert['STAT'] = $res['result'] == TRUE ? "S" : "E";   // R재발송, S발송성공, E에러
    $insert['RESULTS'] = $res['data'];                      // 결과
    $insert['DCNT'] = '0';                                  // 발송 카운트
    $insert['EXTVAL1'] = $arr['extVal1'];                   // 기타값1
    $insert['EXTVAL2'] = $arr['extVal2'];                   // 기타값2
    $insert['EXTVAL3'] = $arr['extVal3'];                   // 기타값3
    $insert['EXTVAL4'] = $arr['extVal4'];                   // 기타값4
    $insert['PROFILE'] = $arr['kakao_profile'];             // 카카오 프로필값

    // 선언된 Array에 SQL문을 합쳐줌.
    $sql = implode(', ', array_map(
        function ($k, $v) {
            return trim($k) . '=\'' . trim($v) . '\'';
        },
        array_keys($insert),
        $insert
    ));

    // 마지막 발송하기전에, 테이블 이나.. 등등 합쳐줌
    mysqli_set_charset($conn_rds, 'utf8');
    $sql = "insert CMSSMS." . $table . " set REQUEST_TIME = '".date("Y-m-d H:i:s")."', " . $sql;

    $conn_rds->query($sql);
}

// ======================================================
// 조회된 데이터에 따라, INSERT 작업을 진행한다.
// 테이블 : CMSSMS.KAKAO_TEMPLATE
// (REG:등록,REQ:심사요청,APR:승인,REJ:반려)
// ======================================================
function insert_Template($item)
{
    global $conn_rds;

    // 혹시몰라.. DB escape 처리
    $arr = array();
    foreach ($item as $k => $v) {
        $arr[$k] = mysqli_real_escape_string($conn_rds, $v);
    }

    $insert = array();
    $insert['TEMPLATE_CD'] = $arr['templateCode'];                   // 템플릿 코드
    $insert['CONTENT'] = $arr['templateContent'];                    // 템플릿 내용
    $insert['INSPECTION'] = 'REQ';                                   // 승인상태 (심사요청 상태로..)

    // button이 있으면, 불러들여서 테이블에 저장한다.
    if (!empty(json_decode($item['buttons'], true))) {
        foreach (json_decode($item['buttons'], true) as $k => $v) {
            if ($v['ordering'] > 4) continue;
            $insert['BUTTONS_'.$v['ordering']] = mysqli_real_escape_string($conn_rds, json_encode($v));
        }
    }

    $sql = @"
            INSERT INTO CMSSMS.KAKAO_TEMPLATE (DATE, " . @implode(", ", array_keys($insert)) . ") 
            select now(), '" . @implode("', '", array_values($insert)) ."' FROM DUAL
            WHERE NOT EXISTS (SELECT TEMPLATE_CD FROM CMSSMS.`KAKAO_TEMPLATE` WHERE TEMPLATE_CD= '" . $arr['templateCode'] . "')
        ";

    // 마지막 발송하기전에, 테이블 이나.. 등등 합쳐줌
    mysqli_set_charset($conn_rds, 'utf8');
    $conn_rds->query($sql);
}

// ======================================================
// 조회된 데이터에 따라, 승인상태를 변경하는 UPDATE
// 테이블 : CMSSMS.KAKAO_TEMPLATE
// (REG:등록,REQ:심사요청,APR:승인,REJ:반려)
// ======================================================
function update_Template($item)
{
    global $conn_rds;

    // 혹시몰라.. DB escape 처리
    $arr = array();
    foreach ($item as $k => $v) {
        $arr[$k] = "'" . mysqli_real_escape_string($conn_rds, $v) . "'";
    }

    $insert = array();
    $insert['INSPECTION'] = $arr['inspectionStatus'];                // 승인상태

    // 선언된 Array에 SQL문을 합쳐줌.
    $temp = implode(', ', array_map(
        function ($k, $v) {
            return trim($k) . '=' . trim($v);
        },
        array_keys($insert),
        $insert
    ));

    $sql = @"UPDATE CMSSMS.KAKAO_TEMPLATE SET " . "$temp" . " WHERE TEMPLATE_CD = " . $arr['templateCode'];

    // 마지막 발송하기전에, 테이블 이나.. 등등 합쳐줌
    mysqli_set_charset($conn_rds, 'utf8');
    $conn_rds->query($sql);
}

?>
