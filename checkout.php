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
if ($input["phone"] == NULL && $input["email"] == NULL) {
    $result["state"] = false;
    $result["contacts"] = "phone or email is missing";
}
if ($input["description"] == NULL) {
    $result["state"] = false;
    $result["message"]["description"] = "description is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"] = "action is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Формирование данных
$send_data["checkout"]["test"] = true;  // Закометировать для отключения тестового режима
$send_data["checkout"]["transaction_type"] = "payment";
$send_data["checkout"]["order"]["tracking_id"] = $input["userId"]."-".mt_rand(100000, 999999);
$send_data["checkout"]["order"]["description"] = $input["description"];
if ($input["pretext"] != NULL) {
    $send_data["checkout"]["order"]["additional_data"]["receipt_text"][] = $input["pretext"];
    $send_data["checkout"]["order"]["additional_data"]["receipt_text"][] = " ";
}
$send_data["checkout"]["settings"]["notification_url"] = $url."/callback.php?action=".$input["action"];
$send_data["checkout"]["customer"]["email"] = $input["email"];
if ($input["phone"] != NULL) {
    $send_data["checkout"]["customer"]["phone"] = $input["phone"];
}

// Получение списка товаров в корзине пользователя
$cursor = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=1&limitation=20", $ss_token), true);
if ($cursor["error"] != NULL && $cursor["error"] != 'undefined') {
    $result["status"] = "error";
    $result["message"][] = "Ошибка получения данных из SmartSender";
    if ($cursor["error"]["code"] == 404 || $cursor["error"]["code"] == 400) {
        $result["message"][] = "Пользователь не найден. Проверте правильность идентификатора пользователя и приналежность токена к текущему проекту.";
    } else if ($cursor["error"]["code"] == 403) {
        $result["message"][] = "Токен проекта SmartSender указан неправильно. Проверте правильность токена.";
    }
    echo json_encode($result);
    exit;
} else if (empty($cursor["collection"])) {
    $result["status"] = "error";
    $result["message"][] = "Корзина пользователя пустая. Для тестирования добавте товар в корзину.";
    echo json_encode($result);
    exit;
}
$pages = $cursor["cursor"]["pages"];
for ($i = 1; $i <= $pages; $i++) {
    $checkout = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=".$i."&limitation=20", $ss_token), true);
	$essences = $checkout["collection"];
	$send_data["checkout"]["order"]["currency"] = $essences[0]["currency"];
	foreach ($essences as $product) {
	    $send_data["checkout"]["order"]["additional_data"]["receipt_text"][] = $product["product"]["name"].': '.$product["name"]." - ".$product["price"].$product["currency"]." x ".$product["pivot"]["quantity"];
		$summ[] = $product["pivot"]["quantity"]*$product["cash"]["amount"];
    }
}
if ($input["posttext"] != NULL) {
    $send_data["checkout"]["order"]["additional_data"]["receipt_text"][] = " ";
    $send_data["checkout"]["order"]["additional_data"]["receipt_text"][] = $input["posttext"];
}
$send_data["checkout"]["order"]["amount"] = round(array_sum($summ) * 100);
$send_data["amount"] = array_sum($summ);

$bepaid = json_decode(send_auth(json_encode($send_data), "https://checkout.bepaid.by/ctp/api/checkouts", $merchant_id, $merchant_key), true);
echo json_encode($bepaid);









