<?php
/**
 * @package	CodeIgniter
 * @author	Jeon
 * @since	Version 1.0.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('__print_c'))
{
	function __print_c($txt, $type) 
	{	
		if($type == 'json') {
			$txt = json_encode($txt);
		}
		echo  '<script>console.log('.$txt.')</script>';
	}
}

if (!function_exists('__setAbrDate'))
{
	/**
	 * @param null
	 * @param 몇일 후~~
	 * @example __setAbrDate( )  return 2016-10-15^^^2016-10-16
 	 * @return "Y-m-d"^^^"Y-m-d"
	 */
    function __setAbrDate() {
        $day = date('d') + 14;
        $a = mktime(0, 0, 0, date('m'), $day, date('Y'));
        $b = date('w', $a);
        $check_in = date('Y-m-d', mktime(0, 0, 0, date('m'), ($day - $b) + 3, date('Y')));
        $check_out = date('Y-m-d', mktime(0, 0, 0, date('m'), ($day - $b) + 4, date('Y')));
        $result_date['check_in'] = $check_in;
        $result_date['check_out'] = $check_out;
        return $result_date;
    }
}

if (!function_exists('__dateCheck'))
{
	/**
	 * @param 체크인
	 * @param 몇일 후~~
	 * @example dateCheck('2016-10-12' , '3'  )  return 2016-10-15
 	 * @return "Y-m-d"
	 */
	function __dateCheck($p_dt, $p_num)
	{ // $p_num일 후의 날짜반환
	
		$res = date("Y-m-d", mktime(0,0,0
				, substr($p_dt,5,2)
				, substr($p_dt,8,2) + $p_num
				, substr($p_dt,0,4)));
		return $res;
	}
}

if (!function_exists('__mobile_date'))
{
    function __mobile_date($p_to , $p_from , $_gap = '')
    {
        $gap = ceil((strtotime($p_from)-strtotime($p_to))/86400);
        
        $in_date = __getYoil($p_to);
        $out_date = __getYoil($p_from);

        $_in = explode("-" , $p_to);
        $_out = explode("-" , $p_from);
        
//         $date_txt = $_in[1].".".$_in[2]."(".$in_date.")-".$_out[1].".".$_out[2]."(".$out_date.") /" .$gap."박";
        if (empty($_gap)){
            $date_txt = $_in[1].".".$_in[2]."(".$in_date.")-".$_out[1].".".$_out[2]."(".$out_date.")";
        } else {
            $date_txt = $_in[1].".".$_in[2]."(".$in_date.")-".$_out[1].".".$_out[2]."(".$out_date.") ," .$gap."박";
        }
        
        
        return $date_txt;
    }
}

if(!function_exists('__dateGap')) 
{	/**
	 * @param 체크인
	 * @param 퇴실일
	 * @example dateCheck('2016-10-12' , '2016-10-15'  )  return 3
 	 * @return int
	 */
	function __dateGap($p_to, $p_from)
	{ // 날짜사이 기간(박수)반환
		$p_to = str_replace('.', '-', $p_to);
		$p_from = str_replace('.', '-', $p_from);
		
	return ceil((strtotime($p_from)-strtotime($p_to))/86400);
	}
}

//if (!function_exists('__rc4crypt'))
//{//makoretaehoteli
////clinethotelnjoypowerbydata2k
//	function __rc4crypt($txt,$mode=1)
//	{ // encryption
//		include ('/mnt/public//middle/cfg/key_encrypt.php');
////		$enckey = "makoretaehoteli";
//		$tmp = "";
//		if (!$mode) $txt = base64_decode($txt);
//		$ctr = 0;
//		$cnt = strlen($txt);
//		$len = strlen($enckey);
//		for ($i=0; $i<$cnt; $i++) {
//			if ($ctr==$len) $ctr=0;
//			$tmp .= substr($txt,$i,1) ^ substr($enckey,$ctr,1);
//			$ctr++;
//		}
//		$tmp = ($mode) ? base64_encode($tmp) : $tmp;
//		return $tmp;
//	}
//}

if (!function_exists('__print_a'))
{
	function __print_a($p_ary  , $user ='')
	{ // array 출력
	    
        echo '<font color="orangered">';
        if (is_array($p_ary)) {
            echo '<xmp>',print_r($p_ary).'</xmp>';
        } else {
            echo $p_ary;
        }
        echo '</font>';

	}
}

