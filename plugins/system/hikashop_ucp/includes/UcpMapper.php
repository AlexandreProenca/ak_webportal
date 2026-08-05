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
defined('_JEXEC') or die;

class HikashopUcpMapper {

	var $params = null;
	var $ucpVersion = '2026-01-11';

	function __construct(&$params) {
		$this->params = $params;
	}

	function getDomain() {
		$siteUrl = rtrim(HIKASHOP_LIVE, '/');
		$parsed = parse_url($siteUrl);
		return isset($parsed['host']) ? $parsed['host'] : 'localhost';
	}

	function globalId($type, $id) {
		return 'gid://'.$this->getDomain().'/'.$type.'/'.$id;
	}

	function parseGlobalId($gid) {
		if(strpos($gid, 'gid://') === 0) {
			$parts = explode('/', $gid);
			return end($parts);
		}
		return $gid;
	}

	function toCents($amount) {
		return (int)round($amount * 100);
	}

	function fromCents($cents) {
		return round($cents / 100, 5);
	}

	function getUcpToken($cart) {
		$params = !empty($cart->cart_params) ? $cart->cart_params : null;
		if(is_string($params))
			$params = json_decode($params);
		if(is_object($params) && !empty($params->ucp_token))
			return $params->ucp_token;
		if(is_array($params) && !empty($params['ucp_token']))
			return $params['ucp_token'];
		return $cart->cart_id;
	}

	function getCurrencyCode($currencyId = 0) {
		if(empty($currencyId)) {
			$currencyId = hikashop_getCurrency();
		}
		$currencyClass = hikashop_get('class.currency');
		$currency = $currencyClass->get($currencyId);
		return !empty($currency->currency_code) ? $currency->currency_code : 'USD';
	}

	function cartToSession($cart, $status = 'incomplete') {
		$currencyCode = $this->getCurrencyCode(!empty($cart->cart_currency_id) ? $cart->cart_currency_id : 0);

		$session = array(
			'ucp' => array(
				'version' => $this->ucpVersion,
				'capabilities' => array(
					array('name' => 'dev.ucp.shopping.checkout', 'version' => $this->ucpVersion),
					array('name' => 'dev.ucp.shopping.fulfillment', 'version' => $this->ucpVersion)
				)
			),
			'id' => $this->globalId('Checkout', $this->getUcpToken($cart)),
			'status' => $status,
			'currency' => $currencyCode,
			'line_items' => array(),
			'totals' => array(),
			'links' => array()
		);

		if(!empty($cart->products)) {
			$lineIdx = 0;
			foreach($cart->products as $product) {
				$unitPrice = 0;
				if(!empty($product->prices) && is_array($product->prices)) {
					$price = reset($product->prices);
					$unitPrice = !empty($price->price_value) ? $price->price_value : 0;
				} elseif(isset($product->order_product_price)) {
					$unitPrice = (float)$product->order_product_price;
				}

				$qty = 1;
				if(!empty($product->cart_product_quantity))
					$qty = (int)$product->cart_product_quantity;
				elseif(!empty($product->order_product_quantity))
					$qty = (int)$product->order_product_quantity;

				$name = '';
				if(!empty($product->product_name))
					$name = $product->product_name;
				elseif(!empty($product->order_product_name))
					$name = $product->order_product_name;

				$lineItemId = $lineIdx;
				if(!empty($product->cart_product_id))
					$lineItemId = $product->cart_product_id;
				elseif(!empty($product->order_product_id))
					$lineItemId = $product->order_product_id;

				$item = array(
					'id' => $this->globalId('LineItem', $lineItemId),
					'item' => array(
						'id' => (string)$product->product_id,
						'title' => $name,
						'price' => $this->toCents($unitPrice)
					),
					'quantity' => $qty,
					'unit_price' => array(
						'amount' => $this->toCents($unitPrice),
						'currency' => $currencyCode
					),
					'totals' => array(
						array('type' => 'subtotal', 'amount' => $this->toCents($unitPrice * $qty))
					)
				);

				$session['line_items'][] = $item;
				$lineIdx++;
			}
		}

		$subtotal = 0;
		$tax = 0;
		$shipping = 0;
		$discount = 0;

		if(!empty($cart->total) && !empty($cart->total->prices) && !empty($cart->total->prices[0])) {
			$totals = $cart->total->prices[0];
			$subtotal = !empty($totals->price_value) ? $totals->price_value : 0;
			if(!empty($totals->taxes)) {
				foreach($totals->taxes as $t) {
					$tax += !empty($t->tax_amount) ? $t->tax_amount : 0;
				}
			}
		}

		if(!empty($cart->shipping) && is_array($cart->shipping)) {
			foreach($cart->shipping as $s) {
				$shipping += !empty($s->shipping_price_with_tax) ? $s->shipping_price_with_tax : (!empty($s->shipping_price) ? $s->shipping_price : 0);
			}
		}

		if(!empty($cart->coupon) && !empty($cart->coupon->discount_value))
			$discount = $cart->coupon->discount_value;

		$total = $subtotal + $tax + $shipping - $discount;
		if(!empty($cart->full_total) && !empty($cart->full_total->prices) && !empty($cart->full_total->prices[0])) {
			$total = !empty($cart->full_total->prices[0]->price_value_with_tax) ? $cart->full_total->prices[0]->price_value_with_tax : $total;
		}

		$session['totals'] = array(
			array('type' => 'subtotal', 'amount' => $this->toCents($subtotal)),
			array('type' => 'tax', 'amount' => $this->toCents($tax)),
			array('type' => 'fulfillment', 'amount' => $this->toCents($shipping)),
			array('type' => 'discount', 'amount' => $this->toCents($discount)),
			array('type' => 'total', 'amount' => $this->toCents($total))
		);

		if(!empty($cart->usable_methods) && !empty($cart->usable_methods->shipping)) {
			$session['fulfillment'] = $this->_mapFulfillment($cart, $currencyCode);
		}

		$buyerAddress = !empty($cart->billing_address) ? $cart->billing_address : (!empty($cart->shipping_address) ? $cart->shipping_address : null);
		if(!empty($buyerAddress) || !empty($cart->user_id)) {
			$buyer = $this->_mapBuyer($buyerAddress, !empty($cart->user_id) ? (int)$cart->user_id : 0);
			if(!empty($buyer))
				$session['buyer'] = $buyer;
		}

		$session['payment'] = array('handlers' => array());
		$gpayHandler = $this->_getGooglePayHandlerForSession();
		if(!empty($gpayHandler))
			$session['payment']['handlers'][] = $gpayHandler;

		$privacyUrl = $this->params->get('privacy_policy_url', '');
		if(!empty($privacyUrl))
			$session['links'][] = array('type' => 'privacy_policy', 'url' => $privacyUrl);

		$termsUrl = $this->params->get('terms_url', '');
		if(!empty($termsUrl))
			$session['links'][] = array('type' => 'terms_of_service', 'url' => $termsUrl);

		return $session;
	}

