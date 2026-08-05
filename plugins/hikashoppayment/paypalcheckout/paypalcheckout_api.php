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
class PaypalCheckoutAPI {


	protected $baseUrl = '';


	protected $clientId = '';


	protected $clientSecret = '';


	protected $bncode = '';


	protected $debug = false;


	protected $accessToken = '';


	protected $tokenExpiry = 0;

	public function __construct($clientId, $clientSecret, $sandbox, $bncode = '', $debug = false) {
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->baseUrl = $sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
		$this->bncode = $bncode;
		$this->debug = $debug;
	}

	public function getAccessToken() {
		if(!empty($this->accessToken) && time() < ($this->tokenExpiry - 60))
			return $this->accessToken;

		$result = $this->requestRaw('POST', $this->baseUrl . '/v1/oauth2/token',
			'grant_type=client_credentials',
			array(
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
			)
		);

		if(empty($result->access_token))
			throw new Exception('PayPal OAuth2 token request failed');

		$this->accessToken = $result->access_token;
		$this->tokenExpiry = time() + (isset($result->expires_in) ? (int)$result->expires_in : 32400);

		return $this->accessToken;
	}

	public function getIdToken($customerId = null) {
		$body = 'grant_type=client_credentials&response_type=id_token';
		if(!empty($customerId))
			$body .= '&target_customer_id=' . urlencode($customerId);

		$result = $this->requestRaw('POST', $this->baseUrl . '/v1/oauth2/token',
			$body,
			array(
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
			)
		);

		return !empty($result->id_token) ? $result->id_token : null;
	}

	public function request($method, $endpoint, $data = null, $extraHeaders = array()) {
		$token = $this->getAccessToken();

		$headers = array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token,
		);
		if(!empty($this->bncode))
			$headers[] = 'PayPal-Partner-Attribution-Id: ' . $this->bncode;

		foreach($extraHeaders as $h) {
			$headers[] = $h;
		}

		$url = $this->baseUrl . $endpoint;
		$body = null;
		if(in_array($method, array('POST', 'PUT', 'PATCH'))) {
			$body = !empty($data) ? (is_string($data) ? $data : json_encode($data)) : '{}';
		}

		return $this->requestRaw($method, $url, $body, $headers);
	}

	public function requestRaw($method, $url, $body = null, $headers = array()) {
		if(!empty($this->bncode)) {
			$hasBncode = false;
			foreach($headers as $h) {
				if(stripos($h, 'PayPal-Partner-Attribution-Id') !== false) {
					$hasBncode = true;
					break;
				}
			}
			if(!$hasBncode)
				$headers[] = 'PayPal-Partner-Attribution-Id: ' . $this->bncode;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
		curl_setopt($ch, CURLOPT_CAPATH, __DIR__ . '/cacert.pem');

		if($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			if(!empty($body))
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		} elseif($method !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if(!empty($body))
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		if($this->debug)
			hikashop_writeToLog('PayPal API ' . $method . ' ' . $url . ($body ? "\n" . $body : ''));

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);

		if(!empty($curlError))
			throw new Exception('PayPal API curl error: ' . $curlError);

		if($this->debug)
			hikashop_writeToLog('PayPal API response (' . $httpCode . '): ' . $response);

		if(empty($response))
			return null;

		return json_decode($response);
	}


	public function createOrder($orderData) {
		return $this->request('POST', '/v2/checkout/orders', $orderData, array('Prefer: return=representation'));
	}

	public function getOrder($orderId) {
		return $this->request('GET', '/v2/checkout/orders/' . urlencode($orderId));
	}

	public function captureOrder($orderId) {
		return $this->request('POST', '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
	}

	public function authorizeOrder($orderId) {
		return $this->request('POST', '/v2/checkout/orders/' . urlencode($orderId) . '/authorize');
	}

	public function reauthorize($authorizationId) {
		return $this->request('POST', '/v2/payments/authorizations/' . urlencode($authorizationId) . '/reauthorize');
	}

	public function captureAuthorization($authorizationId) {
		return $this->request('POST', '/v2/payments/authorizations/' . urlencode($authorizationId) . '/capture');
	}

	public function generateClientToken($customerId = null) {
		$data = new stdClass();
		if(!empty($customerId))
			$data->customer_id = $customerId;
		return $this->request('POST', '/v1/identity/generate-token', $data);
	}
}