if (!function_exists('xmp'))
{
    function xmp($text)
    {
        echo "<xmp>";
        print_r($text);
        echo "</xmp>\n";
    }
}

if (!function_exists('__echo'))
{
	function __echo($data)
	{
		echo "-------------------------<br>";
		echo $data."<<=========";
		echo "-------------------------<br>";
	}
	
}

if (!function_exists('__getUserInfo')) {
	/**
	 * @author ssam
	 * @param array $options
	 * @return array $cookies
	 * 인수 생략시 전체 쿠키리턴됨, rc4crypt 처리 속도가 느리므로 가능하면 필요한 쿠키명을 지정해서 쓰세여..
	 * example : __getUserInfo(array('user_ID', 'user_GN'));
	 */
	function __getUserInfo($options = array()) {
		$cookies = array(
			 "user_EM" => ""
			,"user_GN" => ""
			,"user_ID" => ""
			,"user_Kind" => ""
			,"user_NM" => ""
			,"user_NjoySeq" => ""
			,"user_RT" => ""
			,"user_loginType" => ""
		);
		
		// 빈 인수를 주면 define 된 전체 배열을 리턴
		if(empty($options)) $options = array_keys($cookies);
		
		// options 에 정의된 쿠키만 복호화 처리..
		foreach($options as $cookie_name) {
			if(isset($cookies[$cookie_name]) && $tmp = get_cookie($cookie_name)) {
				$cookies[$cookie_name] = __rc4crypt($tmp, 0);
			}
		}
		
		return $cookies;
	}
}

if (!function_exists('__getUserENO')){
	/**
	 * @param
	 * @author miss2_eu
	 * @return 유저 시퀀스
	 */
	function __getUserENO(){

		$user_eno = "";
	
		if (get_cookie('user_ENO')){
			$user_eno = __rc4crypt( get_cookie('user_ENO'), 0);
		}

		return $user_eno;
	}
}

if (!function_exists('__setTodayProduct')){
    function __setTodayProduct($img , $pcode , $title , $txt , $offer = '', $opt =''){
//        $cookie_total_cnt = count(get_cookie('tpro'));
//        if ($cookie_total_cnt > 5){
//            $is_pro = true;
//            foreach ($_COOKIE['tpro'] as $key => $val)
//            {
//                if ($is_pro){
//                    set_cookie("tpro[$key]", "", time()-3600);
//                    $is_pro = false;
//                }
//            }
//        }

        if (substr($pcode, 3, 1) == '_') {
            $pcode_gubun = 'kor';
        } else if (substr($pcode, 2, 1) == '_') {
            $pcode_gubun = 'abr';
        } else {
            return;
        }
        $cnt_max = 5;

        $a_cookie = get_cookie('tpro');
        $cookie_total_cnt = count($a_cookie);

        // 국내, 해외 각각 $cnt_max 개씩 제한, 이전에 본 것 순으로 삭제
        if ($cookie_total_cnt > 0) {
            $a_kor = array();
            $a_abr = array();
            $cooked = false;
            foreach ($a_cookie as $_key => $_cont) {
                if (!$cooked && $pcode == $_key) {
                    $cooked = true;
                    continue;
                }

                $tmp = explode('x|x', $_cont);
                if (substr($tmp[0], 3, 1) == '_') {
                    $a_kor[$_key] = $tmp[6];
                } else if (substr($tmp[0], 2, 1) == '_') {
                    $a_abr[$_key] = $tmp[6];
                }
            }

            $cnt_kor = count($a_kor);
            $cnt_abr = count($a_abr);
            if ($pcode_gubun == 'kor' && $cnt_kor >= $cnt_max) {
                asort($a_kor);
                $limit = ($cnt_kor - $cnt_max) + 1;
                $a_delete_cookie_kor = array_slice($a_kor, 0, $limit);
                if (count($a_delete_cookie_kor) > 0) foreach ($a_delete_cookie_kor as $_kor_key => $_kor_cont) {
                    setcookie('tpro[' . $_kor_key . ']', '', time() - 3600, '/nm');
                }
            }
            if ($pcode_gubun == 'abr' && $cnt_abr >= $cnt_max) {
                asort($a_abr);
                $limit = ($a_abr - $cnt_max) + 1;
                $a_delete_cookie_abr = array_slice($a_abr, 0, $limit);
                if (count($a_delete_cookie_abr) > 0) foreach ($a_delete_cookie_abr as $_abr_key => $_abr_cont) {
                    setcookie('tpro[' . $_abr_key . ']', '', time() - 3600, '/nm');
                }
            }
        }

        setcookie("tpro[$pcode]", $pcode."x|x".$img."x|x".$title."x|x".$txt."x|x".$offer."x|x".$opt."x|x".date('YmdHis'), time()+86400, '/nm'); // 1 year
    }
}

