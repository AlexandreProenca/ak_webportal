<?php
/**
 * HikaShop Mercado Pago payment plugin
 *
 * Integra o Mercado Pago Checkout Pro (Pix, boleto e cartão) usando a API
 * atual (Access Token / Bearer). O status do pedido nunca é alterado a partir
 * do conteúdo da notificação: o pagamento é sempre reconsultado na API do
 * Mercado Pago antes de qualquer mudança.
 *
 * @package AK Soluções
 * @license GNU/GPLv3
 */
defined('_JEXEC') or die('Restricted access');

class plgHikashoppaymentMercadopago extends hikashopPaymentPlugin
{
	/** Moedas suportadas pelo Mercado Pago. */
	var $accepted_currencies = array('BRL', 'ARS', 'MXN', 'CLP', 'COP', 'PEN', 'UYU');

	/** Moedas sem casas decimais. */
	var $rounding = array('CLP' => 0, 'COP' => 0);

	var $multiple = true;
	var $name = 'mercadopago';
	var $doc_form = 'mercadopago';

	const API_BASE = 'https://api.mercadopago.com';

	var $pluginConfig = array(
		'access_token'    => array('Access Token (produção)', 'input'),
		'webhook_secret'  => array('Assinatura secreta do webhook', 'input'),
		'sandbox'         => array('Modo de testes (sandbox)', 'boolean', 0),
		'max_installments'=> array('Máximo de parcelas', 'input'),
		'binary_mode'     => array('Aprovar ou recusar na hora (sem análise)', 'boolean', 0),
		'statement_descriptor' => array('Nome na fatura do cartão', 'input'),
		'notification'    => array('ALLOW_NOTIFICATIONS', 'boolean', 1),
		'debug'           => array('DEBUG', 'boolean', 0),
		'invalid_status'  => array('INVALID_STATUS', 'orderstatus'),
		'pending_status'  => array('PENDING_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus'),
		'refunded_status' => array('Situação em caso de estorno', 'orderstatus'),
	);

	/**
	 * Impede a criação do pedido se o plugin não estiver configurado.
	 */
	public function onBeforeOrderCreate(&$order, &$do) {
		if(parent::onBeforeOrderCreate($order, $do) === true)
			return true;

		if(empty($this->payment_params->access_token) && $this->plugin_data->payment_id == $order->order_payment_id) {
			$this->getApp()->enqueueMessage('Configure o Access Token no método de pagamento "Mercado Pago".');
			$do = false;
		}
	}

	/**
	 * Cria a preferência de pagamento e envia o cliente ao checkout.
	 */
	public function onAfterOrderConfirm(&$order, &$methods, $method_id) {
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		$rounding = $this->getRounding();

		// hika_order_id serve apenas para localizar as credenciais na notificação;
		// o pedido de verdade vem do external_reference devolvido pela API.
		$notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&hika_order_id=' . (int)$order->order_id . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
		$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order->order_id . $this->url_itemid;
		$cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order->order_id . $this->url_itemid;

		$total = round((float)$order->order_full_price, $rounding);

		$payload = array(
			'items'              => $this->buildItems($order, $total, $rounding),
			'external_reference' => (string)$order->order_id,
			'notification_url'   => $notify_url,
			'back_urls'          => array(
				'success' => $return_url,
				'pending' => $return_url,
				'failure' => $cancel_url,
			),
			'auto_return'        => 'approved',
		);

		$payer = $this->buildPayer($order);
		if(!empty($payer))
			$payload['payer'] = $payer;

		if(!empty($this->payment_params->max_installments)) {
			$payload['payment_methods'] = array(
				'installments' => (int)$this->payment_params->max_installments,
			);
		}

		if(!empty($this->payment_params->binary_mode))
			$payload['binary_mode'] = true;

		if(!empty($this->payment_params->statement_descriptor))
			$payload['statement_descriptor'] = substr($this->payment_params->statement_descriptor, 0, 22);

		if(!empty($this->payment_params->debug))
			$this->writeToLog(array('preference_request' => $payload));

		$response = $this->callApi('POST', '/checkout/preferences', $payload);

		if(!empty($this->payment_params->debug))
			$this->writeToLog(array('preference_response' => $response));

		$sandbox = !empty($this->payment_params->sandbox);
		$redirect = '';
		if($sandbox && !empty($response['body']['sandbox_init_point']))
			$redirect = $response['body']['sandbox_init_point'];
		elseif(!empty($response['body']['init_point']))
			$redirect = $response['body']['init_point'];

		if(empty($redirect)) {
			$message = 'Não foi possível iniciar o pagamento no Mercado Pago.';
			if(!empty($response['body']['message']))
				$message .= ' (' . $response['body']['message'] . ')';
			$this->writeToLog('Falha ao criar preferência: ' . json_encode($response));
			$this->getApp()->enqueueMessage($message, 'error');
			$this->getApp()->redirect($cancel_url);
			return false;
		}

		$this->vars = array(
			'redirect_url' => $redirect,
		);

		return $this->showPage('end');
	}

