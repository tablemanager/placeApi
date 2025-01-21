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


// ======================================================
// 발송내역 조회
// 테이블 : CMSSMS.MSG_RESULT_YYYYMMDD
// by larry
// ======================================================
function getMsgList($data)
{
    global $conn_rds;
	//global $conn_rdsslave;

	// character_set 을 우선적으로 변경해줘야함..
	mysqli_query($conn_rds, "set session character_set_connection=utf8");
	mysqli_query($conn_rds, "set session character_set_results=utf8");
	mysqli_query($conn_rds, "set session character_set_client=utf8");	

	$data['tel'] = str_replace("-","",$data['tel']);
	if ($data['date_select'] == '') $data['date_select'] = $_POST['date_select'];

	$sql_array = array();
	$sql_array[] = "1=1";

	if ($data['IDX'] != '') $sql_array[] = "`IDX` = '".$data['IDX']."'";
	else {
	//    if ($data['sdate'] != '' && $data['edate'] != '') $sql_array[] = "`REQUEST_TIME` between '" . $data['sdate'] . " 00:00:00' and '" . $data['edate'] . " 23:59:59'";
		if ($data['msg_type'] && $data['msg_type'] != 'ALL') $sql_array[] = "`MSG_TYPE` = '" . $data['msg_type'] . "'";
		if ($data['tel']) $sql_array[] = "`DSTADDR` like '%" . $data['tel'] . "%'";
		if ($data['order_Num']) $sql_array[] = "`ORDERNO` like '%" . $data['order_Num'] . "%'";
		if ($data['msg_res'] && $data['msg_res'] != 'ALL') $sql_array[] = "`STAT` = '" . $data['msg_res'] . "'";
	}

	if ($data['date_select'] == '') $data['date_select'] = date("Ym");

	if (count($sql_array) == 0) {
		$sql_array[] = "`REQUEST_TIME` between '" . date("Y-m-d") . " 00:00:00' and '" . date("Y-m-d") . " 23:59:59'";
		$sql_array[] = "";
	}

	if($data["dateType"]=="mm"){ //일별/월별
		$sendDate = "date_format(REQUEST_TIME,'%Y-%m')";
	}else{
		$sendDate = "date_format(REQUEST_TIME,'%Y-%m-%d')";
	}

	if($data["searchName"]=="m"){ //메세지타입
		$searchName = "case when MSG_TYPE='K' then 'KAKAO' when MSG_TYPE='S' then 'SMS' when MSG_TYPE='L' then 'LMS' when MSG_TYPE='M' then 'MMS' end";

	}else if($data["searchName"]=="c"){ //쿠폰타입
		$searchName = "COUPON_TYPE";
	}

	if($data["dataType"]=="group"){
		$sql = @"
			SELECT
				".$sendDate." as sendDate
				,".$searchName." as searchName
				, count(*) as cnt
				,sum(case when STAT != 'E' then 1 ELSE 0 END) as s_cnt
				,sum(case when STAT = 'E' then 1 ELSE 0 END) as e_cnt
			FROM 
				CMSSMS.MSG_RESULT_".str_replace("-","", $data['date_select'])."
			Where ".implode(" and ",$sql_array)."
			GROUP BY sendDate, searchName";
	}else{
		$sql = @"SELECT * FROM CMSSMS.MSG_RESULT_".str_replace("-","", $data['date_select'])."
				Where ".implode(" and ",$sql_array);
	}

	//echo $sql;exit; //test
	$result = $conn_rds->query($sql);

	// 쿼리 조회되었던 데이터.. 다시 가공
	$res = array();
	$msg_type_arr = array("K" => "KAKAO", "S" => "SMS", "L" => "LMS", "M" => "MMS");
	$stat_type_arr = array("S" => "성공", "E" => "실패");

	while ($row = mysqli_fetch_assoc($result)) {
		$msg_err = "";

		$temp = array();

		if($data["dataType"]=="group"){
			$temp['sendDate'] = $row['sendDate'];
			$temp['searchName'] = $row['searchName'] ? $row['searchName'] : '기타';
			$temp['cnt'] = $row['cnt'];
			$temp['s_cnt'] = $row['s_cnt'];
			$temp['e_cnt'] = $row['e_cnt'];

		}else{
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

		}

		$res[] = $temp;
	}

	return $res;

}