if (!function_exists('__delTodayProduct')){
	function __delTodayProduct($pcode){
		setcookie("tpro[$pcode]","",time()-3600,  '/nm');
		return "tpro[$pcode]";
	}
}

if (!function_exists('__getTodayProduct')){
    function __getTodayProduct() {
        $tmp_cookie_del = false;

        $a_cookie = get_cookie('tpro');
        $lastIdx = 0;
        $todayProduct = array();
        if(isset($a_cookie)) {
        	foreach ($a_cookie as $key ) {
        		$tpro = array_pop($a_cookie);
        		$quic_hotel = explode("x|x", $tpro);

                // 시간이 들어가지 않은게 있으면 삭제
                if ($quic_hotel[6] == '') {
                    $tmp_cookie_del = true;
                    break;
                }

                if (empty($quic_hotel[0]) || empty($quic_hotel[1]) || empty($quic_hotel[2])) {
                    continue;
                }

        		$todayProduct[$lastIdx]['code'] = $quic_hotel[0];
        		$todayProduct[$lastIdx]['img'] =$quic_hotel[1];
        		$todayProduct[$lastIdx]['title'] = $quic_hotel[2];
        		$todayProduct[$lastIdx]['txt'] = $quic_hotel[3];
        		$todayProduct[$lastIdx]['offer'] = $quic_hotel[4];
        		$todayProduct[$lastIdx]['opt'] = $quic_hotel[5];
        		$todayProduct[$lastIdx]['time'] = $quic_hotel[6];
        		$lastIdx++;
        	}
        }

        if ($tmp_cookie_del && isset($_COOKIE['tpro']) && is_array($_COOKIE['tpro'])) {
            setcookie('tpro', '', time() - 3600, '/nm');
            foreach ($_COOKIE['tpro'] as $_key => $_val) {
                setcookie('tpro[' . $_key . ']', '', time() - 3600, '/nm');
            }

            $todayProduct = array();
        }
        if (count($todayProduct) > 0) {
            usort($todayProduct, function($a, $b) {
                if ($a['time'] == $b['time']) {
                    return 0;
                }
                return ($a['time'] < $b['time']) ? 1 : -1;
            });
        }

        return $todayProduct;
    }
}

if (!function_exists('__getUserID')){
	
	/**
	 * @param 
	 * @author JEON
	 * @return 유저 아이디
	 */
	
	function __getUserID(){
		
		$user_id = "";
		
		if (get_cookie('user_ID')){
			$user_id = __rc4crypt( get_cookie('user_ID'), 0);
		} 
		
		return $user_id;
	}
}
if (!function_exists('__getUserKind')){
	
	/**
	 * @param 
	 * @author JEON
	 * @return 유저 아이디
	 */
	
    function __getUserKind(){
		
        $user_Kind = "";
		
		if (get_cookie('user_Kind')){
		    $user_Kind = __rc4crypt( get_cookie('user_Kind'), 0);
		} 
		
		return $user_Kind;
	}
}
if (!function_exists('__getUserSeq')){
	
	/**
	 * @param 
	 * @author JEON
	 * @return 유저 아이디
	 */
	
    function __getUserSeq(){
		
        $user_seq = "";
		
		if (get_cookie('user_NjoySeq')){
		    $user_seq = __rc4crypt( get_cookie('user_NjoySeq'), 0);
		} 
		
		return $user_seq;
	}
}