	/**
	 * Recebe a notificação do Mercado Pago.
	 *
	 * O corpo da notificação é tratado como não confiável: dele extraímos
	 * apenas o id do pagamento, que é então reconsultado na API.
	 */
	public function onPaymentNotification(&$statuses) {
		$paymentId = $this->extractPaymentId();
		if(empty($paymentId)) {
			$this->writeToLog('Notificação ignorada: sem id de pagamento.');
			return false;
		}

		// Carrega as credenciais: primeiro pelo pedido indicado na URL, com
		// recurso ao próprio método de pagamento publicado.
		$hintOrderId = (int)hikaInput::get()->getInt('hika_order_id', 0);
		if($hintOrderId > 0) {
			$hintOrder = $this->getOrder($hintOrderId);
			if(!empty($hintOrder))
				$this->loadPaymentParams($hintOrder);
		}
		if(empty($this->payment_params) && $this->pluginParams(0))
			$this->payment_params =& $this->plugin_params;

		if(empty($this->payment_params) || empty($this->payment_params->access_token)) {
			$this->writeToLog('Notificação recebida mas o plugin não está configurado.');
			return false;
		}

		if(isset($this->payment_params->notification) && !$this->payment_params->notification)
			return false;

		if(!$this->verifySignature($paymentId)) {
			$this->writeToLog('Notificação recusada: assinatura inválida (payment ' . $paymentId . ').');
			return false;
		}

		// Fonte da verdade: consulta autenticada à API.
		$response = $this->callApi('GET', '/v1/payments/' . rawurlencode($paymentId));
		$payment = isset($response['body']) ? $response['body'] : array();

		if(!empty($this->payment_params->debug))
			$this->writeToLog(array('payment_lookup' => $payment));

		if(empty($payment['status']) || !isset($payment['external_reference'])) {
			$this->writeToLog('Não foi possível consultar o pagamento ' . $paymentId . ' na API.');
			return false;
		}

		$order_id = (int)$payment['external_reference'];
		$dbOrder = $this->getOrder($order_id);
		if(empty($dbOrder)) {
			$this->writeToLog('Pagamento ' . $paymentId . ' referencia pedido inexistente: ' . $order_id);
			return false;
		}

		// Recarrega os parâmetros no contexto do pedido correto, preservando
		// os que já tínhamos caso este pedido não use este método.
		$fallbackParams = $this->payment_params;
		if(!$this->loadPaymentParams($dbOrder) || empty($this->payment_params))
			$this->payment_params = $fallbackParams;
		$this->loadOrderData($dbOrder);

		$url = HIKASHOP_LIVE . 'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id=' . $order_id;
		$order_text = "\r\n" . JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE', $dbOrder->order_number, HIKASHOP_LIVE);
		$order_text .= "\r\n" . str_replace('<br/>', "\r\n", JText::sprintf('ACCESS_ORDER_WITH_LINK', $url, $url));

		$status   = strtolower($payment['status']);
		$amount   = isset($payment['transaction_amount']) ? (float)$payment['transaction_amount'] : 0;
		$currency = isset($payment['currency_id']) ? $payment['currency_id'] : '';

		$history = new stdClass();
		$history->notified = 0;
		$history->amount = $amount . $currency;
		$history->data = 'Mercado Pago payment ' . $paymentId . ' - ' . $status;

		// Confere valor e moeda antes de aprovar.
		if(in_array($status, array('approved', 'authorized'))) {
			$rounding = $this->getRounding();
			$expected = round((float)$dbOrder->order_full_price, $rounding);
			$received = round($amount, $rounding);
			$expectedCurrency = $this->getCurrencyCode();

			$amountMismatch = (bccomp(sprintf('%F', $expected), sprintf('%F', $received), 2) != 0);
			$currencyMismatch = ($expectedCurrency !== '' && $currency !== $expectedCurrency);

			if($amountMismatch || $currencyMismatch) {
				$email = new stdClass();
				$email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER', 'Mercado Pago') . ' ' . JText::_('INVALID_AMOUNT');
				$email->body = 'Valor recebido (' . $received . ' ' . $currency . ') diferente do total do pedido (' . $expected . ' ' . $expectedCurrency . ').' . $order_text;
				$this->writeToLog('Valor divergente no pagamento ' . $paymentId . ': ' . $received . $currency . ' != ' . $expected . $expectedCurrency);
				$this->modifyOrder($order_id, $this->payment_params->invalid_status, $history, $email);
				return false;
			}
		}

		$order_status = $this->mapStatus($status);
		if(empty($order_status)) {
			$this->writeToLog('Status "' . $status . '" não altera o pedido ' . $order_id . '.');
			return false;
		}

		if($dbOrder->order_status == $order_status)
			return true;

		$history->notified = 1;

		$email = new stdClass();
		$email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Mercado Pago', $status, $dbOrder->order_number);
		$email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Mercado Pago', $status)) . ' ' .
			JText::sprintf('ORDER_STATUS_CHANGED', hikashop_orderStatus($order_status)) . "\r\n\r\n" . $order_text;

		$this->modifyOrder($order_id, $order_status, $history, $email);
		return true;
	}

