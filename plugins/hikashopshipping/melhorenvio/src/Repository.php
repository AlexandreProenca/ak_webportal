<?php

namespace AkSolucoes\Plugin\Hikashopshipping\Melhorenvio;

defined('_JEXEC') or die('Restricted access');

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class Repository
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly Crypto $crypto
	) {
	}

	public function saveTokens(int $shippingId, string $environment, array $tokens): void
	{
		$current = $this->getCredentialRow($shippingId);
		$expiresAt = gmdate('Y-m-d H:i:s', time() + max(60, (int) ($tokens['expires_in'] ?? 2592000)));
		$row = (object) array(
			'shipping_id' => $shippingId,
			'environment' => $environment === 'production' ? 'production' : 'sandbox',
			'access_token' => $this->crypto->encrypt((string) $tokens['access_token']),
			'refresh_token' => $this->crypto->encrypt((string) $tokens['refresh_token']),
			'expires_at' => $expiresAt,
			'updated_at' => gmdate('Y-m-d H:i:s'),
		);

		if ($current) {
			$this->database->updateObject('#__ak_me_credentials', $row, 'shipping_id');
		} else {
			$this->database->insertObject('#__ak_me_credentials', $row);
		}
	}

	/**
	 * Persist a short-lived, one-time OAuth state. This table already records
	 * webhook events and is available on all installed plugin versions, avoiding
	 * a dependency on shared Joomla frontend/administrator sessions.
	 */
	public function createOAuthState(int $shippingId): string
	{
		if ($shippingId < 1) {
			throw new RuntimeException('Método de frete Melhor Envio não encontrado.');
		}

		$cutoff = gmdate('Y-m-d H:i:s', time() - 900);
		$eventName = 'oauth_state';
		$cleanup = $this->database->getQuery(true)
			->delete($this->database->quoteName('#__ak_me_events'))
			->where($this->database->quoteName('event_name') . ' = :event_name')
			->where($this->database->quoteName('created_at') . ' < :cutoff')
			->bind(':event_name', $eventName)
			->bind(':cutoff', $cutoff);
		$this->database->setQuery($cleanup)->execute();

		$state = bin2hex(random_bytes(32));
		$row = (object) array(
			'event_hash' => hash('sha256', $state),
			'event_name' => 'oauth_state',
			'external_id' => (string) $shippingId,
			'payload_json' => '{}',
			'created_at' => gmdate('Y-m-d H:i:s'),
		);
		$this->database->insertObject('#__ak_me_events', $row, 'id');

		return $state;
	}

	public function consumeOAuthState(int $shippingId, string $state): bool
	{
		if ($shippingId < 1 || !preg_match('/^[a-f0-9]{64}$/', $state)) {
			return false;
		}

		$cutoff = gmdate('Y-m-d H:i:s', time() - 900);
		$stateHash = hash('sha256', $state);
		$eventName = 'oauth_state';
		$externalId = (string) $shippingId;
		$query = $this->database->getQuery(true)
			->delete($this->database->quoteName('#__ak_me_events'))
			->where($this->database->quoteName('event_hash') . ' = :event_hash')
			->where($this->database->quoteName('event_name') . ' = :event_name')
			->where($this->database->quoteName('external_id') . ' = :external_id')
			->where($this->database->quoteName('processed_at') . ' IS NULL')
			->where($this->database->quoteName('created_at') . ' >= :cutoff')
			->bind(':event_hash', $stateHash)
			->bind(':event_name', $eventName)
			->bind(':external_id', $externalId)
			->bind(':cutoff', $cutoff);
		$this->database->setQuery($query)->execute();

		return $this->database->getAffectedRows() === 1;
	}

	public function getTokens(int $shippingId): ?array
	{
		$row = $this->getCredentialRow($shippingId);

		if (!$row) {
			return null;
		}

		return array(
			'environment' => $row->environment,
			'access_token' => $this->crypto->decrypt((string) $row->access_token),
			'refresh_token' => $this->crypto->decrypt((string) $row->refresh_token),
			'expires_at' => $row->expires_at,
		);
	}

	public function upsertShipment(int $orderId, int $shippingId, int $serviceId, array $quote, string $invoiceKey): object
	{
		$existing = $this->getShipmentByOrder($orderId);
		$now = gmdate('Y-m-d H:i:s');
		$quoteJson = json_encode($quote, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		$row = (object) array(
			'order_id' => $orderId,
			'shipping_id' => $shippingId,
			'service_id' => $serviceId,
			'quote_json' => $quoteJson,
			'payload_hash' => hash('sha256', $quoteJson),
			'invoice_key' => $invoiceKey,
			'updated_at' => $now,
		);

		if ($existing) {
			$row->id = (int) $existing->id;
			$this->database->updateObject('#__ak_me_shipments', $row, 'id');
		} else {
			$row->state = 'pending';
			$row->created_at = $now;
			$this->database->insertObject('#__ak_me_shipments', $row, 'id');
		}

		return $this->getShipmentByOrder($orderId);
	}

	public function getShipmentByOrder(int $orderId): ?object
	{
		$query = $this->database->getQuery(true)
			->select('*')
			->from($this->database->quoteName('#__ak_me_shipments'))
			->where($this->database->quoteName('order_id') . ' = :order_id')
			->bind(':order_id', $orderId, ParameterType::INTEGER);
		$this->database->setQuery($query);

		return $this->database->loadObject() ?: null;
	}

	public function acquireShipment(int $shipmentId): bool
	{
		$now = gmdate('Y-m-d H:i:s');
		$stale = gmdate('Y-m-d H:i:s', time() - 600);
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__ak_me_shipments'))
			->set($this->database->quoteName('locked_at') . ' = :now')
			->where($this->database->quoteName('id') . ' = :id')
			->where('(' . $this->database->quoteName('locked_at') . ' IS NULL OR ' . $this->database->quoteName('locked_at') . ' < :stale)')
			->bind(':now', $now)
			->bind(':stale', $stale)
			->bind(':id', $shipmentId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		return $this->database->getAffectedRows() === 1;
	}

	public function releaseShipment(int $shipmentId): void
	{
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__ak_me_shipments'))
			->set($this->database->quoteName('locked_at') . ' = NULL')
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $shipmentId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
	}

	public function setShipmentState(int $shipmentId, string $state, ?string $error): void
	{
		$row = (object) array(
			'id' => $shipmentId,
			'state' => mb_substr($state, 0, 32),
			'last_error' => $error,
			'updated_at' => gmdate('Y-m-d H:i:s'),
		);
		$this->database->updateObject('#__ak_me_shipments', $row, 'id');
	}

	public function addLabel(int $shipmentId, string $externalId, string $status, int $packageIndex = 0): void
	{
		$row = (object) array(
			'shipment_id' => $shipmentId,
			'package_index' => $packageIndex,
			'external_id' => mb_substr($externalId, 0, 64),
			'status' => mb_substr($status, 0, 32),
			'created_at' => gmdate('Y-m-d H:i:s'),
			'updated_at' => gmdate('Y-m-d H:i:s'),
		);
		$this->database->insertObject('#__ak_me_labels', $row, 'id');
	}

	public function getLabels(int $shipmentId): array
	{
		$query = $this->database->getQuery(true)
			->select('*')
			->from($this->database->quoteName('#__ak_me_labels'))
			->where($this->database->quoteName('shipment_id') . ' = :shipment_id')
			->order($this->database->quoteName('id') . ' ASC')
			->bind(':shipment_id', $shipmentId, ParameterType::INTEGER);
		$this->database->setQuery($query);

		return $this->database->loadObjectList() ?: array();
	}

	public function getLabelForOrder(int $orderId, string $externalId): ?object
	{
		$query = $this->database->getQuery(true)
			->select('l.*')
			->from($this->database->quoteName('#__ak_me_labels', 'l'))
			->join('INNER', $this->database->quoteName('#__ak_me_shipments', 's') . ' ON s.id = l.shipment_id')
			->where('s.order_id = :order_id')
			->where('l.external_id = :external_id')
			->bind(':order_id', $orderId, ParameterType::INTEGER)
			->bind(':external_id', $externalId);
		$this->database->setQuery($query);

		return $this->database->loadObject() ?: null;
	}

	public function getOrderIdByExternalId(string $externalId): int
	{
		$query = $this->database->getQuery(true)
			->select('s.order_id')
			->from($this->database->quoteName('#__ak_me_labels', 'l'))
			->join('INNER', $this->database->quoteName('#__ak_me_shipments', 's') . ' ON s.id = l.shipment_id')
			->where('l.external_id = :external_id')
			->bind(':external_id', $externalId);
		$this->database->setQuery($query);

		return (int) $this->database->loadResult();
	}

	public function setLabelsState(array $externalIds, string $state): void
	{
		foreach ($externalIds as $externalId) {
			$this->updateLabel((string) $externalId, array('status' => $state));
		}
	}

	public function updateTracking(string $externalId, string $status, string $tracking, string $trackingUrl): void
	{
		$values = array();

		if ($status !== '') {
			$values['status'] = $status;
		}
		if ($tracking !== '') {
			$values['tracking'] = $tracking;
		}
		if ($trackingUrl !== '') {
			$values['tracking_url'] = $trackingUrl;
		}

		if ($values) {
			$this->updateLabel($externalId, $values);
		}
	}

	public function recordEvent(string $hash, string $eventName, string $externalId, string $raw): bool
	{
		$row = (object) array(
			'event_hash' => $hash,
			'event_name' => mb_substr($eventName, 0, 64),
			'external_id' => mb_substr($externalId, 0, 64),
			'payload_json' => $raw,
			'created_at' => gmdate('Y-m-d H:i:s'),
		);

		try {
			$this->database->insertObject('#__ak_me_events', $row, 'id');
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	public function applyWebhook(string $externalId, string $status, string $tracking, string $trackingUrl): void
	{
		$this->updateTracking($externalId, $status, $tracking, $trackingUrl);
	}

	public function markEventProcessed(string $hash): void
	{
		$processedAt = gmdate('Y-m-d H:i:s');
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__ak_me_events'))
			->set($this->database->quoteName('processed_at') . ' = :processed_at')
			->where($this->database->quoteName('event_hash') . ' = :event_hash')
			->bind(':processed_at', $processedAt)
			->bind(':event_hash', $hash);
		$this->database->setQuery($query)->execute();
	}

	private function getCredentialRow(int $shippingId): ?object
	{
		$query = $this->database->getQuery(true)
			->select('*')
			->from($this->database->quoteName('#__ak_me_credentials'))
			->where($this->database->quoteName('shipping_id') . ' = :shipping_id')
			->bind(':shipping_id', $shippingId, ParameterType::INTEGER);
		$this->database->setQuery($query);

		return $this->database->loadObject() ?: null;
	}

	private function updateLabel(string $externalId, array $values): void
	{
		$allowed = array('status', 'tracking', 'tracking_url');
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__ak_me_labels'));
		$bindings = array();

		foreach ($values as $column => $value) {
			if (!in_array($column, $allowed, true)) {
				continue;
			}

			$placeholder = ':' . $column;
			$query->set($this->database->quoteName($column) . ' = ' . $placeholder);
			$bindings[$placeholder] = mb_substr((string) $value, 0, $column === 'tracking_url' ? 1024 : 128);
		}

		$updatedAt = gmdate('Y-m-d H:i:s');
		$query->set($this->database->quoteName('updated_at') . ' = :updated_at')
			->where($this->database->quoteName('external_id') . ' = :external_id')
			->bind(':updated_at', $updatedAt)
			->bind(':external_id', $externalId);

		foreach ($bindings as $placeholder => $value) {
			$query->bind($placeholder, $value);
		}

		$this->database->setQuery($query)->execute();
	}
}
