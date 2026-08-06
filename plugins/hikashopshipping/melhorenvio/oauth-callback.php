<?php

declare(strict_types=1);

/**
 * Public OAuth redirect endpoint. The hosting Nginx layer rejects some valid
 * Melhor Envio authorization codes in a query string sent to com_ajax. Keep
 * the provider callback separate and relay the sensitive values to Joomla by
 * POST, where the plugin validates and consumes the one-time state.
 */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
	http_response_code(405);
	exit;
}

$shippingId = (string) ($_GET['shipping_id'] ?? '');
$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');

if (!preg_match('/^[1-9][0-9]*$/', $shippingId)
	|| !preg_match('/^[A-Za-z0-9._~+\/-=]{20,2048}$/', $code)
	|| !preg_match('/^[a-f0-9]{64}$/', $state)) {
	http_response_code(400);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('success' => false, 'message' => 'Retorno OAuth inválido.'));
	exit;
}

if (!function_exists('curl_init')) {
	http_response_code(500);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('success' => false, 'message' => 'cURL indisponível para concluir a autorização.'));
	exit;
}

$target = 'https://www.aksolucoes.com.br/index.php?' . http_build_query(array(
	'option' => 'com_ajax',
	'plugin' => 'melhorenvio',
	'group' => 'hikashopshipping',
	'format' => 'json',
	'action' => 'callback',
	'shipping_id' => (int) $shippingId,
), '', '&', PHP_QUERY_RFC3986);

$handle = curl_init($target);
curl_setopt_array($handle, array(
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => http_build_query(array('code' => $code, 'state' => $state), '', '&', PHP_QUERY_RFC3986),
	CURLOPT_HTTPHEADER => array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'),
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT => 30,
));

$response = curl_exec($handle);
$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
curl_close($handle);

if (!is_string($response) || $response === '') {
	http_response_code(502);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('success' => false, 'message' => 'Não foi possível concluir a autorização.'));
	exit;
}

http_response_code($status >= 400 ? $status : 200);
header('Content-Type: application/json; charset=utf-8');
echo $response;
