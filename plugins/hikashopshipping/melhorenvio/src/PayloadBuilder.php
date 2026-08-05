<?php

namespace AkSolucoes\Plugin\Hikashopshipping\Melhorenvio;

defined('_JEXEC') or die('Restricted access');

use InvalidArgumentException;

final class PayloadBuilder
{
	public function __construct(private readonly object $params)
	{
	}

	public function buildQuote(object $order): array
	{
		$from = $this->digits((string) ($this->params->sender_postal_code ?? ''));
		$to = $this->digits((string) ($order->shipping_address->address_post_code ?? ''));

		if (strlen($from) !== 8 || strlen($to) !== 8) {
			throw new InvalidArgumentException('CEP de origem ou destino inválido.');
		}

		$products = $this->products($order);
		$payload = array(
			'from' => array('postal_code' => $from),
			'to' => array('postal_code' => $to),
			'products' => $products,
			'options' => array(
				'receipt' => !empty($this->params->receipt),
				'own_hand' => !empty($this->params->own_hand),
			),
		);
		$services = $this->csv($this->params->services ?? '');

		if ($services) {
			$payload['services'] = implode(',', $services);
		}

		return $payload;
	}

	public function snapshot(array $payload, array $quote): array
	{
		$safeQuote = array_intersect_key($quote, array_flip(array(
			'id', 'name', 'price', 'custom_price', 'discount', 'currency',
			'delivery_time', 'custom_delivery_time', 'company', 'packages',
		)));

		return array(
			'service_id' => (int) $quote['id'],
			'price' => (float) ($quote['custom_price'] ?? $quote['price'] ?? 0),
			'delivery_time' => (int) ($quote['custom_delivery_time'] ?? $quote['delivery_time'] ?? 0),
			'company' => (string) ($quote['company']['name'] ?? ''),
			'quote' => $safeQuote,
			'products' => $payload['products'],
			'from_postal_code' => $payload['from']['postal_code'],
			'to_postal_code' => $payload['to']['postal_code'],
			'payload_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
			'quoted_at' => time(),
		);
	}

	public function buildCartPayloads(object $order, array $snapshot, string $invoiceKey): array
	{
		$this->validateCommercialSender();

		if (strlen($invoiceKey) !== 44) {
			throw new InvalidArgumentException('Chave NF-e inválida.');
		}

		$serviceId = (int) ($snapshot['service_id'] ?? 0);

		if ($serviceId < 1) {
			throw new InvalidArgumentException('Serviço de frete inválido.');
		}

		$products = $this->cartProducts($order);
		$volumes = $this->volumes($snapshot, $products);
		$base = array(
			'service' => $serviceId,
			'from' => $this->sender(),
			'to' => $this->recipient($order),
			'products' => $products,
			'options' => array(
				'insurance_value' => round($this->productsValue($products), 2),
				'receipt' => !empty($this->params->receipt),
				'own_hand' => !empty($this->params->own_hand),
				'reverse' => false,
				'non_commercial' => false,
				'invoice' => array('key' => $invoiceKey),
				'platform' => 'HikaShop / AK Soluções',
				'tags' => array(array(
					'tag' => (string) ($order->order_number ?? $order->order_id),
					'url' => null,
				)),
			),
		);

		$carrier = strtolower((string) ($snapshot['company'] ?? ''));
		$split = count($volumes) > 1 && preg_match('/correios|j&t|jet|loggi/', $carrier);

		if ($split) {
			return array_map(static function (array $volume) use ($base): array {
				$payload = $base;
				$payload['volumes'] = array($volume);
				return $payload;
			}, $volumes);
		}

		$base['volumes'] = $volumes;
		return array($base);
	}

