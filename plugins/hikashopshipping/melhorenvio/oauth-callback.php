<?php

declare(strict_types=1);

/**
 * Public OAuth redirect endpoint.
 *
 * The callback must not relay the authorization code through a second local
 * HTTP request: hosting security rules can reject the valid one-time code
 * before Joomla receives it. Bootstrap only the services required to validate
 * the state and exchange the code with Melhor Envio directly.
 */

function akMelhorEnvioCallbackResponse(int $status, string $message): never
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('success' => $status < 400, 'message' => $message), JSON_UNESCAPED_UNICODE);
	exit;
}

function akMelhorEnvioCallbackParams(string $stored): ?object
{
	if ($stored === '') {
		return null;
	}

	if ($stored[0] === '{' || $stored[0] === '[') {
		$params = json_decode($stored);
		return is_object($params) ? $params : null;
	}

	if (!preg_match('#^(?:[aOCsidbN][:;]|R:[0-9]+;)#', $stored)) {
		return null;
	}

	if (preg_match_all('#[OC]:[0-9]+:"([-_a-zA-Z0-9]+)"[0-9]+:\{#iU', $stored, $matches)) {
		foreach ($matches[1] as $className) {
			if ($className !== 'stdClass') {
				return null;
			}
		}
	}

	$params = @unserialize($stored, array('allowed_classes' => array('stdClass')));
	return is_object($params) ? $params : null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
	akMelhorEnvioCallbackResponse(405, 'Método não permitido.');
}

$shippingId = (string) ($_GET['shipping_id'] ?? '');
$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');

if (!preg_match('/^[1-9][0-9]*$/', $shippingId)
	|| !preg_match('/^[A-Za-z0-9._~+\/-=]{20,2048}$/', $code)
	|| !preg_match('/^[a-f0-9]{64}$/', $state)) {
	akMelhorEnvioCallbackResponse(400, 'Retorno OAuth inválido.');
}

if (!function_exists('curl_init')) {
	akMelhorEnvioCallbackResponse(500, 'cURL indisponível para concluir a autorização.');
}

try {
	define('_JEXEC', 1);
	define('JPATH_BASE', dirname(__DIR__, 3));
	require_once JPATH_BASE . '/includes/defines.php';
	require_once JPATH_BASE . '/includes/framework.php';
	require_once __DIR__ . '/src/Crypto.php';
	require_once __DIR__ . '/src/Repository.php';
	require_once __DIR__ . '/src/ApiClient.php';

	$database = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
	$shippingIdInt = (int) $shippingId;
	$shippingType = 'melhorenvio';
	$query = $database->getQuery(true)
		->select($database->quoteName('shipping_params'))
		->from($database->quoteName('#__hikashop_shipping'))
		->where($database->quoteName('shipping_id') . ' = :shipping_id')
		->where($database->quoteName('shipping_type') . ' = :shipping_type')
		->bind(':shipping_id', $shippingIdInt, \Joomla\Database\ParameterType::INTEGER)
		->bind(':shipping_type', $shippingType);
	$database->setQuery($query);
	$row = $database->loadObject();
	$params = $row ? akMelhorEnvioCallbackParams((string) $row->shipping_params) : null;

	if (!$params) {
		akMelhorEnvioCallbackResponse(404, 'Método de frete Melhor Envio não encontrado.');
	}

	$repository = new \AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\Repository(
		$database,
		new \AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\Crypto((string) \Joomla\CMS\Factory::getConfig()->get('secret'))
	);

	if (!$repository->consumeOAuthState((int) $shippingId, $state)) {
		akMelhorEnvioCallbackResponse(403, 'Retorno OAuth inválido.');
	}

	$envName = preg_replace('/[^A-Z0-9_]/i', '', (string) ($params->client_secret_env ?? ''));
	$environmentSecret = $envName !== '' ? getenv($envName) : false;
	$clientSecret = $environmentSecret !== false && $environmentSecret !== ''
		? (string) $environmentSecret
		: (string) ($params->client_secret ?? '');
	$callbackUrl = 'https://www.aksolucoes.com.br/plugins/hikashopshipping/melhorenvio/oauth-callback.php?shipping_id=' . (int) $shippingId;
	$client = new \AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\ApiClient(
		$repository,
		(int) $shippingId,
		$params,
		$clientSecret
	);
	$tokens = $client->exchangeAuthorizationCode($code, $callbackUrl);
	$repository->saveTokens((int) $shippingId, (string) ($params->environment ?? 'sandbox'), $tokens);
} catch (\Throwable $exception) {
	// Do not log OAuth code, state, or client credentials.
	error_log('Melhor Envio OAuth callback failed for shipping_id ' . (int) $shippingId . ': ' . $exception->getMessage());
	akMelhorEnvioCallbackResponse(502, 'Não foi possível concluir a autorização. Tente novamente.');
}

akMelhorEnvioCallbackResponse(200, 'Método de frete autorizado com sucesso.');