	function orderToCompletedSession($cart, $order, $paymentInstrument = null) {
		if(!empty($order->shipping_address))
			$cart->shipping_address = $order->shipping_address;
		if(!empty($order->billing_address))
			$cart->billing_address = $order->billing_address;

		$session = $this->cartToSession($cart, 'completed');
		$session['order'] = array(
			'id' => $this->globalId('Order', $order->order_id),
			'permalink_url' => hikashop_contentLink('order&task=show&cid='.(int)$order->order_id, $order, false, false, false, true)
		);

		if(!empty($paymentInstrument)) {
			$instrumentId = !empty($paymentInstrument['id']) ? $paymentInstrument['id'] : 'pi_'.($order->order_id);

			$instrument = array(
				'id' => $instrumentId
			);
			if(!empty($paymentInstrument['handler_id']))
				$instrument['handler_id'] = $paymentInstrument['handler_id'];
			if(!empty($paymentInstrument['type']))
				$instrument['type'] = $paymentInstrument['type'];
			if(!empty($paymentInstrument['brand']))
				$instrument['brand'] = $paymentInstrument['brand'];
			if(!empty($paymentInstrument['last_digits']))
				$instrument['last_digits'] = $paymentInstrument['last_digits'];
			if(!empty($paymentInstrument['rich_text_description']))
				$instrument['rich_text_description'] = $paymentInstrument['rich_text_description'];

			if(!isset($session['payment']))
				$session['payment'] = array();
			$session['payment']['selected_instrument_id'] = $instrumentId;
			$session['payment']['instruments'] = array($instrument);
		}

		return $session;
	}

