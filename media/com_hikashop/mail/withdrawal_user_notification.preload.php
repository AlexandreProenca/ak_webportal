<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><?php

$app = JFactory::getApplication();
$config = hikashop_config();
$orderClass = hikashop_get('class.order');
$imageHelper = hikashop_get('helper.image');
$productClass = hikashop_get('class.product');
$fieldsClass = hikashop_get('class.field');
$currencyHelper = hikashop_get('class.currency');

if(!is_object($data))
	$data = new stdClass();
if(!isset($data->order))
	$data->order = new stdClass();
if(!isset($data->order->order_id))
	$data->order->order_id = 0;
if(!isset($data->withdrawal))
	$data->withdrawal = new stdClass();
if(!isset($data->withdrawal->withdrawal_products))
	$data->withdrawal->withdrawal_products = array();
if(!isset($data->withdrawal->withdrawal_reason))
	$data->withdrawal->withdrawal_reason = '';

$data->cart = $orderClass->loadFullOrder($data->order->order_id, true, false);

if(!is_object($data->cart))
	$data->cart = new stdClass();
if(!isset($data->cart->products))
	$data->cart->products = array();

$withdrawal_products_map = array();
if(!empty($data->withdrawal->withdrawal_products)) {
    $w_products = is_string($data->withdrawal->withdrawal_products) ? json_decode($data->withdrawal->withdrawal_products) : $data->withdrawal->withdrawal_products;
    foreach($w_products as $wp) {
        $withdrawal_products_map[$wp->order_product_id] = $wp->quantity;
    }
}

$filtered_products = array();
$subtotal = 0;
foreach($data->cart->products as $key => $product) {
    if(isset($withdrawal_products_map[$product->order_product_id])) {
        $qty = $withdrawal_products_map[$product->order_product_id];
        $p = clone $product;
        $p->order_product_quantity = $qty;
        $p->order_product_total_price = $p->order_product_price * $qty; // Simplified
        $p->order_product_total_price_no_vat = $p->order_product_price * $qty;
        if($config->get('price_with_tax')) {
             $p->order_product_total_price = ($p->order_product_price + $p->order_product_tax) * $qty;
        }
        $filtered_products[] = $p;
        $subtotal += $p->order_product_total_price;
    }
}
$data->cart->products = $filtered_products;

$price = new stdClass();
$price->price_value = $subtotal;
$price->price_value_with_tax = $subtotal; // Simplify
$data->cart->full_total = new stdClass;
$data->cart->full_total->prices = array($price);

if(hikashop_isClient('administrator')) {
	$view = 'order';
} else {
	$view = 'address';
}

$customer_name = @$data->user->name;
if(empty($customer_name))
	$customer_name = @$data->cart->billing_address->address_firstname.' '.@$data->cart->billing_address->address_lastname;


$vars = array(
	'LIVE_SITE' => HIKASHOP_LIVE,
	'ORDER_PRODUCT_CODE' => (bool)$config->get('show_code', false),
    'WITHDRAWAL_REASON' => nl2br($data->withdrawal->withdrawal_reason),
	'order' => $data->cart,
	'billing_address' => @$data->cart->billing_address,
	'shipping_address' => @$data->cart->shipping_address,
	'WITHDRAWAL_USER_CHECK' => !empty($data->withdrawal->withdrawal_user_check),
);

$texts = array(
	'BILLING_ADDRESS' => JText::_('HIKASHOP_BILLING_ADDRESS'),
	'SHIPPING_ADDRESS' => JText::_('HIKASHOP_SHIPPING_ADDRESS'),
	'SUMMARY_OF_WITHDRAWAL' => JText::_('HIKA_WITHDRAWAL_PRODUCTS'),
	'PRODUCT_NAME' => JText::_('CART_PRODUCT_NAME'),
	'PRODUCT_CODE' => JText::_('CART_PRODUCT_CODE'),
	'PRODUCT_PRICE' => JText::_('CART_PRODUCT_UNIT_PRICE'),
	'PRODUCT_QUANTITY' => JText::_('CART_PRODUCT_QUANTITY'),
	'PRODUCT_TOTAL' => JText::_('HIKASHOP_TOTAL'),

	'WITHDRAWAL_TITLE' => JText::_('HIKA_WITHDRAWAL_REQUEST_CREATED'),
	'HI_CUSTOMER' => JText::sprintf('HIKA_HELLO_ADMIN_NAME', $customer_name),
	'WITHDRAWAL_INTRO' => JText::sprintf('HIKA_WITHDRAWAL_REQUEST_USER_NOTIFICATION', $data->cart->order_number),
    'WITHDRAWAL_REASON_TITLE' => JText::_('HIKA_WITHDRAWAL_REASON'),
);
$templates = array();

