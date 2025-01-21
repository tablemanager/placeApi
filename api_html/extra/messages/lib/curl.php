<?php

function send_url($url, $method, $data, &$http_status, &$header = null) {

    //Log::debug("Curl $url JsonData=" . $post_data);
    $ch=curl_init();

    //curl_setopt($ch, CURLOPT_HEADER, true);				// 헤더 출력 옵션..
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // 메세지의 따른..
    switch(strtoupper($method))
    {
        case 'GET':
            curl_setopt($ch, CURLOPT_URL, $url);
            break;

        case 'POST':
            $info = parse_url($url);

            $url = $info['scheme'] . '://' . $info['host'] . $info['path'];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            $str = "";
            if (is_array($data)) {
                $req = array();
                foreach ($data as $k => $v) {
                    $req[] = $k . '=' . urlencode($v);
                }

                $str = @implode($req);
            }else {
                $str = $data;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            break;
 
        default:
            return false;
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);						// TimeOut 값 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				// 결과를 받을것인가.. ( False로 하면 자동출력댐.. ㅠㅠ )
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);

	$response = curl_exec($ch);
    $body = null;

	// error
    if (!$response) {
        $body = curl_error($ch);
        // HostNotFound, No route to Host, etc  Network related error
        $http_status = -1;
    } else {
       //parsing http status code
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = $response;
		/*
        if (!is_null($header)) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        } else {
            $body = $response;
        }
		*/
    }

    curl_close($ch);

    return $body;
}

?>