	private function _mapFulfillment($cart, $currencyCode) {
		$fulfillment = array(
			'methods' => array(
				array(
					'type' => 'shipping',
					'options' => array()
				)
			)
		);

		if(!empty($cart->shipping_address)) {
			$addr = $cart->shipping_address;

			$getStr = function($field) use ($addr) {
				if(empty($addr->$field))
					return '';
				$v = $addr->$field;
				if(is_array($v))
					$v = reset($v);
				if(is_object($v))
					return !empty($v->zone_code_2) ? $v->zone_code_2 : (!empty($v->zone_name_english) ? $v->zone_name_english : '');
				return (string)$v;
			};

			$countryCode = $getStr('address_country_code_2');
			if(empty($countryCode)) {
				$raw = $getStr('address_country');
				if(!empty($raw)) {
					$db = JFactory::getDBO();
					$db->setQuery('SELECT zone_code_2 FROM '.hikashop_table('zone').' WHERE zone_namekey = '.$db->Quote($raw).' LIMIT 1');
					$code = $db->loadResult();
					$countryCode = !empty($code) ? $code : $raw;
				}
			}

			$stateName = $getStr('address_state_name_english');
			if(empty($stateName)) {
				$raw = $getStr('address_state');
				if(!empty($raw) && strpos($raw, '_') !== false) {
					$db = JFactory::getDBO();
					$db->setQuery('SELECT zone_name_english FROM '.hikashop_table('zone').' WHERE zone_namekey = '.$db->Quote($raw).' LIMIT 1');
					$name = $db->loadResult();
					$stateName = !empty($name) ? $name : $raw;
				} elseif(!empty($raw)) {
					$stateName = $raw;
				}
			}

			$dest = array(
				'id' => 'dest_1'
			);
			if(!empty($addr->address_post_code))
				$dest['postal_code'] = is_array($addr->address_post_code) ? reset($addr->address_post_code) : $addr->address_post_code;
			if(!empty($countryCode))
				$dest['country'] = $countryCode;
			if(!empty($addr->address_city))
				$dest['address_locality'] = is_array($addr->address_city) ? reset($addr->address_city) : $addr->address_city;
			if(!empty($stateName))
				$dest['address_region'] = $stateName;

			$fulfillment['methods'][0]['destinations'] = array($dest);
			$fulfillment['methods'][0]['selected_destination_id'] = 'dest_1';
		}

		if(!empty($cart->usable_methods) && !empty($cart->usable_methods->shipping)) {
			foreach($cart->usable_methods->shipping as $method) {
				$option = array(
					'id' => (string)$method->shipping_id,
					'name' => !empty($method->shipping_name) ? $method->shipping_name : 'Shipping',
					'amount' => $this->toCents(!empty($method->shipping_price_with_tax) ? $method->shipping_price_with_tax : (!empty($method->shipping_price) ? $method->shipping_price : 0)),
					'currency' => $currencyCode
				);
				$fulfillment['methods'][0]['options'][] = $option;
			}
		}

		if(!empty($cart->cart_shipping_ids)) {
			$ids = is_array($cart->cart_shipping_ids) ? $cart->cart_shipping_ids : explode(';', $cart->cart_shipping_ids);
			if(!empty($ids[0])) {
				$parts = explode('@', $ids[0]);
				$fulfillment['methods'][0]['selected_option_id'] = $parts[0];
			}
		}

		return $fulfillment;
	}

	private function _mapBuyer($address, $userId = 0) {
		$buyer = array();
		if(!empty($address)) {
			if(!empty($address->address_firstname))
				$buyer['first_name'] = $address->address_firstname;
			if(!empty($address->address_lastname))
				$buyer['last_name'] = $address->address_lastname;
		}
		if(!empty($userId)) {
			$userClass = hikashop_get('class.user');
			$user = $userClass->get($userId);
			if(!empty($user->user_email))
				$buyer['email'] = $user->user_email;
		}
		return $buyer;
	}

	function mapIncomingAddress($data) {
		$address = new stdClass();

		if(!empty($data['buyer'])) {
			$buyer = $data['buyer'];
			if(isset($buyer['first_name']))
				$address->address_firstname = $buyer['first_name'];
			if(isset($buyer['last_name']))
				$address->address_lastname = $buyer['last_name'];
		}

		if(!empty($data['fulfillment']['methods'])) {
			foreach($data['fulfillment']['methods'] as $method) {
				if(!empty($method['destinations'])) {
					$dest = null;
					$selectedId = !empty($method['selected_destination_id']) ? $method['selected_destination_id'] : null;
					foreach($method['destinations'] as $d) {
						if($selectedId === null || (isset($d['id']) && $d['id'] === $selectedId)) {
							$dest = $d;
							break;
						}
					}
					if(!$dest)
						$dest = reset($method['destinations']);

					if(isset($dest['postal_code']))
						$address->address_post_code = $dest['postal_code'];
					if(isset($dest['country']))
						$address->address_country = $this->_resolveCountryZone($dest['country']);
					if(isset($dest['address_locality']))
						$address->address_city = $dest['address_locality'];
					if(isset($dest['address_region']))
						$address->address_state = $this->_resolveStateZone($dest['address_region'], $address->address_country);
					if(isset($dest['street_address']))
						$address->address_street = $dest['street_address'];
				}
			}
		}

		return $address;
	}

