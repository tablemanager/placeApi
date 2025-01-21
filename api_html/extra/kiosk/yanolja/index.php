<?php
/*
 *
 * 야놀자 키오스크 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2019-04-17
 * 
 *
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");
$mdate = date("Y-m-d");

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$para = $_GET['val']; // URI 파라미터 

// 파라미터 
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

// 인터페이스 로그
$tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디

$logsql = "insert cmsdb.extapi_log set  apinm='YANOLJA(kiosk)',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);

// 가능 판매채널 코드
$faclist = array('1111');



switch($itemreq[0]){
	case 'tel':
		$mdate = date("Y-m-d");
		$hp = str_replace(array("-"," "),"",$itemreq[1]);
		$hp1 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$1",$hp));
		$hp2 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$2",$hp));
		$hp3 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$3",$hp));
		

		$_orderqry = "SELECT 
							b.couponno as ticket,
							a.usernm as name,
							AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as tel,
							a.mdate as buyDate,
							a.chnm as channel,
							a.itemnm as option
					  FROM ordermts a 
					  LEFT OUTER JOIN ordermts_coupons b 
					  ON a.id = b.order_id 
					  WHERE a.usedate >= '$mdate' 
					  AND	a.grmt_id = '3601'
					  AND	a.state= '예약완료' 
					  AND	b.state = 'N'  
					  AND	a.id in (select order_id from spadb.ordermts_hp where hp_1='$hp1' and hp_2='$hp2' and hp_3='$hp3') ORDER BY a.id DESC 
					  LIMIT 50";


		$_res= $conn_cms3->query($_orderqry);
		$orders = array();
		while($_row = $_res->fetch_assoc()){
			$orders[] =$_row;
		}
		$ocnt = count($orders);
		if($ocnt > 0){
	            header("HTTP/1.0 200 OK");
				echo json_encode(array("result"=>200,"cnt"=>$ocnt,"orders"=>$orders));
				exit;
		}else{
	            header("HTTP/1.0 200 OK");
				echo json_encode(array("result"=>200,"cnt"=>$ocnt,"orders"=>null));
				exit;
		}
	
	break;
	case 'ticket':
		
		if(strlen($itemreq[1]) >= 5 and strlen($itemreq[1]) <= 16){
			$v_val = $itemreq[1];	
			$_sql = "SELECT * FROM spadb.ordermts WHERE id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val') AND grmt_id = '3601'";
			$_row = $conn_cms3->query($_sql)->fetch_object();

			if($_row->id and $_row->state == '예약완료'){
				// 사용가능 주문 
				usecouponno($v_val);

				$ussql = "update spadb.ordermts set paygu = 'kiosk', usegu = 1, usegu_at = now() where grmt_id = '3601' and id = '".$_row->id."' limit 1";
				$conn_cms3->query($ussql);

				$ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where couponno = '$v_val'";
				$conn_cms3->query($ussql2);

				header("HTTP/1.0 200 OK");
				echo json_encode(array("result"=> 201,"cnt"=>1,"orders"=>array("ticket"=>$v_val)));
				exit;			
			}else{

				// 사용불가 주문 
	            header("HTTP/1.0 200 OK");
				echo json_encode(array("result"=> 403,"cnt"=>0,"orders"=>null));
				exit;						
			}

		}else{
	            header("HTTP/1.0 200 OK");
				echo json_encode(array("result"=> 403,"cnt"=>0,"orders"=>null));
				exit;			
		}

	break;
	default:

}


/*
	case 'SS':
        if(is_numeric($v_val) and strlen($v_val) == 4){

			$orderqry2 = "SELECT a.*,b.couponno as coupon, b.state as cstate , AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp 
				FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id where a.grmt_id = '$v_ch' AND a.usedate >= '$mdate' AND a.state= '예약완료' AND a.lasthp = '".$v_val."'";

    
        }else{

			$orderqry = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";

			$ores = $conn_cms3->query($orderqry)->fetch_object();
			
			$orderqry2 = "SELECT a.*,b.couponno as coupon, b.state as cstate , AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp 
				FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id where a.grmt_id = '$v_ch' AND a.usedate >= '$mdate' AND a.state= '예약완료' AND a.hp = '".$ores->hp."'";

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

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$row->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;

                $ord = $track->addChild('ORDER');
                $ord->addChild('ORDERNO',$row->orderno);
                $ord->addChild('COUPONNO',$row->coupon);
                $ord->addChild('MENUCODE',$mcode);
                $ord->addChild('MENUNAME',$row->itemnm);
                $ord->addChild('QTY',1);
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
		$sql = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val')";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id and $row->state == '예약완료'){

            usecouponno($v_val);

            $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' limit 1";
            $conn_cms3->query($ussql);

            $ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where couponno = '$v_val'";
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

        if($row->id and $row->state == '예약완료' and $row->usegu == '1'){

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
			*/


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

function str2hash($no){

	return strtoupper(hash("sha256", $no));

}

// 클라이언트 아아피
function get_ip(){
    
	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";		
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];	
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}


function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
?>