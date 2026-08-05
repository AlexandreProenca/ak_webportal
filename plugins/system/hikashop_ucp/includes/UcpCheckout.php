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

class HikashopUcpCheckout {

	var $params = null;
	var $mapper = null;

	function __construct(&$params) {
		$this->params = $params;
		$this->mapper = new HikashopUcpMapper($params);
	}

	function create() {
		try {
			$input = $this->_getJsonInput();
			if(empty($input['line_items'])) {
				$this->_outputError('line_items is required', 400, 'Bad Request');
				return;
			}

			$idempotencyKey = $this->_getIdempotencyKey();
			if(!empty($idempotencyKey)) {
				$existingCartId = $this->_findByIdempotencyKey($idempotencyKey);
				if(!empty($existingCartId)) {
					$this->_adoptCartSession($existingCartId);
					$cartClass = hikashop_get('class.cart');
					$fullCart = $cartClass->getFullCart($existingCartId, array('ucp' => true));
					if(!empty($fullCart)) {
						$status = $this->_getUcpStatus($fullCart);
						$session = $this->mapper->cartToSession($fullCart, $status);
						$this->_outputJson($session, 201);
						return;
					}
				}
			}

			$cartClass = hikashop_get('class.cart');

			$userId = 0;
			if(!empty($input['buyer']['email'])) {
				$userId = $this->_resolveUserId($input['buyer']['email']);
			}

			$cart = new stdClass();
			$cart->cart_type = 'ucp';
			$cart->user_id = $userId;
			$cart->cart_current = 0;
			$cart->cart_currency_id = hikashop_getCurrency();

			$ucpToken = $this->_generateSessionToken();
			$sessionName = JFactory::getSession()->getName();
			if(!empty($sessionName) && !empty($_COOKIE[$sessionName])) {
				$cart->session_id = JFactory::getSession()->getId();
			} else {
				$cart->session_id = $ucpToken;
			}

			$cart->cart_params = json_encode(array(
				'ucp_token' => $ucpToken,
				'ucp_status' => 'incomplete'
			));
			if(!empty($idempotencyKey))
				$cart->cart_name = 'idem:' . $idempotencyKey;

			$cartId = $cartClass->save($cart);
			if(empty($cartId)) {
				$this->_outputError('Failed to create checkout session', 500);
				return;
			}

			$products = $this->_buildProductsArray($input['line_items']);
			if(empty($products)) {
				$this->_deleteCart($cartId);
				$this->_outputError('No valid product IDs provided in line_items', 400, 'Bad Request');
				return;
			}

			$result = $cartClass->addProduct($cartId, $products);
			if($result === false) {
				$errors = $this->_getCartMessages($cartId);
				$this->_deleteCart($cartId);
				$msg = !empty($errors) ? implode('; ', $errors) : 'Products could not be added to the checkout session';
				$this->_outputError($msg, 400, 'Bad Request');
				return;
			}

			$rawCart = $this->_loadUcpCart($cartId);
			if($rawCart) {
				$this->_applySessionData($rawCart, $input);
			}

			$fullCart = $cartClass->getFullCart($cartId, array('ucp' => true));
			if(empty($fullCart)) {
				$this->_deleteCart($cartId);
				$this->_outputError('Failed to load checkout session', 500);
				return;
			}

			$session = $this->mapper->cartToSession($fullCart, 'incomplete');
			$this->_outputJson($session, 201);
		} catch(Exception $e) {
			$this->_outputError('Create error: ' . $e->getMessage(), 500);
		} catch(Error $e) {
			$this->_outputError('Create error: ' . $e->getMessage(), 500);
		}
	}

	function get($id) {
		try {
			$cartId = $this->_resolveCartId($this->mapper->parseGlobalId($id));
			if(empty($cartId)) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}

			$this->_adoptCartSession($cartId);

			$cartClass = hikashop_get('class.cart');
			$cart = $cartClass->getFullCart($cartId, array('ucp' => true));
			if(empty($cart)) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}

