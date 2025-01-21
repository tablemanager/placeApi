<?php
function makexml_20170214($RTN_DIV,$RTN_MSG){

	$xml = '<?xml version="1.0" encoding="UTF-8" ?>
	<data>
		<rtn_div>'.$RTN_DIV.'</rtn_div>
		<rtn_msg>'.$RTN_MSG.'</rtn_msg>
	</data>';

	if($RTN_DIV == 'S'){
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>
				<data>
					<rtn_div>'.$RTN_DIV.'</rtn_div>
				</data>';
	}
	return trim($xml);
}

function makexml($RTN_DIV,$RTN_MSG){
	$xml = '<?xml version="1.0" encoding="UTF-8" ?>
	<data>
		<rtn_div>'.$RTN_DIV.'</rtn_div>
		<rtn_msg>'.$RTN_MSG.'</rtn_msg>
	</data>';
	return trim($xml);
}


function verify_param($val)
{

}

function set_EVreserv($pincode,$nm,$hp,$v_orderno,$v_sellcode){

	global $conn_cms;
	global $conn_bar;

	//쿠폰 조회
	$row = get_CouponState($pincode);

	if($row['no_coupon']){

		// 쿠폰 번호가 있으면
		if($row['syncfac_result'] != null){

			$rstate = "RC";
			$ecode  = "1001"; // 기주문 코드
			$xml =  makexml('E',$v_orderno,$rstate,"N","Inserted Order",$ecode);

		}else{

			if($nm) $v_usernm = $nm;
			if($hp) $v_userhp = $hp;

			// 리얼 반영시 쿼리 수정 syncfac_result = 'S'
			$rsql = "update pcmsdb.cms_extcoupon set
							syncfac_result = 'S',
							cus_nm = '$v_usernm',
							cus_hp = '$v_userhp',
							order_itemcode = '$v_sellcode',
							order_no = '$v_orderno',
							date_order = now()
					 where no_coupon = '$pincode'
						   and syncfac_result is null limit 1";

			if(mysql_query($rsql,$conn_cms)){
				$bsql = "update spadb.barev_2014 set syncresult='R', orderno = '$v_orderno', regdate=now(), usernm='$v_usernm', hp='$v_userhp' where no ='$pincode' limit 1";
				mysql_query($bsql,$conn_bar);

				$rstate = "RC";
				$ecode = "1000"; // 주문성공
				$xml = makexml('S',$v_orderno,$rstate,"N","Order Success",$ecode);
			}else{
				$rstate = "RC";
				$ecode = "9999"; // DB에러
				$xml = makexml('E',$v_orderno,$rstate,"N","Order Fail",$ecode);
			}
		}

	}else{
		// 쿠폰 번호가 없을시
		$rstate = "RQ";
		$ecode  = "8000"; // 없는 쿠폰번호
		$xml =  makexml('E',$v_orderno,$rstate,"N","Order Fail",$ecode);
	}
	return $xml;

}

function set_EVcancel($pincode){

	global $conn_cms;
	global $conn_bar;

	$row = get_CouponState($pincode);

	if($row['no_coupon'] and $row['syncfac_result'] == 'S'){
		if($row['state_use'] == 'Y'){
			$rstate="CC";
			$ecode  = "2004"; // 사용되어 취소 할수 없는 주문
			$xml =  makexml('E',$row['order_no'],$rstate,$row['state_use'],"Cancel Fail",$ecode);
		}elseif($row['state_use'] == 'C'){
			$rstate="CC";
			$ecode  = "2001"; // 기취소 주문
			$xml =  makexml('E',$row['order_no'],$rstate,$row['state_use'],"Cancel Fail",$ecode);
		}else{

			// 리얼 반영시 쿼리 수정 syncfac_result='S',  state_use ='C'
			$csqll="update pcmsdb.cms_extcoupon set
									   syncfac_result='S',  state_use ='C'
								 where no_coupon ='$pincode'
									   and state_use ='N'
									   and syncfac_result in ('S','O') limit 1";
			$cres = mysql_query($csqll,$conn_cms);

			if($cres){

				$bsql = "update spadb.barev_2014 set syncresult='C', canceldate=now() where no ='$pincode' limit 1";
				mysql_query($bsql,$conn_bar);

				$rstate = "CC";
				$ecode = "2000";
				$xml = makexml('S',$row['order_no'],$rstate,"N","Cancel Success",$ecode);
			}else{
				$rstate = "CC";
				$ecode = "9999"; // DB에러
				$xml = makexml('E',$row['order_no'],$rstate,"N","Cancel Fail",$ecode);
			}
		}
	}else{
		// 쿠폰 번호가 없을시
		$rstate = "RQ";
		$ecode  = "8000"; // 없는 쿠폰번호
		$xml =  makexml('E',$v_orderno,$rstate,"N","Unknown Order",$ecode);
	}

	return $xml;
}

