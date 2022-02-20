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

// Проверка входящих данных
if (file_exists("subscription") !== true) {
    mkdir("subscription");
}
if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
}
if ($input["action"] == "getInfo") {
    // Чтение подписки
    if (file_exists("subscription/".$input["userId"].".json") === true) {
        $fileSubscription = json_decode(file_get_contents("subscription/".$input["userId"].".json"), true);
    } else {
        $result["state"] = false;
        $result["message"]["subscription"] = "This user has no subscriptions";
        echo json_encode($result);
        exit;
    }
    if ($input["subscriptionId"] != NULL) {
        if (in_array($input["subscriptionId"], $fileSubscription) !== true) {
            $result["state"] = false;
            $result["message"]["subscriptionId"] = "subscriptionId does not belong to the specified user";
            echo json_encode($result);
            exit;
        } else {
            $bepaid = json_decode(send_auth(json_encode($send_data), "https://api.bepaid.by/subscriptions/".$input["subscriptionId"], $merchant_id, $merchant_key), true);
            echo json_encode($bepaid);
            exit;
        }
    } else {
        foreach ($fileSubscription as $oneSubscription) {
            $result[] = json_decode(send_auth(json_encode($send_data), "https://api.bepaid.by/subscriptions/".$oneSubscription, $merchant_id, $merchant_key), true);
        }
        echo json_encode($result);
        exit;
    }
} else if ($input["action"] == "canceled") {
    // Отмена подписки
    if (file_exists("subscription/".$input["userId"].".json") === true) {
        $fileSubscription = json_decode(file_get_contents("subscription/".$input["userId"].".json"), true);
    } else {
        $result["state"] = false;
        $result["message"]["subscription"] = "This user has no subscriptions";
        echo json_encode($result);
        http_response_code(422);
        exit;
    }
    if ($input["subscriptionId"] == NULL) {
        $result["state"] = false;
        $result["message"]["subscriptionId"] = "subscriptionId is missing";
        echo json_encode($result);
        http_response_code(422);
        exit;
    } else if (in_array($input["subscriptionId"], $fileSubscription) !== true) {
        $result["state"] = false;
        $result["message"]["subscriptionId"] = "subscriptionId does not belong to the specified user";
        echo json_encode($result);
        http_response_code(422);
        exit;
    }
    $send_data["cancel_reason"] = "Customer's request";
    $bepaid = json_decode(send_auth(json_encode($send_data), "https://api.bepaid.by/subscriptions/".$input["subscriptionId"]."/cancel", $merchant_id, $merchant_key), true);
    echo json_encode($bepaid);
    exit;
} else {
    // Создание подписки
    if ($input["amount"] == NULL) {
        $result["state"] = false;
        $result["message"]["amount"] = "amount is missing";
    }
    if ($input["interval"] == NULL) {
        $result["state"] = false;
        $result["message"]["interval"] = "interval is missing";
    }
    if ($input["currency"] == NULL) {
        $result["state"] = false;
        $result["message"]["currency"] = "currency is missing";
    }
    if ($input["title"] == NULL) {
        $result["state"] = false;
        $result["message"]["title"] = "title is missing";
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
    if ($input["test"] != NULL) {
        $send_data["plan"]["test"] = true;
    }
    $send_data["plan"]["title"] = $input["title"];
    $send_data["plan"]["currency"] = $input["currency"];
    $send_data["plan"]["plan"]["amount"] = round(str_replace(array(",", " "), array("."), $input["amount"]) * 100);;
    $send_data["plan"]["plan"]["interval"] = $input["interval"];
    $send_data["plan"]["plan"]["interval_unit"] = "day";
    if ($input["trial_amount"] != NULL && $input["trial_interval"] != NULL) {
        $send_data["plan"]["trial"]["amount"] = round(str_replace(array(",", " "), array("."), $input["trial_amount"]) * 100);
        $send_data["plan"]["trial"]["interval"] = $input["trial_interval"];
        $send_data["plan"]["trial"]["interval_unit"] = "hour";
        $send_data["plan"]["trial"]["as_first_payment"] = true;
    }
    $send_data["tracking_id"] = $input["userId"]."-".mt_rand(100000, 999999);
    $send_data["notification_url"] = $url."/callback_subscription.php?action=".$input["action"];
    
    $bepaid = json_decode(send_auth(json_encode($send_data), "https://api.bepaid.by/subscriptions", $merchant_id, $merchant_key), true);
    echo json_encode($bepaid);
    
    // Сохранение данных
    if (file_exists("subscription/".$input["userId"].".json") === true) {
        $fileSubscription = json_decode(file_get_contents("subscription/".$input["userId"].".json"), true);
    }
    $fileSubscription[] = $bepaid["id"];
    file_put_contents("subscription/".$input["userId"].".json", json_encode($fileSubscription));
}