	private function products(object $order): array
	{
		$products = array();

		foreach ((array) $order->products as $index => $product) {
			$quantity = max(1, (int) ($product->cart_product_quantity ?? $product->order_product_quantity ?? 1));
			$length = $this->lengthToCm((float) ($product->product_length ?? 0), (string) ($product->product_dimension_unit ?? 'cm'));
			$width = $this->lengthToCm((float) ($product->product_width ?? 0), (string) ($product->product_dimension_unit ?? 'cm'));
			$height = $this->lengthToCm((float) ($product->product_height ?? 0), (string) ($product->product_dimension_unit ?? 'cm'));
			$weight = $this->weightToKg((float) ($product->product_weight ?? 0), (string) ($product->product_weight_unit ?? 'kg'));

			if (min($length, $width, $height, $weight) <= 0) {
				throw new InvalidArgumentException('Todos os produtos físicos precisam de peso e dimensões válidos.');
			}

			$unitPrice = $this->unitPrice($product);

			if ($unitPrice <= 0) {
				throw new InvalidArgumentException('Todos os produtos comerciais precisam de valor unitário válido.');
			}

			$products[] = array(
				'id' => (string) ($product->product_code ?? $product->product_id ?? $index + 1),
				'width' => round($width, 2),
				'height' => round($height, 2),
				'length' => round($length, 2),
				'weight' => round($weight, 3),
				'insurance_value' => round($unitPrice, 2),
				'quantity' => $quantity,
			);
		}

		if (!$products) {
			throw new InvalidArgumentException('O pedido não possui produtos físicos para cotação.');
		}

		return $products;
	}

	private function cartProducts(object $order): array
	{
		$products = array();

		foreach ((array) ($order->products ?? array()) as $index => $product) {
			$unitPrice = $this->unitPrice($product);

			if ($unitPrice <= 0) {
				throw new InvalidArgumentException('Produto comercial sem valor unitário válido.');
			}

			$products[] = array(
				'name' => mb_substr((string) ($product->order_product_name ?? $product->product_name ?? 'Produto'), 0, 255),
				'quantity' => max(1, (int) ($product->order_product_quantity ?? $product->cart_product_quantity ?? 1)),
				'unitary_value' => round($unitPrice, 2),
			);
		}

		if (!$products) {
			throw new InvalidArgumentException('Produtos do pedido não encontrados.');
		}

		return $products;
	}

	private function volumes(array $snapshot, array $products): array
	{
		$packages = $snapshot['quote']['packages'] ?? array();
		$volumes = array();

		foreach ((array) $packages as $package) {
			$dimensions = $package['dimensions'] ?? $package;
			$volume = array(
				'height' => (float) ($dimensions['height'] ?? 0),
				'width' => (float) ($dimensions['width'] ?? 0),
				'length' => (float) ($dimensions['length'] ?? 0),
				'weight' => (float) ($package['weight'] ?? 0),
			);

			if (min($volume) > 0) {
				$volumes[] = $volume;
			}
		}

		if (!$volumes) {
			$requestProducts = (array) ($snapshot['products'] ?? array());
			$height = $width = $length = $weight = 0.0;

			foreach ($requestProducts as $product) {
				$height = max($height, (float) ($product['height'] ?? 0));
				$width = max($width, (float) ($product['width'] ?? 0));
				$length = max($length, (float) ($product['length'] ?? 0));
				$weight += (float) ($product['weight'] ?? 0) * max(1, (int) ($product['quantity'] ?? 1));
			}

			if (min($height, $width, $length, $weight) <= 0) {
				throw new InvalidArgumentException('A cotação não contém volumes válidos. Faça uma nova cotação.');
			}

			$volumes[] = compact('height', 'width', 'length', 'weight');
		}

		return $volumes;
	}

	private function sender(): array
	{
		return array_filter(array(
			'name' => (string) (($this->params->sender_company ?? '') ?: $this->params->sender_name),
			'company_document' => $this->digits((string) $this->params->sender_cnpj),
			'state_register' => (string) $this->params->sender_ie,
			'cnae' => $this->digits((string) ($this->params->sender_cnae ?? '')),
			'email' => (string) $this->params->sender_email,
			'phone' => $this->digits((string) $this->params->sender_phone),
			'address' => (string) $this->params->sender_address,
			'complement' => (string) ($this->params->sender_complement ?? ''),
			'number' => (string) $this->params->sender_number,
			'district' => (string) $this->params->sender_district,
			'city' => (string) $this->params->sender_city,
			'state_abbr' => strtoupper((string) $this->params->sender_state),
			'country_id' => 'BR',
			'postal_code' => $this->digits((string) $this->params->sender_postal_code),
		), static fn($value) => $value !== '');
	}