function get_EVinfo($pincode){

	global $conn_cms;
	$row = get_CouponState($pincode);

	if($row['no_coupon'] and $row['syncfac_result'] == 'S'){
		switch($row['syncfac_result']){
			case 'R':
			case 'C':
			case 'S':
				// 처리된 주문
				if($row['state_use']=='C'){
					$rstate='RQ';
					$ecode = "2000";
					$xml = makexml('S',$row['order_no'],$rstate,$row['state_use'],"Canceled Order",$ecode);
				}else{
					$rstate='RQ';
					$ecode = "1000";
					$xml = makexml('S',$row['order_no'],$rstate,$row['state_use'],"Confirmed Order:".$row['state_use'],$ecode);
				}
			break;
			default:
				$rstate='RQ';
				$ecode = "8000"; // 없는 쿠폰번호
				$xml = makexml('E',$row['order_no'],$rstate,"N","Unknown Order",$ecode);
			break;
		}
	}else{
		// 쿠폰 번호가 없을시
		$rstate = "RQ";
		$ecode  = "8000"; // 없는 쿠폰번호
		$xml =  makexml('E',$v_orderno,$rstate,"Unknown Order","N",$ecode);
	}
	return $xml;
}


function set_PMreserv($v_pincode){
	global $conn_cms;

}

function set_PMcancel($v_pincode){
	global $conn_cms;

}

function get_PMinfo($v_pincode){
	global $conn_cms;

}

function EncodeAES($v_pincode){

	$enval = exec("java -classpath /usr/lib/jvm/java-7-oracle/lib/org-apache-commons-codec.jar:/home/sys.placem.co.kr/php_script EvAesEn $v_pincode");

	return $enval;
}

function DecodeAES($v_pincode){

	$enval = exec("java -classpath /usr/lib/jvm/java-7-oracle/lib/org-apache-commons-codec.jar:/home/sys.placem.co.kr/php_script EvAesDe $v_pincode");

	return $enval;
}


function CJDecodeAES($v_pincode){

	$enval = exec("java -classpath /usr/lib/jvm/java-7-oracle/lib/jce.jar:/usr/lib/jvm/java-7-oracle/lib/org-apache-commons-codec.jar:/home/sys.placem.co.kr/php_script cjdes $v_pincode");

	return $enval;
}


// 암호화해독
function ase_decrypt($val)
{
    if(!$val) return '';

    $salt = 'Wow1daY';
    $mode=MCRYPT_MODE_ECB;
    $enc=MCRYPT_RIJNDAEL_128;

    $iv = mcrypt_create_iv(mcrypt_get_iv_size($enc, $mode), MCRYPT_RAND);
    $val = mcrypt_decrypt($enc, $salt, pack("H*", $val), $mode, $iv );
    return rtrim($val,$val[strlen($val)-1]);
}

// 암호화함수
function ase_encrypt($val)
{

    $salt = 'Wow1daY';
    $mode=MCRYPT_MODE_ECB;
    $enc=MCRYPT_RIJNDAEL_128;
    $iv = mcrypt_create_iv(mcrypt_get_iv_size($enc, $mode),  MCRYPT_RAND);
    $pad_len= 16-(strlen($val) % 16);
    $val=str_pad($val, (16*(floor(strlen($val) / 16)+1)), chr($pad_len));
    return strtoupper( bin2hex(mcrypt_encrypt($enc, $salt, $val, $mode, $iv)));
}