	/**
	 * Converte o status do Mercado Pago na situação do pedido.
	 */
	protected function mapStatus($status) {
		switch($status) {
			case 'approved':
				return $this->payment_params->verified_status;
			case 'authorized':
			case 'pending':
			case 'in_process':
			case 'in_mediation':
				return $this->payment_params->pending_status;
			case 'rejected':
			case 'cancelled':
				return $this->payment_params->invalid_status;
			case 'refunded':
			case 'charged_back':
				return !empty($this->payment_params->refunded_status) ? $this->payment_params->refunded_status : $this->payment_params->invalid_status;
		}
		return '';
	}

	/**
	 * Extrai o id do pagamento aceitando os vários formatos de notificação.
	 *
	 * Atenção: o PHP troca "." por "_" nos nomes de parâmetros, então
	 * "data.id" chega como "data_id". A query string crua também é lida.
	 */
	protected function extractPaymentId() {
		$type = '';
		$id = '';

		if(isset($_GET['type']))    $type = (string)$_GET['type'];
		if(isset($_GET['topic']))   $type = (string)$_GET['topic'];
		if(isset($_GET['data_id'])) $id = (string)$_GET['data_id'];
		if(empty($id) && isset($_GET['id'])) $id = (string)$_GET['id'];

		// Query string crua, para o caso de "data.id" preservado.
		if(empty($id) && !empty($_SERVER['QUERY_STRING'])) {
			foreach(explode('&', $_SERVER['QUERY_STRING']) as $pair) {
				$parts = explode('=', $pair, 2);
				if(count($parts) == 2 && urldecode($parts[0]) === 'data.id')
					$id = urldecode($parts[1]);
			}
		}

		// Corpo JSON.
		$raw = file_get_contents('php://input');
		if(!empty($raw)) {
			$body = json_decode($raw, true);
			if(is_array($body)) {
				if(empty($id) && !empty($body['data']['id']))
					$id = (string)$body['data']['id'];
				if(empty($type) && !empty($body['type']))
					$type = (string)$body['type'];
				if(empty($type) && !empty($body['action']))
					$type = (string)$body['action'];
			}
		}

		// Só tratamos notificações de pagamento.
		if(!empty($type) && stripos($type, 'payment') === false)
			return '';

		return preg_match('#^[0-9a-zA-Z-]{1,64}$#', $id) ? $id : '';
	}

	/**
	 * Valida a assinatura x-signature (HMAC-SHA256).
	 *
	 * Sem secret configurado, seguimos em frente: a checagem definitiva
	 * continua sendo a consulta autenticada do pagamento na API.
	 */
	protected function verifySignature($paymentId) {
		if(empty($this->payment_params->webhook_secret))
			return true;

		$signature = isset($_SERVER['HTTP_X_SIGNATURE']) ? $_SERVER['HTTP_X_SIGNATURE'] : '';
		$requestId = isset($_SERVER['HTTP_X_REQUEST_ID']) ? $_SERVER['HTTP_X_REQUEST_ID'] : '';
		if(empty($signature))
			return false;

		$ts = '';
		$v1 = '';
		foreach(explode(',', $signature) as $part) {
			$kv = explode('=', trim($part), 2);
			if(count($kv) != 2) continue;
			$key = trim($kv[0]);
			if($key === 'ts') $ts = trim($kv[1]);
			if($key === 'v1') $v1 = trim($kv[1]);
		}

		if(empty($ts) || empty($v1))
			return false;

		// O manifesto usa o id em minúsculas quando alfanumérico.
		$id = strtolower($paymentId);
		$manifest = 'id:' . $id . ';request-id:' . $requestId . ';ts:' . $ts . ';';
		$expected = hash_hmac('sha256', $manifest, $this->payment_params->webhook_secret);

		return hash_equals($expected, $v1);
	}

