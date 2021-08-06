<?php

require 'vendor/autoload.php';
/**
 * 
 */
class Functions
{
	
	function orderState($status){
		if($status == 'refunded')
			return 1;
		elseif ($status == 'dispute-lost' || $status == 'disputed')
			return 5;
		elseif ($status == 'fulfilled')
			return 4;
		elseif ($status == 'pending')
			return 1;
		elseif ($status == 'unfulfilled')
			return 2;
		else
			return 1;
	}

	function paymentMethod($payment){
		if($payment == 'paypal')
			return 3;
		else
			return 31;

	}

	function curl_request($url, $auth, $method, $post_array){
		$curl_init = curl_init($url);
		curl_setopt($curl_init, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_init, CURLOPT_HTTPHEADER, array($auth, 'Content-Type: application/json' , 'Accept: application/json', 'accept-version: 1.0.0'));
		curl_setopt($curl_init, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_init, CURLOPT_VERBOSE, 0);
		curl_setopt($curl_init, CURLOPT_HEADER, false);
		curl_setopt($curl_init, CURLOPT_CUSTOMREQUEST, $method);

		if(($method == "POST" || $method == "PATCH") && !empty($post_array)){
			curl_setopt($curl_init, CURLOPT_POSTFIELDS, json_encode($post_array));
		}

		$response = curl_exec ($curl_init);
		$decode_response = json_decode($response, true);
		$httpcode = curl_getinfo($curl_init, CURLINFO_HTTP_CODE);
		curl_close ($curl_init);
		return array($decode_response, $httpcode);
	}

	function createAddress($mOrder, $ord_value){
		$mOrder->invoiceAddress = new \BillbeeDe\BillbeeAPI\Model\Address;
		$mOrder->invoiceAddress->city = $ord_value['allAddresses'][1]['city'];
		$mOrder->invoiceAddress->street = $ord_value['allAddresses'][1]['line1'];
		$mOrder->invoiceAddress->zip = $ord_value['allAddresses'][1]['postalCode'];
		$mOrder->invoiceAddress->state = $ord_value['allAddresses'][1]['state'];
		$mOrder->invoiceAddress->country = $ord_value['allAddresses'][1]['country'];
		$mOrder->invoiceAddress->firstName = $ord_value['allAddresses'][1]['addressee'];
		$mOrder->invoiceAddress->email = $ord_value['customerInfo']['email'];

		$mOrder->shippingAddress = new \BillbeeDe\BillbeeAPI\Model\Address;
		$mOrder->shippingAddress->city = $ord_value['allAddresses'][0]['city'];
		$mOrder->shippingAddress->street = $ord_value['allAddresses'][0]['line1'];
		$mOrder->shippingAddress->zip = $ord_value['allAddresses'][0]['postalCode'];
		$mOrder->shippingAddress->state = $ord_value['allAddresses'][0]['state'];
		$mOrder->shippingAddress->country = $ord_value['allAddresses'][0]['country'];
		$mOrder->shippingAddress->firstName = $ord_value['allAddresses'][0]['addressee'];
		$mOrder->shippingAddress->email = $ord_value['customerInfo']['email'];

		$mOrder->customer = new \BillbeeDe\BillbeeAPI\Model\Customer;
		$mOrder->customer->name = $ord_value['customerInfo']['fullName'];
		$mOrder->customer->email = $ord_value['customerInfo']['email'];

		return $mOrder;
	}

	function createOrder($ord_value){
		$mOrder = new \BillbeeDe\BillbeeAPI\Model\Order;
		$mOrder->acceptLossOfReturnRight = false;
		
		$mOrder->orderNumber = $ord_value['orderId'];

		$mOrder->createdAt = new DateTime();
		$mOrder->createdAt->format('Y-m-d H:i:s');

		$mOrder->payedAt = new DateTime();
		$mOrder->payedAt->format('Y-m-d H:i:s');
		$mOrder->tags = ['webflow_created'];

		$this->createAddress($mOrder, $ord_value);

		$mOrder->customer = new \BillbeeDe\BillbeeAPI\Model\Customer;
		$mOrder->customer->name = $ord_value['customerInfo']['fullName'];
		$mOrder->customer->email = $ord_value['customerInfo']['email'];

		$mOrder->state = $this->orderState($ord_value['status']);
		$mOrder->paymentMethod = $this->paymentMethod($ord_value['paymentProcessor']);
		$mOrder->paidAmount = (float)$ord_value['customerPaid']['value']/100;

		$mOrder->orderItems[] = new \BillbeeDe\BillbeeAPI\Model\OrderItem;

		foreach ($ord_value['purchasedItems'] as $item_key => $item_value) {
			// $item_key += 1;
			$mOrder->orderItems[] = new \BillbeeDe\BillbeeAPI\Model\OrderItem;
			$mOrder->orderItems[$item_key]->product = new \BillbeeDe\BillbeeAPI\Model\SoldProduct;	
			
			$mOrder->orderItems[$item_key]->product->id = $item_value['productId'];
			$mOrder->orderItems[$item_key]->product->weight = $item_value['weight'];
			$mOrder->orderItems[$item_key]->product->sku = $item_value['variantSKU'];

			$mOrder->orderItems[$item_key]->product->title = $item_value['productName'];
			$mOrder->orderItems[$item_key]->quantity = (float)$item_value['count'];
			
			$mOrder->orderItems[$item_key]->unrebatedTotalPrice = 0.0;

			$mOrder->orderItems[$item_key]->taxAmount = 0.0; 
			$mOrder->orderItems[$item_key]->discount = 0.0;
			$mOrder->orderItems[$item_key]->dontAdjustStock = true;
			$mOrder->orderItems[$item_key]->totalPrice = (float)$item_value['rowTotal']['value']/100;	
		}

		$total_pro_items = count($ord_value['purchasedItems']); 
		$mOrder->orderItems[$total_pro_items]->product = new \BillbeeDe\BillbeeAPI\Model\SoldProduct;	
		$mOrder->orderItems[$total_pro_items]->quantity = (float) 1;
		$mOrder->orderItems[$total_pro_items]->product->title = 'Discount';
		$mOrder->orderItems[$total_pro_items]->unrebatedTotalPrice = 0.0;
		$mOrder->orderItems[$total_pro_items]->taxAmount = 0.0;	
		$mOrder->orderItems[$total_pro_items]->discount = 0.0;
		$mOrder->orderItems[$total_pro_items]->dontAdjustStock = true;

		if(!empty($ord_value['totals']['extras'])){
			foreach ($ord_value['totals']['extras'] as $extra_key => $extra_val) {
				if($extra_val['type'] == 'discount'){
					$mOrder->orderItems[$total_pro_items]->totalPrice = (float)($extra_val['price']['value']/100);	
					break;
				}
				else
					$mOrder->orderItems[$total_pro_items]->totalPrice = 0.0;
			}
		}

		if(!empty($ord_value['totals']['extras'])){
			foreach ($ord_value['totals']['extras'] as $extra_key => $extra_val) {
				if ($extra_val['type'] == 'shipping'){
					$mOrder->shippingCost = (float)($extra_val['price']['value']/100);
					$mOrder->shippingProviderName = $extra_val['name'];
					// $mOrder->shipping = [$extra_val['name']];
					break;
				}
				else
					$mOrder->shippingCost = 0.0;
			}
		}

		$mOrder->totalCost = (float) -abs(($ord_value['totals']['total']['value']/100));
		$mOrder->adjustmentCost = 0.0;
		$mOrder->shippingProviderName = $ord_value['totals']['extras'][2]['name'];
		$mOrder->currency = 'EUR';

		return $mOrder;

	}


}
?>