$products_ids = array();
foreach($data->cart->products as $item) { $products_ids[] = $item->product_id; }
$productClass->getProducts($products_ids);

$cartProducts = array();

if(!empty($data->cart->products)){
	$null = null;
	$fields = null;
	$texts['CUSTOMFIELD_NAME'] = '';
	$texts['FOOTER_COLSPAN'] = 3;
	if(hikashop_level(1)){
		$fields = $fieldsClass->getFields('display:mail_order_notif=1',$null,'product');
		if(!empty($fields)){
			$product_customfields = array();
			$usefulFields = array();
			foreach($fields as $field){
				$namekey = $field->field_namekey;
				foreach($productClass->all_products as $product){
					if(!empty($product->$namekey)){
						$usefulFields[] = $field;
						break;
					}
				}
			}
			$fields = $usefulFields;
		}
		if(!empty($fields)){
			foreach($fields as $field){
				$texts['FOOTER_COLSPAN']++;
				$texts['CUSTOMFIELD_NAME'].='<td class="hika_template_color" style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:left;font-size:12px;font-weight:bold;">'.$fieldsClass->getFieldName($field).'</td>';
			}
		}
	}

	foreach($data->cart->products as $item) {
		$product = @$productClass->all_products[$item->product_id];

		$cartProduct = array(
			'PRODUCT_CODE' => $item->order_product_code,
			'PRODUCT_QUANTITY' => $item->order_product_quantity,
			'PRODUCT_IMG' => '',
			'item' => $item,
			'product' => $product,
		);

		if(!empty($item->images[0]->file_path) && $config->get('thumbnail', 1) != 0) {
			$img = $imageHelper->getThumbnail($item->images[0]->file_path, array(50, 50), array('forcesize' => true, 'scale' => 'outside'));
			if($img->success) {
                $image = $img->url;
                if(substr($img->url, 0, 3) == '../') $image = str_replace('../', HIKASHOP_LIVE, $img->url);
                elseif(!$img->external) $image = substr(HIKASHOP_LIVE, 0, strpos(HIKASHOP_LIVE, '/', 9)) . $img->url;
				$cartProduct['PRODUCT_IMG'] = '<img src="'.$image.'" alt="" style="float:left;margin-top:3px;margin-bottom:3px;margin-right:6px;"/>';
			}
		}

		$t = '<p>' . $item->order_product_name . '</p>';
        $cartProduct['PRODUCT_NAME'] = $t;
        $cartProduct['PRODUCT_DOWNLOAD'] = '';
        $cartProduct['PRODUCT_DETAILS'] = ''; 

		$cartProduct['CUSTOMFIELD_VALUE'] = '';
		if(!empty($fields) && hikashop_level(1)){
			foreach($fields as $field){
				$namekey = $field->field_namekey;
				$productData = @$productClass->all_products[$item->product_id];
				$field->currentElement = $productData;
				$cartProduct['CUSTOMFIELD_VALUE'] .= '<td style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right">'.(empty($productData->$namekey)?'':$fieldsClass->show($field,$productData->$namekey)).'</td>';
			}
		}

        $unit_price = $currencyHelper->format($item->order_product_price, $data->cart->order_currency_id);
        $total_price = $currencyHelper->format($item->order_product_total_price, $data->cart->order_currency_id);

		$cartProduct['PRODUCT_PRICE'] = $unit_price;
		$cartProduct['PRODUCT_TOTAL'] = $total_price;

		$cartProducts[] = $cartProduct;
	}
	$templates['PRODUCT_LINE'] = $cartProducts;
}

$vars['BILLING_ADDRESS'] = '';
$vars['SHIPPING_ADDRESS'] = '';
$addressClass = hikashop_get('class.address');
if(!empty($data->cart->billing_address) && !empty($data->cart->fields)){
	$vars['BILLING_ADDRESS'] = $addressClass->displayAddress($data->cart->fields,$data->cart->billing_address,$view);
}
if(!empty($data->cart->order_shipping_id) && !empty($data->cart->shipping_address) && !empty($data->cart->fields)) {
	$vars['SHIPPING_ADDRESS'] = $addressClass->displayAddress($data->cart->fields,$data->cart->shipping_address,$view);
} else {
	$vars['SHIPPING_ADDRESS'] = $vars['BILLING_ADDRESS'];
}
?>
