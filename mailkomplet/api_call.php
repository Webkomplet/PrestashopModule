<?php
/**
 * Calls a Mailkomplet API method
 *
 * @param string $apiKey - API key, generated in Mailkomplet admin
 * @param string $baseCrypt - Base crypt - from Mailkomplet admin
 * @param string $method - HTTP method name
 * @param string $url - API function name
 * @param array $data - API call params
 * @return mixed - result of curl_exec with given params
 */
function mailkompletApiCall($apiKey, $baseCrypt, $method, $url, $data = null)
{
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
    }
    curl_setopt($curl, CURLOPT_URL, 'https://api.mail-komplet.cz/api/' . $baseCrypt . '/' . $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    $header = array(
        "Accept: application/json;charset:utf-8",
        "Content-Type: application/json;charset=UTF-8",
        "X-Requested-With: XMLHttpRequest",
        "Authorization: Basic {$apiKey}"
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}
