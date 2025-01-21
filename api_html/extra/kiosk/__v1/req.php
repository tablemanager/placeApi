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
$charr = array(242,3327,3463,3584,3088,218,3467,3590,211);

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
            $orderqry2 = "SELECT o. * ,AES_DECRYPT(UNHEX(o.hp),'Wow1daY') dhp 
                             FROM spadb.ordermts o 
                             WHERE 1 
                             AND grmt_id = '$v_ch' 
                             AND usedate >= '$mdate' 
                             AND state= '예약완료' 
                             AND AES_DECRYPT(UNHEX(o.hp),'Wow1daY') like '%$v_val'";
    
        }else{

         $orderqry = "SELECT * 
                         FROM spadb.ordermts  
                         WHERE 1 
                         AND grmt_id = '$v_ch' 
                         AND barcode_no like '%$v_val%' limit 1";
         echo $orderqry = "SELECT * 
                         FROM spadb.ordermts  
                         WHERE 1 
                         AND grmt_id = '$v_ch' 
                         AND barcode_no ='$v_val' limit 1";
        $ores = $conn_cms3->query($orderqry)->fetch_object();
        
        $orderqry2 = "SELECT o. * ,AES_DECRYPT(UNHEX(o.hp),'Wow1daY') dhp 
                         FROM spadb.ordermts o 
                         WHERE 1 
                         AND grmt_id = '$v_ch' 
                         AND usedate >= '$mdate' 
                         AND state= '예약완료' 
                         AND hp = '".$ores->hp."'";

                         
//                         AND usedate >= '$mdate' 
        }
        $res = $conn_cms3->query($orderqry2);



        if($res->num_rows > 0){
            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');        

         
            




            while($row = $res->fetch_object()){
                    $mcode="";
                    switch($row->itemmt_id){
                        case '17028':
                        case '17037':
                        case '16561':
                        case '16559':
                        case '16593':
                        case '16591':
                        case '16551':
                        case '17413':
                        case '17268': // 성인
                        case '17636':
                        case '17657':
                        case '17659':
                        case '18017':

                        case '18023':
                        case '18021':

                        case '18443':
                        case '18441':
                        case '18438':
                        case '18437':
                        case '18397':

                        case '18852':
                        case '18851':
                        case '18886':
                        case '18884':

                            $mcode="41010055";
                        break;

                        case '17038':
                        case '16562':
                        case '16560':
                        case '16594':
                        case '16592':
                        case '17414':
                        case '17029':
                        case '16552':
                        case '17637':
                        case '17269': // 소인
                        case '17658':
                        case '17660':
                        case '18018':

                        case '18024':
                        case '18022':

                        case '18444':
                        case '18442':
                        case '18440':
                        case '18439':
                        case '18398':
                        case '18854':
                        case '18853':
                        case '18887':
                        case '18885':
                            $mcode="41010056";
                        break;
                    }

                if(!$mcode){
                  $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$row->itemmt_id."' limit 1";
                  $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                  $mcode = $mcinfo->item_cd;
                }

                $ord = $track->addChild('ORDER');
                $ord->addChild('ORDERNO',$row->orderno);
                $ord->addChild('COUPONNO',$row->barcode_no);
                $ord->addChild('MENUCODE',$mcode);
//                $ord->addChild('MENUNAME',base64_encode($row->itemnm));
                $ord->addChild('MENUNAME',$row->itemnm);
                $ord->addChild('QTY',$row->man1);
                $ord->addChild('EXPDATE',str_replace("-", "",$row->usedate));
                $ord->addChild('STATE',$row->state);
                $ord->addChild('USTATE',$row->usegu);
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
         $sql = "select * from spadb.ordermts where barcode_no = '$v_val' AND grmt_id = '$v_ch' limit 1";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id and $row->state == '예약완료' and $row->usegu == '2'){

            usecouponno($v_val);

            $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' limit 1";
            $conn_cms3->query($ussql);

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
        $sql = "select * from spadb.ordermts where barcode_no = '$v_val' AND grmt_id = '$v_ch' limit 1";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id and $row->state == '예약완료' and $row->usegu == '1'){

            $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 2, usegu_at = null where id = '".$row->id."' limit 1";
            $conn_cms3->query($ussql);

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