if (!function_exists('__getUserMGBN')){
	/**
	 * @param 
	 * @author JEON
	 * @return 유저 아이디
	 */
	
	function __getUserMGBN(){
			
		$user_mem_gbn = "";
		
/* 		if (get_cookie('user_MGBN')){
			$user_mem_gbn = __rc4crypt( get_cookie('user_MGBN'), 0);
		}  */
		if (get_cookie('user_GN')){
			$user_mem_gbn = __rc4crypt( get_cookie('user_GN'), 0);
		}	
		return $user_mem_gbn;
	}
}




if (!function_exists('__getUserNM')){

	/**
	 * @param
	 * @author miss2_eu
	 * @return 유저 이름
	 */
	function __getUserNM(){

		$user_nm = "";

		if (get_cookie('user_NM')){
			$user_nm = __rc4crypt( get_cookie('user_NM'), 0);
		}

		return $user_nm;
	}
}

if (!function_exists('__getCookie')){
	
	/**
	 * @param 쿠키이름 , 암호화 여부
	 * @author JEON
	 * @return 쿠키데이터
	 */
	function __getCookie($name , $rc4 = false){
		
		$rsult = "";
		if (get_cookie($name)){
			if ($rc4){
				$rsult = __rc4crypt( get_cookie($name), 0);
			} else {
				$rsult =  get_cookie($name);
			}
		}
		return $rsult;
	}
}


if (!function_exists('__user_info')){
    function __user_info( ){
        $user_info = array();
        $_info = array("user_ID","user_NjoySeq","user_Kind","user_loginType","user_NM","user_GN","user_RT","user_EM");
        foreach ($_info as $key) {
            $user_info[$key] =__rc4crypt( get_cookie($key), 0);
        }
        return $user_info;
    
    }
}





if (!function_exists('__getYoil')){
	
	/**
	 * @param yyyy-mm-dd
	 * @param Language 
	 * @author JEON
	 * @return 언어별 요일 리턴
	 */
	function __getYoil($day , $lang = ""){
		
		if ($lang == "kor"){
			$yoil = array("일","월","화","수","목","금","토");
		} else if ($lang == "eng"){
			$yoil = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
		}  else if ($lang == "jpn"){
			$yoil = array("日","月","火","水","木","金","土");
		} else if ($lang == "chn"){
			$yoil = array("周日","周一","周二","周三","周四","周五","周六");
		} else {
			$yoil = array("일","월","화","수","목","금","토");
		}
		
		return ($yoil[date('w', strtotime($day))]);
		
	}
	
}

if(!function_exists('__statusLanguage')) {
	/**
	 * 예약상태 확인
	 * @param string $status
	 * @example __statusLanguage(F1)
	 * @return string 예약대기;
	 */
	function __statusLanguage($status) {
		$returnData = "";
		
		switch($status) {
			case "C1":
				$returnData = lang('C1'); //lang('C1');   // 예약취소
				break;
			case "C2":
				$returnData = lang('C2');  // 예약불가 -> 접수실패
				break;
			case "C3":
				$returnData = lang('C3'); // 취소대기
				break;
			case "C4":
				$returnData = lang('C4');  // 취소수수료
				break;
			case "F1":
				$returnData = lang('F1');  // 예약완료
				break;
			case "F2":
				$returnData = lang('F2'); // 정산대기 -> 접수대기
				break;
			case "F3":
				$returnData = lang('F3'); // 입금대기 -> 예약대기
				break;
			case "F4":
				$returnData = lang('F4');  // 예약대기
				break;
			case "F5":
				$returnData = lang('F5');  // 예약확정
				break;
			case "F6":
				$returnData = lang('F6');  // 부분확정
				break;
			case "E1":
				$returnData = lang('E1');  // 예약삭제 -> 예약취소
				break;
			case "E2":
				$returnData = lang('E2');  // 환불
				break;
			case "E3":
				$returnData = lang('E3');  // 기타 -> 예약대기
				break;
			case "E4":
				$returnData = lang('E4');  // 접수실패
				break;
			case "E5":
				$returnData = lang('E5');  // 결제실패
				break;
			default:
				$returnData = lang('ect'); // 접수대기
		}
		return $returnData;
	}
}

/**
 * 통화구분
 * @param $curr
 * @example __currType( kor )
 * @return ￦
 */
