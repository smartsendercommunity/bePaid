<?php

// v1   13.12.2021
// Powered by M-Soft
// https://t.me/mufik

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
include ('config.php');
$headers = getallheaders();

// Functions
{
function send_forward($inputJSON, $link){
    $request = 'POST';	
    $descriptor = curl_init($link);
     curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
     curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
     curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
    return $itog;
}
function send_bearer($url, $token, $type = "GET", $param = []){
    $descriptor = curl_init($url);
     curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
     curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('User-Agent: M-Soft Integration', 'Content-Type: application/json', 'Authorization: Bearer '.$token)); 
     curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
	return $itog;
}
}




// Верификация данных
$public_key = str_replace(array("\r\n", "\n"), '', $merchant_rsa);
$public_key = chunk_split($public_key, 64);
$public_key = "-----BEGIN PUBLIC KEY-----\n$public_key-----END PUBLIC KEY-----";
$signature = base64_decode($headers["Content-Signature"]);
$key = openssl_pkey_get_public($public_key);
$a = openssl_verify($inputJSON, $signature, $key, OPENSSL_ALGO_SHA256);
if ($a != 1) {
    $result["state"] = false;
    $result["error"]["message"] = "signature if failed";
    echo json_encode($result);
    exit;
}


// Запуск триггера в Smart Sender
$userId = (explode("-", $input["transaction"]["tracking_id"]))[0];
$trigger["name"] = $_GET["action"];
$result["SmartSender"] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/fire", $ss_token, "POST", $trigger), true);

$result["state"] = true;
echo json_encode($result);