function curl_get( $curl, $url, $cookiefile) {
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiefile);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiefile);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($curl);
    return $data;
}

//curl post

function curl_post( $curl, $url, $cookiefile, $post) {
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiefile);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiefile);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($curl);
    return $data;
}

function insertorder_pcms($itemmt_id,$qty,$ousedate,$state,$usernm,$userhp,$desc_1,$desc_2) {

	global $conn_pcms;

	$mdate = date("Y-m-d");

	$porderno = get_pcmsorderno();
	if(!$qty) $qty= 1 ;
	$irow = get_pcmsitem($itemmt_id); // 상품 정보
	$prow = get_pcmsprice($itemmt_id,$ousedate); // 가격정보

    $pricemt_id = $prow[id]; // 가격 id
	$grmt_id = $irow[grmt_id]; // 업체 id
	$grnm = $irow[grnm]; // 업체명
	$jpmt_id = $irow[jpmt_id]; // 시설 id
	$jpnm = $irow[jpnm]; // 시설명
	$item_gu = $irow[nm]; // 상품명,구분
	$ohp=get_opthp($userhp); // 휴대전화 번호 정규화
	$p_hp=$ohp[1]."-".$ohp[2]."-".$ohp[3];
	$gongamt = $prow[gongdan]; // 공급가
	$saipamt =$prow[saipdan]; // 사입가
    $accamt = $gongamt; //판매액
//	$chrate = get_pcmschrate($pcmsitem_id,"2558");
	$chpay = $accamt-($accamt*(8/100)); // 채널 수수료
	$man1 = $qty;
	$man2 = 0;

	$ordertbl="terp_placem.ordermts";

    $ordersql="insert $ordertbl set
						Created_at = now(),
						Updated_at = now(),
						orderno = '$porderno',
						usermt_id='-1',
						grmt_id = '$grmt_id',
						grnm = '$grnm',
						jpmt_id = '$jpmt_id',
						jpnm = '$jpnm',
						itemmt_id = '$itemmt_id' ,
						itemnm = '$item_gu',
						itemgu ='단품',
						gunm = '스파',
						pricemt_id = '$pricemt_id',
						ch_id = '2613',
						chnm = '$usernm',
						ch_rate = '8',
						ch_pay = '$chpay',
						usedate = '$ousedate',
						mdate = '$mdate',
						man1 = '$man1',
						man2 = '0',
						dan1 = '$gongamt',
						dan2 = '',
						amt = '$amt',
						roomsu = 1,
						resdays = 1,
						accamt = '$accamt',
						state = '확정',
						usernm = '$usernm',
						hp = hex(aes_encrypt( '$p_hp', 'Wow1daY' )),
						bigo = '$desc_1',
						damnm = '시스템',
						usernm2 = '$usernm',
						hp2 = hex(aes_encrypt( '$p_hp', 'Wow1daY' )),
						site = 'EBA',
						usegu = '2',
						gongamt = '$gongamt',
						saipamt = '$saipamt',
						mechstate = '정산대기',
						meipstate = '정산대기',
						priceinfo = '$pricemt_id',
						barcode_no = '$desc_2',
						dammemo ='',
						ch_orderno = '$desc_1'";

	mysql_query($ordersql, $conn_pcms);

	$orsql = "select * from terp_placem.ordermts where ch_orderno = '$desc_1' limit 1";
	$orres = mysql_query($orsql, $conn_pcms);
	$orrow = mysql_fetch_array($orres);

	return $orrow['orderno'];
}


function get_barcode($gu,$orderno,$usernm,$hp){

	global $conn_bar;
	global $conn_cms;

	if(!$gu) return 0;
	if(!$orderno) return 0;

	$barsql = "update spadb.barev_2014 set usernm = '$usernm', orderno='$orderno', hp ='$hp', syncresult = 'R' , regdate = now() where gu ='$gu' and  syncresult is null limit 1";
	mysql_query($barsql,$conn_bar);

	$bsql = "select * from spadb.barev_2014 where gu ='$gu' and orderno= '$orderno' order by id desc limit 1";
	$bres = mysql_query($bsql,$conn_bar);
	$brow = mysql_fetch_array($bres);

	$csql ="update pcmsdb.ordermts_ext set pcms_couponno ='$brow[no]' where order_num ='$orderno' limit 1 ";
	mysql_query($csql,$conn_cms);

	return $brow['no'];
}