	private function _resolveCountryZone($code) {
		if(empty($code))
			return '';
		$code = strtoupper(trim($code));
		$db = JFactory::getDBO();
		$db->setQuery('SELECT zone_namekey FROM '.hikashop_table('zone').' WHERE zone_code_2 = '.$db->Quote($code).' AND zone_type = '.$db->Quote('country').' LIMIT 1');
		$namekey = $db->loadResult();
		return !empty($namekey) ? $namekey : $code;
	}

	private function _resolveStateZone($state, $countryNamekey) {
		if(empty($state))
			return '';
		if(is_array($state))
			$state = reset($state);
		$state = trim($state);
		if(empty($countryNamekey))
			return $state;

		$zoneClass = hikashop_get('class.zone');
		$children = $zoneClass->getChildren($countryNamekey);
		if(empty($children))
			return $state;

		$childNamekeys = array();
		foreach($children as $child) {
			if(!empty($child->zone_namekey))
				$childNamekeys[] = $child->zone_namekey;
		}
		if(empty($childNamekeys))
			return $state;

		$db = JFactory::getDBO();
		$stateQ = $db->Quote($state);
		$stateUpper = $db->Quote(strtoupper($state));
		$db->setQuery(
			'SELECT zone_namekey FROM '.hikashop_table('zone').
			' WHERE zone_namekey IN ('.implode(',', array_map(array($db, 'Quote'), $childNamekeys)).')'.
			' AND (zone_code_2 = '.$stateUpper.' OR zone_code_3 = '.$stateUpper.' OR zone_name_english = '.$stateQ.')'.
			' LIMIT 1'
		);
		$namekey = $db->loadResult();
		return !empty($namekey) ? $namekey : $state;
	}