			$status = $this->_getUcpStatus($cart);

			$session = $this->mapper->cartToSession($cart, $status);

			if($status === 'incomplete') {
				require_once __DIR__.'/UcpPayment.php';
				$ucpPayment = new HikashopUcpPayment($this->params);
				$session['available_payment_methods'] = $ucpPayment->getAvailableMethods($cart);
			}

			$this->_outputJson($session);
		} catch(Exception $e) {
			$this->_outputError('Get error: ' . $e->getMessage(), 500);
		} catch(Error $e) {
			$this->_outputError('Get error: ' . $e->getMessage(), 500);
		}
	}

	function update($id) {
		try {
			$cartId = $this->_resolveCartId($this->mapper->parseGlobalId($id));
			$rawCart = $this->_loadUcpCart($cartId);
			if(!$rawCart) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}

			$status = $this->_getUcpStatus($rawCart);
			if($status !== 'incomplete') {
				$this->_outputError('Cannot update a ' . $status . ' session', 400, 'Bad Request');
				return;
			}

			$input = $this->_getJsonInput();
			$cartClass = hikashop_get('class.cart');

			$this->_adoptCartSession($cartId);

			if(!empty($input['line_items'])) {
				$products = $this->_buildProductsArray($input['line_items']);
				if(empty($products)) {
					$this->_outputError('No valid product IDs provided in line_items', 400, 'Bad Request');
					return;
				}

				$this->_clearCartProducts($cartId);

				$cartClass->get('reset_cache', $cartId);

				$result = $cartClass->addProduct($cartId, $products);
				if($result === false) {
					$errors = $this->_getCartMessages($cartId);
					if(empty($errors)) {
						$app = JFactory::getApplication();
						$appMessages = $app->getMessageQueue();
						foreach($appMessages as $m) {
							if(!empty($m['message']))
								$errors[] = $m['message'];
						}
					}
					$msg = !empty($errors) ? implode('; ', $errors) : 'Products could not be updated';
					$this->_outputError($msg, 400, 'Bad Request');
					return;
				}
			}

			$rawCart = $this->_loadUcpCart($cartId);
			$this->_applySessionData($rawCart, $input);

			$cartClass->get('reset_cache', $cartId);

			$fullCart = $cartClass->getFullCart($cartId, array('ucp' => true));

			$session = $this->mapper->cartToSession($fullCart, 'incomplete');
			$this->_outputJson($session);
		} catch(Exception $e) {
			$this->_outputError('Update error: ' . $e->getMessage(), 500);
		} catch(Error $e) {
			$this->_outputError('Update error: ' . $e->getMessage(), 500);
		}
	}

	function complete($id) {
		try {
			$cartId = $this->_resolveCartId($this->mapper->parseGlobalId($id));
			$this->_adoptCartSession($cartId);

			$cartClass = hikashop_get('class.cart');
			$cart = $cartClass->getFullCart($cartId, array('ucp' => true));
			if(empty($cart)) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}

			$status = $this->_getUcpStatus($cart);
			if($status !== 'incomplete') {
				$this->_outputError('Cannot complete a ' . $status . ' session', 400, 'Bad Request');
				return;
			}

			$input = $this->_getJsonInput();
			$orderClass = hikashop_get('class.order');

			$paymentData = !empty($input['payment_data']) ? $input['payment_data'] : array();
			$riskSignals = !empty($input['risk_signals']) ? $input['risk_signals'] : array();
			$paymentMethodId = !empty($input['payment_method_id']) ? $input['payment_method_id'] : '';
			$credential = !empty($paymentData['credential']) ? $paymentData['credential'] : null;

			if(empty($paymentMethodId) && empty($credential)) {
				$this->_outputError('Payment information is required: provide payment_method_id or payment_data with credential', 400, 'Bad Request');
				return;
			}

			if(!empty($cart->messages))
				$cart->messages = array();

			ob_start();

			$order = $orderClass->createFromCart($cart, array('skipPayment' => true));
			if(empty($order)) {
				ob_end_clean();
				$this->_outputError('Failed to create order: createFromCart returned empty', 500);
				return;
			}
			if(empty($order->order_id)) {
				ob_end_clean();
				if(!empty($order->error))
					hikashop_writeToLog('UCP createFromCart error: ' . $order->error);
				$this->_outputError('Failed to create order', 500);
				return;
			}

			$orderId = (int)$order->order_id;

			if(!empty($paymentMethodId) && empty($credential)) {
				$localPaymentId = (int)$this->mapper->parseGlobalId($paymentMethodId);

				$db = JFactory::getDBO();
				$db->setQuery('SELECT payment_type FROM '.hikashop_table('payment').' WHERE payment_id = '.(int)$localPaymentId);
				$paymentType = $db->loadResult();

				$orderUpdate = new stdClass();
				$orderUpdate->order_id = $orderId;
				$orderUpdate->order_payment_id = $localPaymentId;
				if(!empty($paymentType))
					$orderUpdate->order_payment_method = $paymentType;
				$orderClass->save($orderUpdate);

				$order = $orderClass->loadFullOrder($orderId, false, false);
				$this->_prepareOrderForPayment($order);

				require_once __DIR__.'/UcpPayment.php';
				$ucpPayment = new HikashopUcpPayment($this->params);
				$result = $ucpPayment->generatePaymentURL($order, $localPaymentId);

				if(!empty($result['error'])) {
					ob_end_clean();
					$this->_outputError($result['error'], 400, 'Bad Request');
					return;
				}

				$this->_updateCartStatus($cartId, 'requires_payment');

				$buffered = ob_get_clean();
				if(!empty($buffered))
					hikashop_writeToLog('UCP complete() captured output: ' . $buffered);
				$session = $this->mapper->orderToCompletedSession($cart, $order, $paymentData);
				$session['status'] = 'requires_payment';
				$session['continue_url'] = $result['url'];
				$this->_outputJson($session);
				return;
			}

			if(!empty($paymentMethodId) && !empty($credential)) {
				$localPaymentId = (int)$this->mapper->parseGlobalId($paymentMethodId);

				$db = JFactory::getDBO();
				$db->setQuery('SELECT payment_type FROM '.hikashop_table('payment').' WHERE payment_id = '.(int)$localPaymentId);
				$paymentType = $db->loadResult();

				$orderUpdate = new stdClass();
				$orderUpdate->order_id = $orderId;
				$orderUpdate->order_payment_id = $localPaymentId;
				if(!empty($paymentType))
					$orderUpdate->order_payment_method = $paymentType;
				$orderUpdate->order_payment_params = json_encode(array(
					'payment_instrument' => $paymentData,
					'risk_signals' => $riskSignals
				));
				$orderClass->save($orderUpdate);

				$order = $orderClass->loadFullOrder($orderId, false, false);
				$this->_prepareOrderForPayment($order);
			}

			if(!empty($paymentData)) {
				$orderUpdate = new stdClass();
				$orderUpdate->order_id = $orderId;
				$orderUpdate->order_payment_method = 'ucp';
				$orderUpdate->order_payment_params = json_encode(array(
					'payment_instrument' => $paymentData,
					'risk_signals' => $riskSignals
				));
				$orderClass->save($orderUpdate);
			}

			$order = $orderClass->loadFullOrder($orderId, false, false);
			$this->_prepareOrderForPayment($order);

			$paymentResult = new stdClass();
			$paymentResult->success = false;
			$paymentResult->message = '';
			$paymentResult->transaction_id = '';

			$handlerId = !empty($paymentData['handler_id']) ? $paymentData['handler_id'] : '';

			if(!empty($credential)) {
				if(HIKASHOP_J50 && !class_exists('JFactory'))
					class_alias('Joomla\\CMS\\Factory', 'JFactory');
				$app = JFactory::getApplication();
				JPluginHelper::importPlugin('hikashoppayment');

				$paymentContext = array(
					'payment_instrument' => $paymentData,
					'credential' => $credential,
					'handler_id' => $handlerId,
					'risk_signals' => $riskSignals
				);
				$app->triggerEvent('onUcpPaymentProcess', array(&$order, &$paymentContext, &$paymentResult));

				if(!empty($paymentResult->requires_redirect) && !empty($paymentResult->continue_url)) {
					$this->_updateCartStatus($cartId, 'requires_payment');
					$buffered = ob_get_clean();
					if(!empty($buffered))
						hikashop_writeToLog('UCP complete() captured output: ' . $buffered);
					$session = $this->mapper->orderToCompletedSession($cart, $order, $paymentData);
					$session['status'] = 'requires_payment';
					$session['continue_url'] = $paymentResult->continue_url;
					$this->_outputJson($session);
					return;
				}

				if($paymentResult->success) {
					$paymentParams = new stdClass();
					$paymentParams->ucp_payment_instrument = $paymentData;
					if(!empty($paymentResult->transaction_id))
						$paymentParams->transaction_id = $paymentResult->transaction_id;

					$orderClass->modifyOrder(
						$orderId,
						'confirmed',
						'ucp',
						array('notified' => 1, 'data' => !empty($paymentResult->transaction_id) ? $paymentResult->transaction_id : ''),
						true,
						$paymentParams
					);
					$order = $orderClass->loadFullOrder($orderId, false, false);
				} else {
					$orderClass->modifyOrder(
						$orderId,
						'cancelled',
						'ucp',
						array('notified' => 0, 'data' => 'UCP payment failed: ' . $paymentResult->message),
						false
					);
					$this->_updateCartStatus($cartId, 'incomplete');
					$buffered = ob_get_clean();
					if(!empty($buffered))
						hikashop_writeToLog('UCP complete() captured output: ' . $buffered);
					$errorMsg = !empty($paymentResult->message) ? $paymentResult->message : 'Payment processing failed';
					$this->_outputError($errorMsg, 402, 'Payment Required');
					return;
				}
			}

			$this->_updateCartStatus($cartId, 'completed');

			$cart = $cartClass->getFullCart($cartId, array('ucp' => true));

			$buffered = ob_get_clean();
			if(!empty($buffered)) {
				hikashop_writeToLog('UCP complete() captured output: ' . $buffered);
			}

			$session = $this->mapper->orderToCompletedSession($cart, $order, $paymentData);
			$this->_outputJson($session);
		} catch(Exception $e) {
			if(ob_get_level()) {
				$buffered = ob_get_clean();
				if(!empty($buffered))
					hikashop_writeToLog('UCP complete() captured output on error: ' . $buffered);
			}
			$this->_outputError('Complete error: ' . $e->getMessage(), 500);
		} catch(Error $e) {
			if(ob_get_level()) {
				$buffered = ob_get_clean();
				if(!empty($buffered))
					hikashop_writeToLog('UCP complete() captured output on error: ' . $buffered);
			}
			$this->_outputError('Complete error: ' . $e->getMessage(), 500);
		}
	}

	function cancel($id) {
		try {
			$cartId = $this->_resolveCartId($this->mapper->parseGlobalId($id));
			if(empty($cartId)) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}
			$this->_adoptCartSession($cartId);
			if(!$this->_isUcpCart($cartId)) {
				$this->_outputError('Checkout session not found', 404, 'Not Found');
				return;
			}

			$this->_updateCartStatus($cartId, 'canceled');

			$cartClass = hikashop_get('class.cart');
			$fullCart = $cartClass->getFullCart($cartId, array('ucp' => true));
			$session = $this->mapper->cartToSession($fullCart, 'canceled');
			$this->_outputJson($session);
		} catch(Exception $e) {
			$this->_outputError('Cancel error: ' . $e->getMessage(), 500);
		} catch(Error $e) {
			$this->_outputError('Cancel error: ' . $e->getMessage(), 500);
		}
	}

	private function _applySessionData($rawCart, $input) {
		$cartClass = hikashop_get('class.cart');
		$hasChanges = false;

		if(!empty($input['buyer']['email'])) {
			$userId = $this->_resolveUserId($input['buyer']['email']);
			if(!empty($userId) && (int)$rawCart->user_id !== $userId) {
				$rawCart->user_id = $userId;
				$hasChanges = true;
			}
		}

		$addressData = $this->mapper->mapIncomingAddress($input);
		$hasAddressData = !empty($addressData->address_country) || !empty($addressData->address_post_code) || !empty($addressData->address_street) || !empty($addressData->address_firstname) || !empty($addressData->address_lastname);

		if($hasAddressData) {
			$addressClass = hikashop_get('class.address');
			$addressData->address_user_id = (int)$rawCart->user_id;
			$addressData->address_published = 1;
			$addressData->skip_user_check = true;

			$existingAddressId = !empty($rawCart->cart_shipping_address_ids) ? (int)$rawCart->cart_shipping_address_ids : 0;
			if($existingAddressId > 0) {
				$addressData->address_id = $existingAddressId;
			}

			$addressId = $addressClass->save($addressData);
			if(!empty($addressId)) {
				$rawCart->cart_shipping_address_ids = (string)$addressId;
				$rawCart->cart_billing_address_id = (int)$addressId;
				$hasChanges = true;
			}
		}

		if(!empty($input['fulfillment']['methods'])) {
			foreach($input['fulfillment']['methods'] as $method) {
				if(!empty($method['selected_option_id'])) {
					$shippingId = (int)$method['selected_option_id'];
					$rawCart->cart_shipping_ids = $shippingId.'@0';
					$hasChanges = true;
					break;
				}
			}
		}

		if(isset($input['discount_code'])) {
			$rawCart->cart_coupon = $input['discount_code'];
			$hasChanges = true;
		}

		if($hasChanges) {
			$existingCart = $cartClass->get($rawCart->cart_id, null, array('skip_user_check' => true));
			if(!empty($existingCart->cart_products)) {
				$rawCart->cart_products = $existingCart->cart_products;
			}

			$cartClass->save($rawCart);
		}
	}

	private function _resolveUserId($email) {
		$email = trim($email);
		if(empty($email))
			return 0;

		$userClass = hikashop_get('class.user');
		$user = $userClass->get($email, 'email');

		if(!empty($user) && !empty($user->user_id))
			return (int)$user->user_id;

		$newUser = new stdClass();
		$newUser->user_email = $email;
		$userId = $userClass->save($newUser);

		return !empty($userId) ? (int)$userId : 0;
	}

	private function _buildProductsArray($lineItems) {
		$products = array();
		foreach($lineItems as $lineItem) {
			$productId = 0;
			if(!empty($lineItem['item']['id']))
				$productId = (int)$this->mapper->parseGlobalId($lineItem['item']['id']);

			if(empty($productId))
				continue;

			$products[] = array(
				'id' => $productId,
				'qty' => !empty($lineItem['quantity']) ? (int)$lineItem['quantity'] : 1
			);
		}
		return $products;
	}

	private function _adoptCartSession($cartId) {
		$jsession = JFactory::getSession();
		$database = JFactory::getDBO();
		$database->setQuery('UPDATE #__hikashop_cart SET session_id = '.$database->Quote($jsession->getId()).' WHERE cart_id = '.(int)$cartId.' AND cart_type = '.$database->Quote('ucp'));
		$database->execute();
	}

	private function _clearCartProducts($cartId) {
		$database = JFactory::getDBO();
		$database->setQuery('DELETE FROM #__hikashop_cart_product WHERE cart_id = '.(int)$cartId);
		$database->execute();
	}

	private function _resolveCartId($identifier) {
		if(empty($identifier))
			return 0;

		$database = JFactory::getDBO();

		if(!is_numeric($identifier) && strpos($identifier, 'ucp_') === 0) {
			$database->setQuery(
				'SELECT cart_id FROM #__hikashop_cart'
				.' WHERE cart_type = '.$database->Quote('ucp')
				.' AND cart_params LIKE '.$database->Quote('%"ucp_token":"'.$database->escape($identifier, true).'"%')
				.' LIMIT 1'
			);
			return (int)$database->loadResult();
		}

		$database->setQuery('SELECT cart_id FROM #__hikashop_cart WHERE cart_id = '.(int)$identifier.' AND cart_type = '.$database->Quote('ucp'));
		return (int)$database->loadResult();
	}

	private function _isUcpCart($cartId) {
		if(empty($cartId))
			return false;
		$database = JFactory::getDBO();
		$database->setQuery('SELECT cart_id FROM #__hikashop_cart WHERE cart_id = '.(int)$cartId.' AND cart_type = '.$database->Quote('ucp'));
		return !empty($database->loadResult());
	}

	private function _loadUcpCart($cartId) {
		if(empty($cartId))
			return null;

		$database = JFactory::getDBO();
		$database->setQuery('SELECT * FROM #__hikashop_cart WHERE cart_id = '.(int)$cartId.' AND cart_type = '.$database->Quote('ucp'));
		$cart = $database->loadObject();
		if(empty($cart))
			return null;

		$sessionName = JFactory::getSession()->getName();
		if(!empty($sessionName) && !empty($_COOKIE[$sessionName])) {
			$jsessionId = JFactory::getSession()->getId();
			if(!empty($cart->session_id) && $cart->session_id !== $jsessionId)
				return null;
		}

		return $cart;
	}

	private function _getUcpStatus($cart) {
		$params = !empty($cart->cart_params) ? $cart->cart_params : null;
		if(is_string($params))
			$params = json_decode($params);
		if(is_object($params) && !empty($params->ucp_status))
			return $params->ucp_status;
		if(is_array($params) && !empty($params['ucp_status']))
			return $params['ucp_status'];
		return 'incomplete';
	}

	private function _updateCartStatus($cartId, $status) {
		$database = JFactory::getDBO();
		$database->setQuery('SELECT cart_params FROM #__hikashop_cart WHERE cart_id = '.(int)$cartId.' AND cart_type = '.$database->Quote('ucp'));
		$raw = $database->loadResult();

		$params = !empty($raw) ? json_decode($raw, true) : array();
		$params['ucp_status'] = $status;

		$database->setQuery('UPDATE #__hikashop_cart SET cart_params = '.$database->Quote(json_encode($params)).' WHERE cart_id = '.(int)$cartId);
		$database->execute();
	}

	private function _deleteCart($cartId) {
		$database = JFactory::getDBO();
		$database->setQuery('DELETE FROM #__hikashop_cart_product WHERE cart_id = '.(int)$cartId);
		$database->execute();
		$database->setQuery('DELETE FROM #__hikashop_cart WHERE cart_id = '.(int)$cartId.' AND cart_type = '.$database->Quote('ucp'));
		$database->execute();
	}

	private function _getCartMessages($cartId) {
		$cartClass = hikashop_get('class.cart');
		$cart = $cartClass->get($cartId, null, array('skip_user_check' => true));
		$messages = array();
		if(!empty($cart->messages)) {
			foreach($cart->messages as $msg) {
				if(!empty($msg->msg))
					$messages[] = $msg->msg;
				elseif(is_string($msg))
					$messages[] = $msg;
			}
		}
		return $messages;
	}

	private function _getIdempotencyKey() {
		if(!empty($_SERVER['HTTP_IDEMPOTENCY_KEY']))
			return substr(trim($_SERVER['HTTP_IDEMPOTENCY_KEY']), 0, 200);
		return '';
	}

	private function _prepareOrderForPayment(&$order) {
		if(empty($order))
			return;

		if(!empty($order->order_user_id)) {
			$app = JFactory::getApplication();
			$app->setUserState(HIKASHOP_COMPONENT.'.user_id', (int)$order->order_user_id);
			$app->setUserState(HIKASHOP_COMPONENT.'.shipping_address', @$order->order_shipping_address_id);
			$app->setUserState(HIKASHOP_COMPONENT.'.billing_address', @$order->order_billing_address_id);
			hikashop_loadUser(false, true); // reset cached user
		}

		$order->cart =& $order;

		if(empty($order->cart->full_total)) {
			$order->cart->full_total = new stdClass();
			$price = new stdClass();
			$price->price_value_with_tax = (float)$order->order_full_price;
			$price->price_value = (float)$order->order_full_price - (float)@$order->order_tax;
			$price->price_currency_id = !empty($order->order_currency_id) ? (int)$order->order_currency_id : 0;
			$order->cart->full_total->prices = array($price);
		}

		if(empty($order->cart->total) && !empty($order->products)) {
			$config = hikashop_config();
			if($config->get('group_options', 0)) {
				foreach($order->cart->products as $k => $product) {
					if(!empty($product->order_product_option_parent_id)) {
						foreach($order->cart->products as $k2 => $product2) {
							if($product->order_product_option_parent_id == $product2->order_product_id) {
								$product2->order_product_price += $product->order_product_price;
								$product2->order_product_tax += $product->order_product_tax;
								$product2->order_product_total_price_no_vat += $product->order_product_total_price_no_vat;
								$product2->order_product_total_price += $product->order_product_total_price;
							}
						}
					}
				}
			}
			$order->cart->total = new stdClass();
			$currencyClass = hikashop_get('class.currency');
			$currencyClass->calculateTotal($order->cart->products, $order->cart->total, $order->order_currency_id);
		}

		if(empty($order->cart->coupon) && !empty($order->order_discount_price) && bccomp(sprintf('%F', $order->order_discount_price), 0, 5) !== 0) {
			$order->cart->coupon = new stdClass();
			$order->cart->coupon->discount_value =& $order->order_discount_price;
		}

		if(!isset($order->cart->additional))
			$order->cart->additional = array();
	}

	private function _findByIdempotencyKey($key) {
		$database = JFactory::getDBO();
		$cutoff = time() - 86400;
		$database->setQuery(
			'SELECT cart_id FROM #__hikashop_cart'.
			' WHERE cart_type = '.$database->Quote('ucp').
			' AND cart_name = '.$database->Quote('idem:'.$key).
			' AND cart_modified > '.(int)$cutoff.
			' ORDER BY cart_id DESC LIMIT 1'
		);
		return (int)$database->loadResult();
	}

	private function _generateSessionToken() {
		return 'ucp_'.time().'_'.bin2hex(random_bytes(8));
	}

	private function _getJsonInput() {
		if(!empty($GLOBALS['_hikashop_ucp_mcp_input'])) {
			$raw = $GLOBALS['_hikashop_ucp_mcp_input'];
			unset($GLOBALS['_hikashop_ucp_mcp_input']);
		} else {
			$raw = file_get_contents('php://input');
		}
		if(empty($raw))
			return array();
		$data = json_decode($raw, true);
		return is_array($data) ? $data : array();
	}

	private function _outputJson($data, $code = 200) {
		if($code !== 200)
			header($_SERVER['SERVER_PROTOCOL'].' '.$code, true, $code);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}

	private function _outputError($message, $code = 500, $httpText = 'Internal Server Error') {
		header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$httpText, true, $code);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'status' => 'error',
			'messages' => array(
				array('type' => 'error', 'message' => $message)
			)
		));
		exit;
	}
}