if(!function_exists('__currType')) {
	function __currType ($curr) {
		$returnCurr= "";
		if( strtolower($curr) == 'krw' ) {
			$returnCurr ="￦"; //\
		} else if ( strtolower($curr) == 'usd' ) {
			$returnCurr = "＄";
		} else if (strtolower($curr) == 'jpy' || strtolower($curr) == 'cny') {
			$returnCurr = "￥";
		}
		return $returnCurr;
	}
}

/**
 * 언어별 결제방법
 */
if(!function_exists('__pgateLanguage')) {
	function __pgateLanguage($pgate, $k_agent) {
		$returnData = "";
		switch($pgate){
			case "해외카드" :
				$returnData = lang('myorder_abr_credit');
				break;
			case "신용카드" :
				$returnData = lang('myorder_m_credit');
				break;
			case "제휴사" :
					switch($k_agent){
						case "auction" :
							$returnData = '옥션';
							break;
						case "gm5707" :
							$returnData = '지마켓';
							break;
						case "11street03" :
							$returnData = '11번가';
							break;
						default :
							$returnData = empty($k_agent)?  lang('myorder_m_credit') :  '제휴사결제';
					}
				break;
			default : // 국내카드(해외 명칭), 신용카드(국내 명칭)
				$returnData = lang('myorder_credit');
		}
		return $returnData;
	}
}


/**
 * @author miss2_eu
 * @param 체크인
 * @param 체크아웃
 * @param (박)수
 * @param kor
 * @example __mk_resDays_text( 2016-10-01, 2016-10-03, 2, kor )
 * @return 2015.08.01~2015.08.31(30박)
 */
if(!function_exists('__mk_resDays_text')) {
	function __mk_resDays_text ($stt, $end, $diff, $lang) {

		$returnDays ="";
		if( in_array($lang, array('kor','jpn','chn')) ) {
			$returnDays = $stt. '~' . $end .'('.$diff. lang('myorder_nights') .')';
		} else if($lang == 'eng') {

			
			$returnDays = __mkEngDate($stt) ." ~ ". __mkEngDate($end) ." (". $diff. " ". lang('myorder_nights') .")";
			//  $returnDays = Feb 27, 2016 ~ Feb 27, 2016 (2 night stay)
		}
		
		return $returnDays;
		
	}
	
}

if (!function_exists('__exchangeMarkup')){
	
	/**
	 * 판매가 산출 함수
	 * 1. 해당 화폐 매입단가로 적용
	 * 2. 입력 마크업 적용
	 * 3. 화폐별 판매가 산출
	 * @param string $p_price
	 * @param string $curency
	 * @param string $markup
	 */
	function __exchangeMarkup($p_price, $curency='krw', $markup, $tax=21, $exrate='') {
	
		if($p_price > 0 && !empty($curency) && !empty($markup)) {
	
			//환율 가져오기
			include("/mnt/public/public_html/mid_new/abr/exchange.php");
	
			//화폐코드별 환율 가져오기
			if ($curency) {
				if($exrate == '') {
					$curency        = strtolower($curency);
					if ($curency == "usd" || $curency == "cny" || $curency == "cay" || $curency == "eur") {
						$ex_def     = ${"ex_".$curency."_def"};
					} else if($curency == "jpy") {
						$ex_def     = $ex_jpy_def/100;
					} else if($curency == "krw") {
						$ex_def     = 1;
					} else {
						$ex_def     = $ex_usd_def;
					}
				} else {
					$ex_def = $exrate;
				}
			} else {
				$ex_def = $ex_usd_def;
			}
	
			//입력요금 정리
			$p_price = str_replace(",","",number_format($p_price,2));
			//입력마크업 적용
			$markup_price = round($p_price + $p_price*$markup*0.01,2);
			//환율 적용
			$rst = round($markup_price / $ex_def, 2);
			//세금율 정리
			if(!empty($tax)) {
				$tax = 1 + ($tax*0.01);
			}
			/*
			 (
				[pp] => 99000.00 입력금액
				[mp] => 110880 마크업
				[ex] => 1 환율
				[pr] => 110880 마크업 + 환율
				[ph] => 91636.36 객실 + 환율
				[hp] => 19243.64 세봉 + 환율
				[op] => 99000 입력요금 + 환율
				[mh] => 19243.64 krw 세봉
				[mr] => 91636.36 krw 객실
			 )
			 */
			$res['pp'] = $p_price;                                      // 입력요금
			$res['mp'] = $markup_price;                                 // 마크업요금
			$res['ex'] = $ex_def;                                       // 환율
			$res['pr'] = $rst;                                          // 마크업/환율 적용된 요금
			$res['ph'] = round($res['pr']/$tax, 2);                     // 환율적용된요금 - 세금봉사료 21% : 객실
			$res['hp'] = $res['pr'] - $res['ph'];                       // 세금봉사료 21% : 봉사료
			$res['op'] = $p_price / $ex_def;                            // 입력 요금에 환율 적용한 금액(마크업안붙임)
			$res['mh'] = $markup_price - round($markup_price/$tax,2);   // 환율적용 안된 세금봉사료 21%
			$res['mr'] = round($markup_price/$tax,2);                   // 환율적용 안된 세금봉사료 21%를 제외한 요금
			$res['ap'] = round($res['op']/$tax, 2);                     // 환율 적용한 객실금액 (ph 에서 마크업 제외 - 이미 마크업 됐다는 전제하에) 2015-04-28 추가
			$res['bp'] = $res['op'] - $res['ap'];                       // 환율 적용한 세금봉사료 (hp에서 마크업 제외 - 이미 마크업 됐다는 전제하에) 2015-04-28 추가
		} else {
			$res['pp'] = 0;
			$res['mp'] = 0;
			$res['ex'] = 0;
			$res['pr'] = 0;
			$res['sc'] = 0;
			$res['tx'] = 0;
			$res['tp'] = 0;
			$res['ph'] = 0;
			$res['hp'] = 0;
			$res['mh'] = 0;
			$res['mr'] = 0;
			$res['ap'] = 0;
			$res['bp'] = 0;
		}
		return $res;
	}
}

