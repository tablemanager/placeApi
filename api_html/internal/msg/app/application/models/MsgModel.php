<?php
/**
 * 공통 모델
 * @author larry
 * @since 2020.08.18
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class MsgModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->rds = $this->load->database('rds', TRUE);        // rds DB
    }
    
       /**
     * 발송내역 조회
	 * @author larry
	 * @since 2020.08.18
	 * @table CMSSMS.MSG_RESULT_YYYYMMDD
     * @param array $aData 데이터 배열
     * @return array
     */
    public function getMsgList($aData)
    {
        if (empty($aData) === true || is_array($aData) === false) {
            return array();
        }

		if(isset($aData['dateFormat'])){
			if($aData["dateFormat"]=="mm"){ //일별/월별
				$sendDate = "date_format(REQUEST_TIME,'%Y-%m')";
			}else{
				$sendDate = "date_format(REQUEST_TIME,'%Y-%m-%d')";
			}
		}
	
		$aData["searchName"]="m";
		if(isset($aData['searchName'])){
			if($aData["searchName"]=="m"){ //메세지타입
				$searchName = "case when MSG_TYPE='K' then 'KAKAO' when MSG_TYPE='S' then 'SMS' when MSG_TYPE='L' then 'LMS' when MSG_TYPE='M' then 'MMS' end";

			}else if($aData["searchName"]=="c"){ //쿠폰타입
				$searchName = "COUPON_TYPE";
			}
		}
		
		if ($aData['date_select'] == '') $aData['date_select'] = date("Ym");

		if($aData["dataType"]=="group"){
			$sql = "
				SELECT
					".$sendDate." as sendDate
					,".$searchName." as searchName
					, count(*) as cnt
					,sum(case when STAT != 'E' then 1 ELSE 0 END) as s_cnt
					,sum(case when STAT = 'E' then 1 ELSE 0 END) as e_cnt
				FROM 
					CMSSMS.MSG_RESULT_".str_replace("-","", $aData['date_select'])."
				WHERE 1 = ?";
		}else{
			$sql = "
				SELECT 
					* 
				FROM 
					CMSSMS.MSG_RESULT_".str_replace("-","", $aData['date_select'])."
				WHERE 1 = ?";
		}
		$arrData = array(1);

		if (isset($aData['IDX']) && $aData['IDX']){			
			$sql .= " and IDX = ?";
			array_push($arrData, $aData['IDX']);
		}else {
			if (isset($aData['msg_type']) && $aData['msg_type']){
				$sql .= " and MSG_TYPE = ?";
				array_push($arrData, $aData['msg_type']);
			}
			if (isset($aData['tel']) && $aData['tel']){
				$aData['tel'] = str_replace("-","",$aData['tel']);
				$sql .= " and DSTADDR like ?";
				array_push($arrData, '%'.$aData['tel'].'%');
			}
			if (isset($aData['order_Num']) && $aData['order_Num']){
				$sql .= " and ORDERNO like ?";
				array_push($arrData, '%'.$aData['order_Num'].'%');
			}
			if (isset($aData['msg_res']) && $aData['msg_res']){
				$sql .= " and STAT = ?";
				array_push($arrData, $aData['msg_res']);
			}
		}

		if($aData["dataType"]=="group"){
			$sql .= " GROUP BY sendDate, searchName";
		}
		
		//$this->CI->commLib->sql($sql, '', $arrData); //test

        $result = $this->rds->query($sql, $arrData)->result_array();        
        if (empty($result) === true) {
            return array();
        }
        
        return $result;
    }

	
    /**
     * SMS전송
	 * @author larry
	 * @since 2020.08.18
	 * @table CMSSMS.SC_TRAN
     * @param array $aData 데이터 배열
     * @return int
     */
    public function addSMS($aData)
    {
        if (empty($aData) === true || is_array($aData) === false) {
            return 0;
        }
		$sql = "
            INSERT INTO CMSSMS.SC_TRAN (
				TR_SENDDATE,
				TR_ID,
				TR_SENDSTAT,
				TR_RSLTSTAT,
				TR_MSGTYPE,

				TR_PHONE,
				TR_CALLBACK,
				TR_RSLTDATE,
				TR_MODIFIED,
				TR_MSG,

				TR_NET,
				TR_ETC1,
				TR_ETC2,
				TR_ETC3,
				TR_ETC4,

				TR_ETC5,
				TR_ETC6,
				TR_ROUTEID,
				TR_REALSENDDATE
            )
            VALUES (
                ?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?, ?
            )
        ";
        $this->rds->query($sql, array(
            date('Y-m-d H:i:s'), date('YmdHis'), '0', '00',	'0',
			$aData["dstAddr"], $aData["callBack"], '', '', $aData["msgText"],
            '', '', $aData["Authorization"], '', '',
			'', '', '', ''
        ));
        return $this->rds->insert_id();
	}

	/**
     * MMS전송
	 * @author larry
	 * @since 2020.08.18
	 * @table CMSSMS.MMS_MSG
     * @param array $aData 데이터 배열
     * @return int
     */
    public function addMMS($aData)
    {
		if (empty($aData) === true || is_array($aData) === false) {
            return 0;
        }
		$ID = "";
		$sql = "
            INSERT INTO CMSSMS.MMS_MSG (
				SUBJECT,
				PHONE,
				CALLBACK,
				STATUS,
				REQDATE,

				MSG,
				FILE_CNT,	
				FILE_CNT_REAL,
				FILE_PATH1,
				FILE_PATH2,

				FILE_PATH3,				
				FILE_PATH4,
				FILE_PATH5,
				TYPE,
				ID,

				ETC1,
				ETC2,
				ETC3,
				ETC4
            )
            VALUES (
                ?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?, ?
            )
        ";
        $this->rds->query($sql, array(
            $aData["msgSubject"], $aData["dstAddr"], $aData["callBack"], '0', date('Y-m-d H:i:s'),
			$aData["msgText"], $aData["FILE_CNT"], '0', $aData["FILE_PATH1"], '',
			 '', '', '', '0', $ID,
			 '', $aData["Authorization"], '', ''
        ));
        return $this->rds->insert_id();
	}


	/**
     * 문자 전송로그 기록
	 * @author larry
	 * @since 2020.08.27
	 * @table CMSSMS.MSG_RESULT_YYYYMMDD
     * @param array $aData 데이터 배열
     * @return int
     */
    public function addMsgResult($aData, $stat)
    {
		if (empty($aData) === true || is_array($aData) === false) {
            return 0;
        }
		$result = $this->createMsgResult();
		if(!$result){
			return 0;
		}

		$type_arr = array("LMS" => "L", "MMS" => "M", "SMS" => "S", "KAKAO" => "K");
		$aData["kakao_profile"] = $aData["kakao_profile"]? $aData["kakao_profile"] : "hamac"; // 프로필키가 없으면 하마톡톡키

		//$table = "CMSSMS.MSG_RESULT_" . date("Ym") . "";
		$table = "CMSSMS.MSG_RESULT_TEST"; //test
		$sql = "
            INSERT INTO ".$table." (
				REQUEST_TIME,
				MSG_TYPE,
				DSTADDR,
				CALLBACK,
				ORDERNO,

				COUPONNO,
				COUPON_TYPE,
				MSG_SUBJECT,
				MSG_TEXT,
				FILELOC1,

				STAT,
				RESULTS,
				DCNT,
				EXTVAL1,
				EXTVAL2,

				EXTVAL3,
				EXTVAL4,
				PROFILE
            )
            VALUES (
                ?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, 
				?, ?, ?
            )
        ";
		$arrData = array(
            date('Y-m-d H:i:s'), $type_arr[$aData["type"]], $aData["dstAddr"], $aData["callBack"], @$aData["orderNo"],
			@$aData["pinNo"], @$aData["pinType"], $aData["msgSubject"], $aData["msgText"], @$aData["FILE_PATH1"],
            $stat, '', '0', @$aData["extVal1"], $aData["Authorization"], 
			@$aData["extVal3"], @$aData["extVal4"], $aData["kakao_profile"]
        );
		/*
		$this->CI->commLib->addLog(date('Ymd').'_addMsgResult_log', print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'aData' => $aData,
			'sql' => $sql,
			'arrData' => $arrData
		), true), 'api'); //debug
		*/
        $this->rds->query($sql, $arrData);
        return $this->rds->insert_id();
	}

	/**
     * 문자 전송로그 테이블 생성
	 * @author larry
	 * @since 2020.08.27
	 * @table CMSSMS.MSG_RESULT_YYYYMMDD
     * @return int
     */
    public function createMsgResult()
    {
		$table = "MSG_RESULT_" . date("Ym") . "";
		$table = "MSG_RESULT_TEST"; //test

		$arrData = array($table);		
		$sql = "SHOW TABLES IN CMSSMS LIKE ?";
		$result = $this->rds->query($sql, $arrData)->row_array();
		/*
		$this->CI->commLib->addLog(date('Ymd').'_error_log', print_r(array(
			'errorLog' => date('Y.m.d H:i:s'),
			'result' => $result			
		), true), 'api'); //debug
		*/

		if (empty($result) === false) {
            return 1;
        } 

		// =================== 테이블 생성
		$arrData = array("ID");
        $sql = "
        CREATE TABLE IF NOT EXISTS CMSSMS.".$table." (
          `IDX` int(11) NOT NULL,
          `AUTH_ID` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT ?,
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
		//$this->CI->commLib->sql($Create_sql, '', $arrData); //test
		$this->CI->commLib->addLog(date('Ymd').'_create_table', print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'sql' => $sql
		), true), 'api');
		$this->rds->query($sql, $arrData);
		
        // =================== 인덱스 설정
		$arrData = array("IDX");
        $sql = "
            -- 테이블의 인덱스 ?
            ALTER TABLE CMSSMS.".$table."
              ADD PRIMARY KEY (`IDX`),
              ADD KEY `ORDERNO` (`ORDERNO`);
        ";
		$this->CI->commLib->addLog(date('Ymd').'_create_table', print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'sql' => $sql			
		), true), 'api');
		$this->rds->query($sql, $arrData);

        // =================== (AUTO_INCREMENT)
		$arrData = array("IDX");
        $sql = "
            -- 테이블의 AUTO_INCREMENT ?
            ALTER TABLE CMSSMS.".$table."
              MODIFY `IDX` int(11) NOT NULL AUTO_INCREMENT;
        ";
		$this->CI->commLib->addLog(date('Ymd').'_create_table', print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'sql' => $sql			
		), true), 'api');
		$this->rds->query($sql, $arrData);


		return 1;
	}

}