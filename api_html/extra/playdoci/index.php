<?php
exit;
/*
 *
 * 웅진 플레이도시 주문 XML 생성 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2018-04-16
 * 
 * https://gateway.sparo.cc/extra/playdoci/
*  http://www.playdoci.com/reseller/alliance/reseller_order.aspx?authkey=Cp10zsapCdg%3D&url=https%3A%2F%2Fgateway.sparo.cc%2Fextra%2Fplaydoci%2Findex.php
 */
error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

Header('Content-type:application/xml');
$sdate = date("Ymd");
$edate = date("Ymd");
$fortime = date("YmdHis");
$auth = urlencode("nDOF6SM68/k=​");

//echo urlencode("https://gateway.sparo.cc/extra/playdoci/index.php");
$userid = "004657";
$flist = array();
$fqry = "SELECT * FROM pcmsdb.item_fac WHERE faccode = 'WPC' and chncode = 'P'";
$fres = $conn_cms->query($fqry);
while($frow = $fres->fetch_object()){
    $flist[]=$frow->pcms_id;
}


$pcms_iid = implode ($flist,',');

$ordqry="select *,AES_DECRYPT(UNHEX(hp),'Wow1daY') dhp from spadb.ordermts
where grmt_id = '75' 
and state='예약완료' 
and ch_id not in ('150','154','142')
and LENGTH(barcode_no) > 2  
and itemmt_id in ($pcms_iid) 
and sync_fac != '1'  
and usegu= '2' 
AND usedate > '".date('Y-m-d', strtotime('-1 day'))."' order by rand() limit 200";

/*
$ordqry="select *,AES_DECRYPT(UNHEX(hp),'Wow1daY') dhp from spadb.ordermts
where grmt_id = '75' 
and state='예약완료' 
and LENGTH(barcode_no) > 2  
and itemmt_id = '21346'
and ch_id != '3112' 
AND created > '2019-02-11 16:00:00";

$ordqry="select *,AES_DECRYPT(UNHEX(hp),'Wow1daY') dhp from spadb.ordermts
where grmt_id = '75' 
and state='예약완료' 
and LENGTH(barcode_no) > 2  
AND created > '2019-02-11 16:00:00'";

created > '2019-02-11 16:00:00'
*/


$res = $conn_cms3->query($ordqry);

if($res->num_rows > 0){};
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><reseller/>');
$info = $xml->addChild('reqInfo',null);
$items = $xml->addChild('resItems',null);


while($row = $res->fetch_object()){

    if($row->sync_fac == 'Y') continue;

	$expdate=str_replace("-","",$row->usedate);
    
    // 웅진 연동설정
    $cfgqry = "";

	$cfgqry = "SELECT * FROM pcmsdb.item_fac WHERE state = 'Y' and faccode = 'WPC' and chncode = 'P' and pcms_id = '$row->itemmt_id' order by id desc limit 1";

    $cfg = $conn_cms->query($cfgqry)->fetch_object();

    // 권종 셋팅이 없으면 무조건 종일권으로 
    if(!$cfg->goodskind) $cfg->goodskind = 10;
    
    // 네이버 주문의 경우 바코드를 그룹 주문번호로 
    if($row->ch_id == "2984"){
        $couponno = $row->barcode_no;        
    }else{
        $couponno = $row->id;    
    }

    $couponno = trim($row->barcode_no);    

    $resdata = $items->addChild('resData',null);
        $resdata->addChild('GrpOID',$couponno);
        $resdata->addChild('SellDate',$sdate);
        $resdata->addChild('SellerOID',$row->orderno);
        $resdata->addChild('GoodsCD',$cfg->itemcode);
        $resdata->addChild('GoodsName',urlencode($row->itemnm));
        $resdata->addChild('GoodsAmt',$row->amt);
        $resdata->addChild('RecomDate',null);
        $resdata->addChild('BuyerName',urlencode($row->usernm));
        $resdata->addChild('CompCD',$userid);
        $resdata->addChild('StoreCD',$cfg->gdcode);
        $resdata->addChild('UserCnt',$row->man1);
        $resdata->addChild('BuyerHP',"010-0000-".substr($row->dhp,-4));
        $resdata->addChild('BuyerHP2',substr($row->dhp,-4));
        $resdata->addChild('VatyDate',$expdate);
        $resdata->addChild('BgnVatyDate',$cfg->sdate);
        $resdata->addChild('GoodsKind',$cfg->goodskind);

}


$info->addChild('UserID',$userid);
$info->addChild('ReqSdate',$sdate);
$info->addChild('ReqEdate',$edate);
$info->addChild('ResCount',$res->num_rows);

print($xml->asXML());
?>