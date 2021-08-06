<?php

require 'vendor/autoload.php';
require 'functions.php';

$user = 'chiara+lst@merconic.de';
$apiPassword = 'PYMg&beMQi3t';
$apiKey = 'AA48FA77-E143-4562-85A6-20F9B51692EF';
$client = new \BillbeeDe\BillbeeAPI\Client($user, $apiPassword, $apiKey);
$shopID = 20000000000036960;

$all_functions = new Functions();

$auth = "Authorization: Bearer 1a42551cf107fd0d12087e80e7df838b652e1fb526c045b4979e871f04d51cdb";
$wf_orders_url = "https://api.webflow.com/sites/5e675a33a5d142f2cc2ca1ca/orders";
$webflow_response = $all_functions->curl_request($wf_orders_url, $auth, "GET", null)[0];

$all_orders = $client->getOrders();
// print_r($all_orders); exit();

// create the orders from webflow to billbee
foreach ($webflow_response as $ord_key => $ord_value) {
	// if($ord_value['status'] != 'refunded' && $ord_value['comment'] != 'billbee_exported'){
		$mOrder = $all_functions->createOrder($ord_value);
		$res = $client->createOrder($mOrder, $shopID);
		
		if($res->errorCode == 0){
			$update_comment_url = "https://api.webflow.com/sites/5e675a33a5d142f2cc2ca1ca/order/". $ord_value['orderId'];
			$put_array['fields'] = array(
				'comment' => 'billbee_exported',
			);

			$update_response = $all_functions->curl_request($update_comment_url, $auth, "PATCH", $put_array);
			print_r($update_response);
		}

	// }	// end of if condition, order in not refunded
}

// // update the status of orders from billbee to webflow

// $all_orders = $client->getOrders();

// foreach ($all_orders->data as $ord_key => $ord_value) {
// 	if($ord_value->state == 1){
// 		$order_number = substr($ord_value->orderNumber, 0, -3);
// 		$order_index = array_search($order_number, array_column($decode_response, 'orderId'));		

// 		if(!empty($order_index)){
// 			$update_status_url = "https://api.webflow.com/sites/5e675a33a5d142f2cc2ca1ca/order/". $order_number."/refund";
// 			$response = $all_functions->curl_request($update_status_url, $auth, "POST", null);

// 			print_r($response); exit();	
// 		}
		
// 	}
// }





?>