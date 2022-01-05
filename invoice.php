<?php

// v1   19.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');

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
function send_auth($inputJSON, $link, $user, $psw){
    $request = 'POST';	
    $descriptor = curl_init($link);
    curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
    curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($descriptor, CURLOPT_USERPWD, "$user:$psw");
    curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'X-API-Version: 2')); 
    curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
    return $itog;
}
}

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
}
if ($input["email"] == NULL) {
    $result["state"] = false;
    $result["message"]["email"] = "email is missing";
}
if ($input["amount"] == NULL) {
    $result["state"] = false;
    $result["message"]["amount"] = "amount is missing";
}
if ($input["currency"] == NULL) {
    $result["state"] = false;
    $result["message"]["currency"] = "currency is missing";
}
if ($input["description"] == NULL) {
    $result["state"] = false;
    $result["message"]["description"] = "description is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"]["action"] = "action is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Формирование данных
$send_data["checkout"]["test"] = true;      // закоментировать для отключения тестового режима
$send_data["checkout"]["transaction_type"] = "payment";
$send_data["checkout"]["order"]["amount"] = round(str_replace(array(",", " "), array("."), $input["amount"]) * 100);
$send_data["checkout"]["order"]["currency"] = $input["currency"];
$send_data["checkout"]["order"]["description"] = $input["description"];
$send_data["checkout"]["order"]["tracking_id"] = $input["userId"]."-".mt_rand(100000, 999999);
$send_data["checkout"]["settings"]["notification_url"] = $url."/callback.php?action=".$input["action"];
$send_data["checkout"]["customer"]["email"] = $input["email"];
if ($input["phone"] != NULL) {
    $send_data["checkout"]["customer"]["phone"] = $input["phone"];
}

$bepaid = json_decode(send_auth(json_encode($send_data), "https://checkout.bepaid.by/ctp/api/checkouts", $merchant_id, $merchant_key), true);
echo json_encode($bepaid);










