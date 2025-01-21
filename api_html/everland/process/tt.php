<?php

    echo getusesticket("CB5000648408003632");

    function getusesticket($no)
    {
        $curl = curl_init();
        $url = "https://gateway.sparo.cc/everland/sync/".$no;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        
        $data = curl_exec($curl);
        
        $result = json_decode($data);

        //print_r($result);
        switch($result->PIN_STATUS){          
            case 'CR':
            case 'PS':
                $use = 'N';
                break;
            default:
                $use = 'Y';
        }
        return $use;
    }

?>