/**
 * Int 값 유효성 검사
 * @param value
 * @return Int
 */
if (!function_exists('__getInt')){
	function __getInt($no){
		return (int)$no;
	}
}

if (!function_exists('__extra_inverse_replace')){

	function __extra_inverse_replace($str)
	{ // 따옴표 역치환
		$str = str_replace("&acute;","'",$str);
		$str = str_replace("‘","'",$str);
		$str = str_replace("’","'",$str);
		$str = str_replace("´","'",$str);
		$str = str_replace("&quot;","\"",$str);
		$str = str_replace("“","\"",$str);
		$str = str_replace("”","\"",$str);
		$str = str_replace("&lt;","<",$str);
		$str = str_replace("&gt;",">",$str);
		return $str;

	}

}


if(!function_exists('__obj2ary')) {
	function __obj2ary($ary, $idx=array())
	{
		$res = array();
		if (is_object($ary)) {
			$ary = get_object_vars($ary);
		}
		if (is_array($ary)) {
			foreach ($ary as $key => $val) {
				if (is_object($val) || is_array($val)) {
					$val = __obj2ary($val, $idx);
				}
				if (in_array($key, $idx)) {
					continue;
				}
				$res[$key] = $val;
			}
		}
		return $res;
	}
}

/**
 * @param yyyy-dd-mm
 * @return (string) Feb 27, 2016 
 */
if(!function_exists('__mkEngDate')) {
	function __mkEngDate($eng_date) {
		
		$BAmonth = config_item('BAmonth'); // 함수안에선  $this를 쓸수없으니.. config_var안에 공통변수를 사용하고싶을때 config_item('')로 사용.

		$v_year = substr($eng_date,0,4);
		$v_mon = substr($eng_date,5,2);
		$v_day = substr($eng_date, 8,2);
		
		$res_dt = $BAmonth[$v_mon]." ".$v_day.", ".$v_year;
		return $res_dt;
	}
}



if(!function_exists('__getCancelFee')) {
	function __getCancelFee($p_oid,$p_rule='')
	{ // 취소수수료 반환

		if ($p_rule) {
			$rule = $p_rule;
			$tday = substr($p_oid,0,10);
			$time = substr($p_oid,-2).'00';
		} else {
			$rule = __getCancelRule($p_oid);
			if ($rule) {
				$tday = date("Y-m-d");
				$time = date("Hi");
			} else {
				return false;
			}
		}
		
		$res = 100;
		$loop = count($rule);
		for ($i=1;$i<$loop;$i++) {
			$cday = (isset($rule[$i]['cndate']))? $rule[$i]['cndate'] : $rule['cndate'] ; // 배열 빈값일때 에러나서 잠시 $rue['cndate']추가
			$cfee = (isset($rule[$i]['cnfee']))? $rule[$i]['cnfee'] : $rule['cnfee'];// 배열 빈값일때 에러나서 잠시 $rue['cnfee']추가

			if ($tday>=$cday) {
				$tmp1 = $tday.' '.$time;
				$tmp2 = $cday.' 1800';
				if ($tmp2>=$tmp1) {
					$res = $cfee;
				}
				break;
			} else {
				$res = $cfee;
			}
		}
		
		return $res;
	}
}


