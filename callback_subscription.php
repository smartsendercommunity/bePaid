<?php

// v1   20.02.2022
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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

// Проверка авторизации
if (stripos($headers["Authorization"], "basic") !== false) {
    $auth = explode(" ", $headers["Authorization"])[1];
    $result["auth"] = $auth;
    $login = base64_decode($auth);
    if ($login != $merchant_id.":".$merchant_key) {
        $result["state"] = false;
        $result["error"]["authorization"] = "Authorization is failed";
        echo json_encode($result);
        send_forward(json_encode($result), $logUrl);
        exit;
    }
}

// Поиск подписки и проверка данных
$userData = explode("-", $input["tracking_id"]);
$userId = $userData[0];
if (file_exists("subscription/".$userId.".json") === true) {
    $fileSubscription = json_decode(file_get_contents("subscription/".$userId.".json"), true);
} else {
    $result["state"] = false;
    $result["error"]["subscription"] = "Subscription not found";
    echo json_encode($result);
    exit;
}
if (in_array($input["id"], $fileSubscription) !== true) {
    $result["state"] = false;
    $result["error"]["subscriptionId"] = "This subscriptionId does not belong to the specified user";
    echo json_encode($result);
    exit;
}

// Запуск триггера в Smart Sender
$trigger["name"] = $_GET["action"]."-".$input["state"];
$result["SmartSender"] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/fire", $ss_token, "POST", $trigger), true);
$result["state"] = true;
echo json_encode($result);