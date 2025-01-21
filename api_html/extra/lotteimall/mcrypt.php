<?php

function evencrypt($str)
{
    $curl = curl_init();
    //$url = "http://115.68.42.2:3030/encrypt/" . urlencode($str);
    $url = "http://localhost:3030/encrypt/" . urlencode($str);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info['http_code'] == "200") {
        return $data;
    } else {
        return false;
    }
}

function evdecrypt($str)
{
    $curl = curl_init();
    //$url = "http://115.68.42.2:3030/decrypt/" . urlencode($str);
    $url = "http://localhost:3030/decrypt/" . urlencode($str);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info['http_code'] == "200") {
        return $data;
    } else {
        return false;
    }
}
?>