/**
 * 취소규정 
 * @param oid
 * @return array
 */
if(!function_exists('__getCancelRule')) {
	function __getCancelRule( $oid ) {
		
		
		$dir  = '20'.substr($oid,-2).'/'.substr($oid,5,4);
		$path = dirname(FCPATH).'/cRule/'.$dir;
		$file = $path.'/'.$oid.'.xml';


		
		if (file_exists($file)) {
			$xml = simplexml_load_file($file);
			$ary = __obj2ary($xml);
			extract($ary);
			if (@is_array($cnRule['cndate'])) {
				$y = substr($oid,9,2);
				$m = substr($oid,5,2);
				$d = substr($oid,7,2);
				$ndt = '20'.$y.'-'.$m.'-'.$d;
				$cnRule['cndate'] = $ndt;
			}
			$res = $cnRule;
		} else {
			$res = false;
		}
		return $res;
	}
}


/**
 * @param $v_oid (접수번호), $lang (설정 언어)
 * @return array()
 */
if(!function_exists('__mkCancelView')) {
	function __mkCancelView($p_ary, $lang='kor') 
	{
		
		if (!is_array($p_ary)) {
			return false;
		}
		
		$a_able = array(
			   "Y"=> lang('cancel_able1') // "취소가능"
			  ,"O"=>lang('cancel_able2') // "취소가능(수수료 부과)"
			  ,"N"=> lang('cancel_non_able') // "취소불가"
		);
		
			if (@is_string($p_ary['cntime'])) {
				$gbn = $p_ary['cndate'];
				$abl = $a_able[$p_ary['cnable']];
				$fee = $p_ary['cnfee'];
				$fee = $fee? $fee.'%' : "0%"; // '없음';

				$ary[0]['cdt'] = $gbn;
				$ary[0]['gbn'] = "";
				$ary[0]['abl'] = $abl;
				$ary[0]['fee'] = $fee;
				
			} else {
				$old = 'x';
				$v_sum = 0;
				$v_loop = count($p_ary);
				for ($i=0; $i<$ip=$v_loop; $i++) {
					$v_sum = $v_sum + $p_ary[$i]['cnfee'];
				}
				$v_chk = $v_sum / $v_loop;
		
				for ($i=0; $i<$ip=$v_loop; $i++) {
					$p_ary[$i]['cndate'] = ($lang != 'eng')? $p_ary[$i]['cndate'] : __mkEngDate($p_ary[$i]['cndate']);
					$cndate = $p_ary[$i]['cndate'];
					$cntime = $p_ary[$i]['cntime'] . lang('time_hour'); // '시';
					$cnfee = $p_ary[$i]['cnfee'];
					if ($cndate) {
						$cndate = $cndate . lang('cancel_date_word'); // '일 ';
					}

					$gbn = ($cnfee < 100) ? lang('cancel_until') : lang('cancel_from'); // '까지':'부터';


					$tmp = $p_ary[$i]['cnable'];
					$abl = $a_able[$tmp];
					$cnfee = $cnfee? $cnfee.'%': "없음"; // '없음';
					if ($v_chk==100) {
						$a_cdt[0] = $cndate;
						$a_gbn[0] = $gbn;
						$a_abl[0] = $abl;
						$a_fee[0] = $cnfee;
					} else {
						if ($cnfee!=$old) {
							if ($old==100) {
								$a_cdt[0] = $cndate;
							}
							$a_cdt[] = $cndate;
							if($lang == "kor" || $lang == "chn" || $lang == "jpn") {
								$a_gbn[] =$cntime." ".$gbn;
							} else {
								$a_gbn[] = $gbn. " " .$cntime;
							}
							$a_abl[] = $abl;
							$a_fee[] = $cnfee;
						}
					}
					$old = $cnfee;
				}
		
				for ($i=1; $i<=$ip=count($a_gbn); $i++) {
					$j = $ip - $i;
					$k = $i - 1;
					$ary[$k]['cdt'] = str_replace('-', '.', $a_cdt[$j]);
					$ary[$k]['gbn'] = $a_gbn[$j];
					$ary[$k]['abl'] = $a_abl[$j];
					$ary[$k]['fee'] = $a_fee[$j];
				}
			}

				$res = $ary;

			return $res;
		
	}
}