// ======================================================
// LG SMS 전송
// 테이블 : CMSSMS.SC_TRAN
// by larry
// ======================================================
function insertSMS($item)
{
	global $conn_rds;
	mysqli_set_charset($conn_rds, 'utf8');

	$data = array();
	$data['TR_SENDDATE'] = 'now()';                 // 메시지를 전송할 시간, 미래 시간을 넣으면 예약 발송됨
	$data['TR_ID'] = '';		// 고객이 발급한 SubID NULL 값 허용
    $data['TR_SENDSTAT'] = '0';                   // 발송상태 (0 : 발송대기, 1 : 결과수신대기, 2 : 결과수신완료)
    $data['TR_RSLTSTAT'] = '00';                // 발송 결과수신 값
    $data['TR_MSGTYPE'] = '0';                   // 문자전송 형태(0 : 일반메시지)
    $data['TR_PHONE'] = trim(str_replace("-", "", $item['dstAddr']));                    // 수신할 핸드폰 번호 
    $data['TR_CALLBACK'] = trim(str_replace("-", "", $item['callBack']));               //송신자 전화번호
    $data['TR_RSLTDATE'] = 'null';            // 이동통신사로부터 결과를 통보 받은 시간
	$data['TR_MODIFIED'] = 'null';            // 프로그램 내부적으로 사용
    $data['TR_MSG'] = trim($item['msgText']);             //전송할 메시지 
    $data['TR_NET'] =  '';                  // 전송 완료 후 최종 이동통신사 정보(011,016,019,000)

    $data['TR_ETC1'] = '';   // 기타 필드1 
    $data['TR_ETC2'] = '';   // 기타 필드2
    $data['TR_ETC3'] = '';   // 기타 필드3
    $data['TR_ETC4'] = '';   // 기타 필드4
    $data['TR_ETC5'] = '';   // 기타 필드5
	$data['TR_ETC6'] = '';   // 기타 필드6

    $data['TR_ROUTEID'] = '';                   // 실제 발송한 세션 ID
    $data['TR_REALSENDDATE'] = 'null';          //모듈이 실제 발송(DELIVER)한 시간


    $sql = @"insert into CMSSMS.SC_TRAN select '','" . @implode("', '", array_values($data)) ."' FROM DUAL";
	$sql = str_replace("'null'", "null", $sql);
	$sql = str_replace("'now()'", "now()", $sql);
	//echo $sql;exit;
    $ret = $conn_rds->query($sql);
	return $ret;

}

// ======================================================
// LG MMS 전송
// 테이블 : CMSSMS.MMS_MSG
// by larry
// ======================================================
function insertMMS($item)
{
	global $conn_rds;
	mysqli_set_charset($conn_rds, 'utf8');

	$msgSubject = trim($item['msgSubject']);
	$msgSubject = str_replace("{", "[", $msgSubject);
	$msgSubject = str_replace("}", "]", $msgSubject);
	$msgSubject = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#]/i", "", $msgSubject);;
	$msgSubject = $msgSubject == "" ? '모바일 이용권' : mb_substr($msgSubject, 0, 40);

	$data = array();
	$data['SUBJECT'] = $msgSubject;           //제목  40byte까지. 한글, 영어, 숫자,  스페이스, (), [],<> 만 허용.  (기타 특수 기호 사용시  전송이 실패될 수 있음)
	$data['PHONE'] = trim(str_replace("-", "", $item['dstAddr']));                    // 수신할 핸드폰 번호 
    $data['TR_CALLBACK'] = trim(str_replace("-", "", $item['callBack']));               //송신자 전화번호
    $data['STATUS'] = '0';							  // 0 : 전송대기, 2 : 결과수신대기, 3 : 결과수신완료)
	$data['REQDATE'] = 'now()';                 // 메시지를 전송할 시간, 미래 시간을 넣으면 예약 발송됨
	$data['MSG'] = trim($item['msgText']);             //전송할 메시지 
    $data['FILE_CNT'] = '0';                   // 전송파일 개수
	$data['FILE_CNT_REAL'] = '0';                   // 클라이언트가 실제로 체크한 전송파일 개수

	$data['FILE_PATH1'] = '';            // 전송파일1 위치
	$data['FILE_PATH1_SIZ'] = '';            // 전송파일1 사이즈(현재 사용 안함)
	$data['FILE_PATH2'] = '';            // 전송파일2 위치
	$data['FILE_PATH2_SIZ'] = '';            // 전송파일2 사이즈(현재 사용 안함)
	$data['FILE_PATH3'] = '';            // 전송파일3 위치
	$data['FILE_PATH3_SIZ'] = '';            // 전송파일3 사이즈(현재 사용 안함)
	$data['FILE_PATH4'] = '';            // 전송파일4 위치
	$data['FILE_PATH4_SIZ'] = '';            // 전송파일4 사이즈(현재 사용 안함)
	$data['FILE_PATH5'] = '';            // 전송파일5 위치
	$data['FILE_PATH5_SIZ'] = '';            // 전송파일5 사이즈(현재 사용 안함)

	$data['EXPIRETIME'] = '';		// 사용하지 않음
    $data['SENTDATE'] = 'null';                   // 송신완료시간
    $data['RSLTDATE'] = 'null';                   // 클라이언트가 수신 받은 시간
	$data['REPORTDATE'] = 'null';                   // 결과 수신 받은 시간
	$data['TERMINATEDDATE'] = 'null';                   // 메시지 처리가 완료된 시간
    $data['RSLT'] = 'null';            // 결과값
	$data['TYPE'] = '0';            // 0 : MMS , 1 : MMSURL , 2 : 국제 SMS , 3 : 국제 MMS , 4 : PUSH , 7 : HTML
    
    $data['TELCOINFO'] =  'null';                  //이통사 구분코드
	$data['ROUTE_ID'] =  'null';                  //실제 발송한 세션 ID
	$data['ID'] =  'null';                  //송신자 ID
	$data['POST'] =  'null';                  //송신자 부서

    $data['ETC1'] = '';   // 기타 필드1 
    $data['ETC2'] = '';   // 기타 필드2
    $data['ETC3'] = '';   // 기타 필드3
    $data['ETC4'] = '';   // 기타 필드4
    $data['MULTI_SEQ'] = '';   // 동보 발송 시 사용되는 동보 단위 키 값


    $sql = @"insert into CMSSMS.MMS_MSG select '','" . @implode("', '", array_values($data)) ."' FROM DUAL";
	$sql = str_replace("'null'", "null", $sql);
	$sql = str_replace("'now()'", "now()", $sql);
	//echo $sql;exit;
    $ret = $conn_rds->query($sql);
	return $ret;

}

?>