	function productToUcp($product, $currencyCode = null) {
		if(empty($currencyCode))
			$currencyCode = $this->getCurrencyCode();

		$ucpProduct = array(
			'id' => $this->globalId('Product', $product->product_id),
			'name' => !empty($product->product_name) ? strip_tags(hikashop_translate($product->product_name)) : '',
			'sku' => !empty($product->product_code) ? $product->product_code : ''
		);

		if(!empty($product->product_description)) {
			$ucpProduct['description'] = strip_tags($product->product_description);
		}

		$ucpProduct['url'] = hikashop_contentLink('product&task=show&cid='.(int)$product->product_id.'&name='.(!empty($product->alias) ? $product->alias : $product->product_id), $product);

		if(!empty($product->prices) && is_array($product->prices)) {
			$price = reset($product->prices);
			$priceValue = 0;
			if(!empty($price->price_value_with_tax)) {
				$priceValue = $price->price_value_with_tax;
			} elseif(!empty($price->price_value)) {
				$priceValue = $price->price_value;
			}
			$ucpProduct['price'] = array(
				'amount' => $this->toCents($priceValue),
				'currency' => $currencyCode
			);

			if(!empty($price->price_orig_value_with_tax) && $price->price_orig_value_with_tax > $priceValue) {
				$ucpProduct['compare_at_price'] = array(
					'amount' => $this->toCents($price->price_orig_value_with_tax),
					'currency' => $currencyCode
				);
			}
		}

		if(!empty($product->images) && is_array($product->images)) {
			$ucpProduct['images'] = array();
			$imageHelper = hikashop_get('helper.image');
			foreach($product->images as $image) {
				$imgUrl = '';
				if(!empty($image->file_path)) {
					$thumbResult = $imageHelper->getThumbnail($image->file_path, array('width' => 800, 'height' => 800), array(), true);
					$imgUrl = !empty($thumbResult->url) ? $thumbResult->url : '';
					if(empty($imgUrl))
						$imgUrl = HIKASHOP_LIVE.'media/com_hikashop/upload/'.$image->file_path;
				}
				if(!empty($imgUrl) && strpos($imgUrl, 'http') !== 0) {
					$imgUrl = rtrim(HIKASHOP_LIVE, '/').'/'.$imgUrl;
				}
				if(!empty($imgUrl)) {
					$ucpProduct['images'][] = array(
						'url' => $imgUrl,
						'alt' => !empty($image->file_description) ? $image->file_description : $ucpProduct['name']
					);
				}
			}
		}

		$quantity = isset($product->product_quantity) ? (int)$product->product_quantity : -1;
		if($quantity == 0) {
			$ucpProduct['availability'] = 'out_of_stock';
		} elseif(!empty($product->product_sale_start) && $product->product_sale_start > time()) {
			$ucpProduct['availability'] = 'preorder';
		} else {
			$ucpProduct['availability'] = 'in_stock';
		}

		if($quantity > 0) {
			$ucpProduct['inventory_quantity'] = $quantity;
		}

		if(!empty($product->product_type) && $product->product_type == 'variant') {
			$ucpProduct['type'] = 'variant';
		} elseif(!empty($product->has_options)) {
			$ucpProduct['type'] = 'configurable';
		} else {
			$ucpProduct['type'] = 'simple';
		}

		if(!empty($product->product_weight) && $product->product_weight > 0) {
			$weightUnit = !empty($product->product_weight_unit) ? $product->product_weight_unit : 'kg';
			$ucpProduct['weight'] = array(
				'value' => (float)$product->product_weight,
				'unit' => $weightUnit
			);
		}

		if(!empty($product->product_width) || !empty($product->product_height) || !empty($product->product_length)) {
			$dimUnit = !empty($product->product_dimension_unit) ? $product->product_dimension_unit : 'cm';
			$ucpProduct['dimensions'] = array(
				'unit' => $dimUnit
			);
			if(!empty($product->product_width))
				$ucpProduct['dimensions']['width'] = (float)$product->product_width;
			if(!empty($product->product_height))
				$ucpProduct['dimensions']['height'] = (float)$product->product_height;
			if(!empty($product->product_length))
				$ucpProduct['dimensions']['length'] = (float)$product->product_length;
		}

		if(!empty($product->categories) && is_array($product->categories)) {
			$ucpProduct['categories'] = array();
			foreach($product->categories as $cat) {
				$ucpProduct['categories'][] = array(
					'id' => $this->globalId('Category', $cat->category_id),
					'name' => !empty($cat->category_name) ? $cat->category_name : ''
				);
			}
		}

		return $ucpProduct;
	}

	private function _getGooglePayHandlerForSession() {
		if(!$this->params->get('gpay_enabled', 0))
			return null;

		$gatewayConfig = HikashopUcpDiscovery::detectGooglePayGateway();
		if(empty($gatewayConfig))
			return null;

		$gateway = $gatewayConfig['gateway'];
		$gatewayMerchantId = $gatewayConfig['gatewayMerchantId'];

		$cardNetworks = $this->params->get('gpay_card_networks', array());
		if(is_string($cardNetworks))
			$cardNetworks = explode(',', $cardNetworks);
		$cardNetworks = array_filter(array_values($cardNetworks));
		if(empty($cardNetworks))
			$cardNetworks = array('VISA', 'MASTERCARD');

		$authMethods = $this->params->get('gpay_auth_methods', array());
		if(is_string($authMethods))
			$authMethods = explode(',', $authMethods);
		$authMethods = array_filter(array_values($authMethods));
		if(empty($authMethods))
			$authMethods = array('PAN_ONLY', 'CRYPTOGRAM_3DS');

		return array(
			'id' => 'gpay',
			'name' => 'com.google.pay',
			'config' => array(
				'api_version' => 2,
				'api_version_minor' => 0,
				'environment' => $this->params->get('gpay_environment', 'TEST'),
				'merchant_info' => array(
					'merchant_name' => $this->params->get('gpay_merchant_name', ''),
					'merchant_id' => $this->params->get('gpay_merchant_id', '')
				),
				'allowed_payment_methods' => array(
					array(
						'type' => 'CARD',
						'parameters' => array(
							'allowed_auth_methods' => $authMethods,
							'allowed_card_networks' => $cardNetworks
						),
						'tokenization_specification' => array(
							'type' => 'PAYMENT_GATEWAY',
							'parameters' => array(
								'gateway' => $gateway,
								'gatewayMerchantId' => $gatewayMerchantId
							)
						)
					)
				)
			)
		);
	}

}
