<?php


function dellog($logpath, $logtp){
    $sdate = date('Y-m-d');
    $ldate  = date('Ymd', strtotime('-1 month', strtotime($sdate)));

    //echo $ldate;
    $dellog = "{$logpath}log_{$ldate}{$logtp}.log";
    //echo "삭제대상 로그 찾기 $dellog ";

    if(file_exists($dellog)){
        unlink($dellog);
        //echo "$dellog log file deleted now.\n\n";
    }else{
        // 굳이 로그남길 필요가 없을듯
        //echo "[상태검사] 삭제할 로그파일이 없는 상태입니다.\n\n";
    }
}

// 비문 결과를 로그 남김(비문/평문)
// fp : file desc
// crypto : 복호화 모듈
// arEncData : 비문 데이터
function logresult($fp, $crypto, $arEncData){
 
    // 일시 기록
    fwrite($fp, date("Y-m-d H:i:s")."\n");

    // 암호화된 데이터 
    fwrite($fp, print_r($arEncData, true));

    $arDecData = array();
    foreach($arEncData as $k => $v){
        //$arDecData[$k] = base64_decode($crypto->decrypt($v));
        $arDecData[$k] = $crypto->decrypt($v);
    }

    fwrite($fp, print_r($arDecData, true));
}

?>
