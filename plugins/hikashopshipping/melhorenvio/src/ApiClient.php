<?php

namespace AkSolucoes\Plugin\Hikashopshipping\Melhorenvio;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Http\HttpFactory;
use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
	public function __construct(private readonly string $publicMessage, int $status = 0)
	{
		parent::__construct($publicMessage, $status);
	}

	public function getPublicMessage(): string
	{
		return $this->publicMessage;
	}
}

final class ApiClient
{
	private const SCOPES = 'cart-read cart-write orders-read shipping-calculate shipping-checkout shipping-generate shipping-print shipping-tracking shipping-cancel ecommerce-shipping';

	private string $baseUrl;

	public function __construct(
		private readonly Repository $repository,
		private readonly int $shippingId,
		private readonly object $params,
		private readonly string $clientSecret
	) {
		$this->baseUrl = ($params->environment ?? 'sandbox') === 'production'
			? 'https://melhorenvio.com.br'
			: 'https://sandbox.melhorenvio.com.br';
	}

	public function authorizationUrl(string $redirectUri, string $state): string
	{
		$this->assertApplicationCredentials();

		return $this->baseUrl . '/oauth/authorize?' . http_build_query(array(
			'client_id' => (string) $this->params->client_id,
			'redirect_uri' => $redirectUri,
			'response_type' => 'code',
			'scope' => self::SCOPES,
			'state' => $state,
		), '', '&', PHP_QUERY_RFC3986);
	}

	public function exchangeAuthorizationCode(string $code, string $redirectUri): array
	{
		$this->assertApplicationCredentials();

		return $this->tokenRequest(array(
			'grant_type' => 'authorization_code',
			'client_id' => (string) $this->params->client_id,
			'client_secret' => $this->clientSecret,
			'redirect_uri' => $redirectUri,
			'code' => $code,
		));
	}

	public function request(string $method, string $path, ?array $payload = null, bool $retry = true): array
	{
		$data = $this->requestValue($method, $path, $payload, $retry);

		if (!is_array($data)) {
			throw new ApiException('Resposta inválida recebida do Melhor Envio.');
		}

		return $data;
	}

	public function requestValue(string $method, string $path, ?array $payload = null, bool $retry = true): mixed
	{
		$token = $this->validAccessToken();
		$headers = $this->headers($token);

		try {
			$response = $this->send($method, $this->baseUrl . $path, $headers, $payload);
		} catch (Throwable $e) {
			throw new ApiException('Falha de comunicação com o Melhor Envio. Tente novamente.', 0);
		}

		if ((int) $response->code === 401 && $retry) {
			$this->refreshAccessToken();
			return $this->requestValue($method, $path, $payload, false);
		}

		return $this->decodeResponse($response);
	}

	private function validAccessToken(): string
	{
		$credential = $this->repository->getTokens($this->shippingId);

		if (!$credential) {
			throw new ApiException('Autorize o método de frete no Melhor Envio antes de usá-lo.', 401);
		}

		if (!empty($credential['expires_at']) && strtotime($credential['expires_at']) <= time() + 86400) {
			$credential = $this->refreshAccessToken();
		}

		return (string) $credential['access_token'];
	}

	private function refreshAccessToken(): array
	{
		$this->assertApplicationCredentials();
		$current = $this->repository->getTokens($this->shippingId);

		if (!$current || empty($current['refresh_token'])) {
			throw new ApiException('A autorização expirou. Autorize novamente o Melhor Envio.', 401);
		}

		$tokens = $this->tokenRequest(array(
			'grant_type' => 'refresh_token',
			'client_id' => (string) $this->params->client_id,
			'client_secret' => $this->clientSecret,
			'refresh_token' => $current['refresh_token'],
		));
		$this->repository->saveTokens($this->shippingId, (string) ($this->params->environment ?? 'sandbox'), $tokens);

		return $this->repository->getTokens($this->shippingId);
	}

	private function tokenRequest(array $payload): array
	{
		try {
			$response = $this->send('POST', $this->baseUrl . '/oauth/token', $this->headers(), $payload);
		} catch (Throwable $e) {
			throw new ApiException('Não foi possível concluir a autorização no Melhor Envio.');
		}

		$data = $this->decodeResponse($response);

		if (empty($data['access_token']) || empty($data['refresh_token'])) {
			throw new ApiException('O Melhor Envio não devolveu credenciais OAuth válidas.');
		}

		return $data;
	}

	private function send(string $method, string $url, array $headers, ?array $payload)
	{
		$http = HttpFactory::getHttp();
		$body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

		return match (strtoupper($method)) {
			'GET' => $http->get($url, $headers, 20),
			'POST' => $http->post($url, $body, $headers, 20),
			'PUT' => $http->put($url, $body, $headers, 20),
			'PATCH' => $http->patch($url, $body, $headers, 20),
			'DELETE' => $http->delete($url, $headers, 20, $body),
			default => throw new ApiException('Método HTTP não suportado.'),
		};
	}

	private function decodeResponse($response): mixed
	{
		$data = json_decode((string) $response->body, true);

		if ((int) $response->code < 200 || (int) $response->code >= 300) {
			$message = (string) ($data['message'] ?? $data['error'] ?? 'Requisição recusada pelo Melhor Envio.');
			throw new ApiException(mb_substr($message, 0, 500), (int) $response->code);
		}

		if ($data === null && trim((string) $response->body) !== 'null') {
			throw new ApiException('Resposta inválida recebida do Melhor Envio.');
		}

		return $data;
	}

	private function headers(string $accessToken = ''): array
	{
		$email = filter_var((string) ($this->params->support_email ?? ''), FILTER_VALIDATE_EMAIL)
			? (string) $this->params->support_email
			: 'suporte@aksolucoes.com.br';
		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'User-Agent' => 'AK Solucoes HikaShop (' . $email . ')',
		);

		if ($accessToken !== '') {
			$headers['Authorization'] = 'Bearer ' . $accessToken;
		}

		return $headers;
	}

	private function assertApplicationCredentials(): void
	{
		if (empty($this->params->client_id) || $this->clientSecret === '') {
			throw new ApiException('Configure Client ID e Client Secret do aplicativo Melhor Envio.');
		}
	}
}