function __pivotLeft($data) {
	$_data = array();
	
	$data= array_reverse($data);
	foreach($data as $key1 => $val1) {
		foreach($val1 as $key2 => $val2) {

			$_data[$key2][$key1] = $val2;

		}

	}
	return $_data;

}



function __pivotRight($data) {

	foreach($data as $key1 => $val1) {

		$val1 = array_reverse($val1);
		foreach($val1 as $key2 => $val2) {

			$_data[$key2][$key1] = $val2;

		}

	}

	return$_data;

}

/**
 *  @autor liha
 *
 * 캘린더 <header> 에서 보여주는 check in , out 포맷 변경 ex) 
 * @param dataType : DATE
 *  __FormatDate('2016-11-12', '2016-11-13')
 * 
 * @return value
 * $lang == 'eng' => ex) Dec 23 ~ Dec 24, 2016
 * $lang == 'kor' || jpn || chn  =>ex)211.04.금
 * 
 */
function __FormatDate($checkin, $checkout) { 

	$lang = __getCookie('lang');
	
	if(!empty($checkin) && !empty($checkout))
	{
		if($lang == "eng") 
		{
			$format_In = date("M d", strtotime($checkin));
			$format_Out = date("M d, Y", strtotime($checkout));
				
		}
		else
		{
			$format_In = date('m. d.', strtotime($checkin));
			$format_In .= __getYoil($checkin, $lang);
			
			$format_Out = date('m. d.', strtotime($checkout));
			$format_Out .= __getYoil($checkout, $lang);

		}
		
		$result = Array("checkin" => $format_In, "checkout" => $format_Out);
		return $result;
	


	}
	else
	{
		return $totdy = date("Y-m-d"); //오늘날짜
	}

	//return$_data;

}

function __FormatDate2($checkin) {

	$lang = __getCookie('lang');

	if(!empty($checkin) && !empty($checkout))
	{
		if($lang == "eng")
		{
			$format_In = date("M d", strtotime($checkin));
			//$format_Out = date("M d, Y", strtotime($checkout));

		}
		else
		{
			$format_In = date('m. d.', strtotime($checkin));
			$format_In .= __getYoil($checkin, $lang);
				
			//$format_Out = date('m. d.', strtotime($checkout));
			//$format_Out .= __getYoil($checkout, $lang);

		}

		//$result = Array("checkin" => $format_In, "checkout" => $format_Out);
		return $format_In;//$result;



	}
	else
	{
		return $totdy = date("Y-m-d"); //오늘날짜
	}

	//return$_data;

}

 function __current_url()
 {
     return current_url()."?".$_SERVER['QUERY_STRING'];
 }
 

 if (!function_exists('__alert')){
	// 경고메세지를 경고창으로
	function __alert($msg='', $url='') {
	 $CI =& get_instance();
	 
	 if (!$msg) {
		 $msg = '올바른 방법으로 이용해 주십시오.';
	 }
	 
	 echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=".$CI->config->item('charset')."\">";
	 echo "<script type='text/javascript'>alert('".$msg."');";
		if ($url) { 
			echo "location.replace('".$url."');";
		} else { 
			  echo "history.go(-1);";
		}
	 echo "</script>";
	 exit;
	}

 }
 
 

 //인앱체크
 if( !function_exists('__checkInApp')){
     
     function __checkInApp(){
         $result = false;
         if((strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile/') !== false) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari/') == false) ) {
             $result = true;
         } elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
             $result = true;
         } elseif( strpos( strtoupper( $_SERVER['HTTP_USER_AGENT'] ), 'NAVER(INAPP' ) !== false ){
             $result = true;
         }
         
         return $result;
     }
 }
 
 