	/**
	 * Monta os itens da preferência, garantindo que a soma bata com o total.
	 */
	protected function buildItems(&$order, $total, $rounding) {
		$items = array();
		$sum = 0;

		if(!empty($order->cart->products)) {
			foreach($order->cart->products as $product) {
				if(empty($product->order_product_quantity))
					continue;
				$price = round((float)$product->order_product_price + (float)@$product->order_product_tax, $rounding);
				if($price <= 0)
					continue;
				$qty = (int)$product->order_product_quantity;
				$items[] = array(
					'title'       => substr(strip_tags($product->order_product_name), 0, 250),
					'quantity'    => $qty,
					'unit_price'  => $price,
					'currency_id' => $this->getCurrencyCode(),
				);
				$sum += $price * $qty;
			}
		}

		$sum = round($sum, $rounding);
		$diff = round($total - $sum, $rounding);

		// Frete, impostos e descontos entram como ajuste para fechar o total.
		if(!empty($items) && abs($diff) > 0) {
			$items[] = array(
				'title'       => $diff > 0 ? 'Frete e encargos' : 'Desconto',
				'quantity'    => 1,
				'unit_price'  => $diff,
				'currency_id' => $this->getCurrencyCode(),
			);
			$sum = $total;
		}

		// Sem itens utilizáveis, cobra o total em uma linha única.
		if(empty($items) || round($sum, $rounding) != $total) {
			$items = array(array(
				'title'       => 'Pedido ' . $order->order_number,
				'quantity'    => 1,
				'unit_price'  => $total,
				'currency_id' => $this->getCurrencyCode(),
			));
		}

		return $items;
	}

	/**
	 * Dados do comprador, quando disponíveis.
	 */
	protected function buildPayer(&$order) {
		$payer = array();

		if(!empty($order->customer->user_email))
			$payer['email'] = $order->customer->user_email;

		$address = null;
		if(!empty($order->cart->billing_address))
			$address = $order->cart->billing_address;
		elseif(!empty($order->cart->shipping_address))
			$address = $order->cart->shipping_address;

		if(!empty($address)) {
			if(!empty($address->address_firstname))
				$payer['name'] = $address->address_firstname;
			if(!empty($address->address_lastname))
				$payer['surname'] = $address->address_lastname;
			if(!empty($address->address_telephone))
				$payer['phone'] = array('number' => preg_replace('#[^0-9]#', '', $address->address_telephone));
		}

		return $payer;
	}

	/**
	 * Chamada HTTP autenticada à API do Mercado Pago.
	 */
	protected function callApi($method, $path, $payload = null) {
		$url = self::API_BASE . $path;

		$headers = array(
			'Authorization: Bearer ' . trim($this->payment_params->access_token),
			'Accept: application/json',
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if($method === 'POST') {
			$headers[] = 'Content-Type: application/json';
			// Evita cobrança duplicada se a requisição for repetida.
			$headers[] = 'X-Idempotency-Key: ' . md5($path . json_encode($payload));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$raw = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if($raw === false) {
			$this->writeToLog('Erro de conexão com o Mercado Pago: ' . $error);
			return array('code' => 0, 'body' => array());
		}

		$body = json_decode($raw, true);
		return array('code' => $code, 'body' => is_array($body) ? $body : array());
	}

	protected function getRounding() {
		$code = $this->getCurrencyCode();
		return ($code !== '' && isset($this->rounding[$code])) ? $this->rounding[$code] : 2;
	}

	/**
	 * loadOrderData() zera $this->currency quando o pedido não tem moeda,
	 * então nunca acessamos a propriedade diretamente.
	 */
	protected function getCurrencyCode() {
		if(!empty($this->currency) && is_object($this->currency) && !empty($this->currency->currency_code))
			return $this->currency->currency_code;
		return '';
	}

	function writeToLog($data = null) {
		if(!empty($data))
			hikashop_writeToLog($data, $this->name);
	}

	public function onPaymentConfiguration(&$element) {
		parent::onPaymentConfiguration($element);

		if(empty($element->payment_params->access_token)) {
			$this->getApp()->enqueueMessage('Informe o Access Token da sua conta Mercado Pago (Suas integrações &rarr; Credenciais de produção).');
		}
	}

	/**
	 * $this->app nem sempre está inicializado (ex.: tela de configuração).
	 */
	protected function getApp() {
		if(empty($this->app))
			$this->app = JFactory::getApplication();
		return $this->app;
	}

	public function getPaymentDefaultValues(&$element) {
		$element->payment_name = 'Mercado Pago';
		$element->payment_description = 'Pague com Pix, boleto ou cartão de crédito pelo Mercado Pago.';
		$element->payment_images = 'Pix,Boleto,VISA,MasterCard,Credit_card';

		$element->payment_params->notification = 1;
		$element->payment_params->sandbox = 0;
		$element->payment_params->binary_mode = 0;
		$element->payment_params->max_installments = 12;
		$element->payment_params->invalid_status = 'cancelled';
		$element->payment_params->pending_status = 'created';
		$element->payment_params->verified_status = 'confirmed';
		$element->payment_params->refunded_status = 'refunded';
	}
}