	private function recipient(object $order): array
	{
		$address = $order->shipping_address ?? null;

		if (!$address) {
			throw new InvalidArgumentException('Endereço de entrega não encontrado.');
		}

		$documentField = $this->fieldName($this->params->document_field ?? 'address_vat');
		$numberField = $this->fieldName($this->params->number_field ?? 'address_street2');
		$districtField = $this->fieldName($this->params->district_field ?? 'address_neighborhood');
		$document = $this->digits((string) ($address->{$documentField} ?? ''));
		$recipient = array(
			'name' => trim((string) ($address->address_firstname ?? '') . ' ' . (string) ($address->address_lastname ?? '')),
			'email' => (string) ($address->address_email ?? $order->customer->user_email ?? ''),
			'phone' => $this->digits((string) ($address->address_telephone ?? $address->address_telephone2 ?? '')),
			'address' => (string) ($address->address_street ?? ''),
			'complement' => (string) ($address->address_complement ?? ''),
			'number' => (string) ($address->{$numberField} ?? ''),
			'district' => (string) ($address->{$districtField} ?? ''),
			'city' => (string) ($address->address_city ?? ''),
			'state_abbr' => strtoupper((string) ($address->address_state_code_2 ?? $address->address_state_code ?? '')),
			'country_id' => strtoupper((string) ($address->address_country_code_2 ?? 'BR')),
			'postal_code' => $this->digits((string) ($address->address_post_code ?? '')),
		);
		$recipient[strlen($document) === 14 ? 'company_document' : 'document'] = $document;

		foreach (array('name', 'phone', 'address', 'number', 'district', 'city', 'state_abbr', 'postal_code') as $required) {
			if ($recipient[$required] === '') {
				throw new InvalidArgumentException('Campo obrigatório ausente no destinatário: ' . $required . '.');
			}
		}

		if (!in_array(strlen($document), array(11, 14), true)) {
			throw new InvalidArgumentException('CPF/CNPJ do destinatário inválido.');
		}

		return array_filter($recipient, static fn($value) => $value !== '');
	}

	private function validateCommercialSender(): void
	{
		$required = array(
			'sender_name', 'sender_email', 'sender_phone', 'sender_cnpj', 'sender_ie',
			'sender_address', 'sender_number', 'sender_district', 'sender_city',
			'sender_state', 'sender_postal_code',
		);

		foreach ($required as $field) {
			if (trim((string) ($this->params->{$field} ?? '')) === '') {
				throw new InvalidArgumentException('Configuração fiscal/remetente incompleta: ' . $field . '.');
			}
		}

		if (strlen($this->digits((string) $this->params->sender_cnpj)) !== 14) {
			throw new InvalidArgumentException('CNPJ do remetente inválido.');
		}
	}

	private function productsValue(array $products): float
	{
		$total = 0.0;
		foreach ($products as $product) {
			$total += (float) $product['unitary_value'] * (int) $product['quantity'];
		}

		return $total;
	}

	private function unitPrice(object $product): float
	{
		if (!empty($product->prices) && is_array($product->prices)) {
			$price = reset($product->prices);

			foreach (array('price_value_with_tax', 'price_value') as $field) {
				if (isset($price->{$field}) && is_numeric($price->{$field}) && (float) $price->{$field} > 0) {
					return (float) $price->{$field};
				}
			}
		}

		foreach (array('order_product_price', 'product_price_value', 'product_price') as $field) {
			if (isset($product->{$field}) && is_numeric($product->{$field})) {
				return (float) $product->{$field};
			}
		}

		return 0.0;
	}

	private function lengthToCm(float $value, string $unit): float
	{
		return match (strtolower($unit)) {
			'm' => $value * 100,
			'mm' => $value / 10,
			'in', 'inch' => $value * 2.54,
			'ft' => $value * 30.48,
			default => $value,
		};
	}

	private function weightToKg(float $value, string $unit): float
	{
		return match (strtolower($unit)) {
			'g' => $value / 1000,
			'mg' => $value / 1000000,
			'lb', 'lbs' => $value * 0.45359237,
			'oz' => $value * 0.0283495,
			default => $value,
		};
	}

	private function fieldName(string $value): string
	{
		return preg_replace('/[^a-zA-Z0-9_]/', '', $value) ?: 'invalid';
	}

	private function digits(string $value): string
	{
		return preg_replace('/\D+/', '', $value);
	}

	private function csv($value): array
	{
		return array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
	}
}