function get_barcodetest($gu,$orderno,$usernm,$hp){

	global $conn_bar;
	global $conn_cms;

	if(!$gu) return 0;
	if(!$orderno) return 0;

	$no = "Z".str_pad(rand(7904,99000), 11, "0", STR_PAD_LEFT);

//	$no = "S06102220450"; //2차
//	$no = "S06102277240"; //1차
//	$no = "S06102943850"; //3차
//	$no = "S06102402630"; //4차

	$csql ="update pcmsdb.ordermts_ext set pcms_couponno ='$no' where order_num ='$orderno' limit 1 ";
	@mysql_query($csql,$conn_cms);

	return $no;
}

function insertorder_test($itemmt_id,$ousedate,$state,$usernm,$userhp,$desc_1,$desc_2) {
	$tdate=date('Ymd');
	return $oresno = $tdate.str_pad(rand(7904,99000), 5, "0", STR_PAD_LEFT);
}

function get_pcmsitem($pcmsitem_id)
{
	global $conn_pcms;

	$isql="select * from terp_placem.itemmts where id='$pcmsitem_id'";
	$ires = mysql_query($isql, $conn_pcms);
	return @mysql_fetch_array($ires);
}

function get_opthp($optstr)
{
	$pattern = "/([0]{1}[1]{1}[016789]{1})([0-9]{3,4})([0-9]{4})/";

	$optstr = str_replace(" ", "",$optstr);
	$optstr = str_replace(".", "",$optstr);
	$optstr = str_replace("-", "",$optstr);
	$optstr = str_replace("_", "",$optstr);
	$optstr = str_replace(")", "",$optstr);


	preg_match($pattern,$optstr,$hp);

	return $hp;
}


function get_pcmsprice($pcmsitem_id,$usedate)
{
	global $conn_pcms;
	$pdate = explode("-",$usedate);
 	$psql="SELECT * FROM terp_placem.pricemts WHERE itemmt_id = '$pcmsitem_id' AND yy ='$pdate[0]' AND mm ='$pdate[1]' AND dd ='$pdate[2]'";
	$pres = mysql_query($psql, $conn_pcms);

	return @mysql_fetch_array($pres);
}

// PCMS 채널 수수료율
function get_pcmschrate($pcmsitem_id,$ch_id)
{
	  global $conn_pcms;
 	  $rsql="SELECT * FROM terp_placem.divmts where itemmt_id='$pcmsitem_id' and grmt_id ='$ch_id'";
	  $rres = mysql_query($rsql, $conn);
	  $rrow = mysql_fetch_array($rres);

	  if($rrow[rate]){
		  return $rrow[rate]; // 채널 수수료율
	  }else{
		  return "E";
	  }
}

// PCMS 주문 번호 생성
function get_pcmsorderno()
{
	global $conn_pcms;
	$orderdate = date("Ymd");
	$orkey = sprintf('%X',mt_rand(1,15));

    $qry = "SELECT orderno,SUBSTRING(orderno, 11, 4) ono FROM terp_placem.ordermts WHERE orderno like '%$orderdate%'";

    $tbl = @mysql_query($qry,$conn_pcms);
    $onrow = @mysql_fetch_array($tbl);
    $ordernocnt=mysql_num_rows($tbl);

    if($ordernocnt == 0) $orderno = $orderdate."_X".$orkey."00001";
    else{
		// 새로운 주문번호 생성
		$ordersno = $ordernocnt + 1;
        $orderno = $orderdate."_X".$orkey.str_pad($ordersno, 5, "0", STR_PAD_LEFT);
    }

	return $orderno;
}

// PCMS 주문 번호 생성
function get_extorder($orderno)
{
	global $conn_cms;

 	$psql="SELECT * FROM pcmsdb.ordermts_ext WHERE order_num = '$orderno' limit 1";
	$pres = mysql_query($psql, $conn_cms);

	return @mysql_fetch_array($pres);
}

?>
