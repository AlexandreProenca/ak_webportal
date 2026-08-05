<?php

namespace AkSolucoes\Plugin\Hikashopshipping\Melhorenvio;

defined('_JEXEC') or die('Restricted access');

use RuntimeException;

final class Crypto
{
	private string $key;

	public function __construct(string $siteSecret)
	{
		if ($siteSecret === '') {
			throw new RuntimeException('O secret do Joomla não está configurado.');
		}

		$this->key = hash('sha256', $siteSecret . '|ak-melhor-envio-v1', true);
	}

	public function encrypt(string $plainText): string
	{
		if ($plainText === '') {
			return '';
		}

		if (function_exists('sodium_crypto_secretbox')) {
			$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$cipherText = sodium_crypto_secretbox($plainText, $nonce, $this->key);

			return 's1:' . base64_encode($nonce . $cipherText);
		}

		$iv = random_bytes(12);
		$tag = '';
		$cipherText = openssl_encrypt($plainText, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

		if ($cipherText === false) {
			throw new RuntimeException('Não foi possível proteger a credencial.');
		}

		return 'o1:' . base64_encode($iv . $tag . $cipherText);
	}

	public function decrypt(string $encoded): string
	{
		if ($encoded === '') {
			return '';
		}

		[$version, $payload] = array_pad(explode(':', $encoded, 2), 2, '');
		$binary = base64_decode($payload, true);

		if ($binary === false) {
			throw new RuntimeException('Credencial armazenada inválida.');
		}

		if ($version === 's1' && function_exists('sodium_crypto_secretbox_open')) {
			$nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
			$plainText = sodium_crypto_secretbox_open(substr($binary, $nonceSize), substr($binary, 0, $nonceSize), $this->key);
		} elseif ($version === 'o1') {
			$iv = substr($binary, 0, 12);
			$tag = substr($binary, 12, 16);
			$plainText = openssl_decrypt(substr($binary, 28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
		} else {
			throw new RuntimeException('Formato de credencial não suportado.');
		}

		if ($plainText === false) {
			throw new RuntimeException('Não foi possível abrir a credencial armazenada.');
		}

		return $plainText;
	}
}
