<?php
/*
 *
 * 다이노스타 키오스크 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 * 
 *
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

Header('Content-type: text/xml');
$mdate = date("Y-m-d");

$v_ch = trim($_GET["ch"]);
$v_val = trim($_GET["pval"]);
$v_mode = trim($_GET["pc"]);
$v_termno = $_GET["fnco"];


if(!$v_ch) $v_ch = 242;

// 가능 판매채널 코드
$charr = array(242,3327,3463,3584,3088,218,3467,3590,211,3605,3610,3615,3619,238,3631,4012,4014,3632,3633,3634,3638,3324,3135,3642);

if(!in_array($v_ch,$charr)){
	exit;
}

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RESULT/>');

switch($v_mode){
    case 'CL':
		$sdate = $mdate." 00:00:00";
		$edate = $mdate." 23:59:59";


	    $orderqry = "SELECT o. * ,AES_DECRYPT(UNHEX(o.hp),'Wow1daY') dhp 
                             FROM spadb.ordermts o 
                             WHERE 1 
                             AND grmt_id = '$v_ch' 
                             AND usegu_at between '$sdate' and '$edate'   
							 AND usegu = '1' 
                             AND state= '예약완료' ";

		if($v_termno == "pos" ) $orderqry = $orderqry." and paygu='pos'";
		if($v_termno == "kiosk" ) $orderqry = $orderqry." and paygu='kiosk'";

	    $ores = $conn_cms3->query($orderqry);

        $track = $xml->addChild('RCODE',"S");
        $track = $xml->addChild('RMSG',"성공");
        $track = $xml->addChild('RCNT',$ores->num_rows);
        $track = $xml->addChild('ORDERS');    


		while($orow = $ores->fetch_object()){

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$orow->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;

				$ord = $track->addChild('ORDER');
                $ord->addChild('MENUCODE',$mcode);
				$ord->addChild('COUPONNO',$orow->barcode_no);
                $ord->addChild('USEDATE',$orow->usegu_at);
				
		}


	break;	
	case 'SS':
        if(is_numeric($v_val) and strlen($v_val) == 4){

			$orderqry2 = "SELECT a.*,b.couponno as coupon, b.state as cstate , AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp 
				FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id where a.grmt_id = '$v_ch' AND a.usedate >= '$mdate' AND a.state= '예약완료' AND a.lasthp = '".$v_val."'";

    
        }else{

			 $orderqry = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";

			$ores = $conn_cms3->query($orderqry)->fetch_object();
			
			   $orderqry2 = "SELECT 
								a.*,
								b.couponno as coupon,
								b.state as cstate ,
								AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp 
							FROM 
								ordermts a 
							LEFT OUTER JOIN 
								ordermts_coupons  b 
							ON a.id = b.order_id 
							WHERE 
								a.grmt_id = '$v_ch' AND 
								a.usedate >= '$mdate' AND 
								a.state= '예약완료' AND 
								a.hp = '".$ores->hp."'";

        }

		$res = $conn_cms3->query($orderqry2);



        if($res->num_rows > 0){
            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');        


            while($row = $res->fetch_object()){
				if(empty($row->coupon)) continue; 

				if($row->cstate == 'Y') $useyn = '1';
					else $useyn = '2';
				
				$n = 1;
				if($row->man1 != '1'){
					// 개별 쿠폰과 수량 쿠폰 구별
					if(count(array_filter(explode(";",$row->barcode_no.";")))== "1"){
						$n = $row->man1;
					}else{
						$n = 1;
					}
				}

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$row->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;

                $ord = $track->addChild('ORDER');
                $ord->addChild('ORDERNO',$row->orderno);
                $ord->addChild('COUPONNO',$row->coupon);
                $ord->addChild('MENUCODE',$mcode);
//                $ord->addChild('MENUNAME',"".$row->itemnm);								
				$ord->addChild('MENUNAME',str_replace("&", ",",$row->itemnm));

                $ord->addChild('QTY',$n);
                $ord->addChild('EXPDATE',str_replace("-", "",$row->usedate));
                $ord->addChild('STATE',$row->state);
                $ord->addChild('USTATE',$useyn);
                $ord->addChild('CUSNM',$row->usernm);
                $ord->addChild('CUSHP',$row->dhp);
                $ord->addChild('CUSOPT'," ");
//                $ord->addChild('CUSOPT',$row->bigo);
            }        
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');        
        
        }



    break;
    case 'US':
		$sql = "SELECT
					* 
				FROM 
					spadb.ordermts 
				WHERE 
					grmt_id = '$v_ch' AND 
					id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val')";

        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id and $row->state == '예약완료'){

            usecouponno($v_val);

            $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' limit 1";
            $conn_cms3->query($ussql);

            $ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where state='N' and couponno = '$v_val'";
            $conn_cms3->query($ussql2);

            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');  
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"사용처리 실패");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');          
        }


    break;
    case 'RC':
		$sql = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id){

            $ussql2 = "update spadb.ordermts_coupons set state='N', dt_use= null where couponno = '$v_val'";
            $conn_cms3->query($ussql2); 

			$ussql3 = "select order_id from spadb.ordermts_coupons where order_id = '$row->id' and state ='Y'";
			$uscnt3 = $conn_cms3->query($ussql3)->num_rows; 
			
			if($uscnt3 == 0){
	          $ussql = "update spadb.ordermts set usegu = 2, usegu_at = null where id = '".$row->id."' limit 1";
		      $conn_cms3->query($ussql);
			}

            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"회수 성공");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');  

        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"회수 실패");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');          
        }
    break;
    default:

            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');   

}
        print($xml->asXML());

function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}
?>