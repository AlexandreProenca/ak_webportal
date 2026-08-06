<?php
/**
 * HikaShop shipping integration for Melhor Envio.
 *
 * @package     AKSolucoes.Plugin
 * @subpackage  Hikashopshipping.Melhorenvio
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\ApiClient;
use AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\ApiException;
use AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\Crypto;
use AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\PayloadBuilder;
use AkSolucoes\Plugin\Hikashopshipping\Melhorenvio\Repository;

require_once __DIR__ . '/src/Crypto.php';
require_once __DIR__ . '/src/Repository.php';
require_once __DIR__ . '/src/ApiClient.php';
require_once __DIR__ . '/src/PayloadBuilder.php';

class plgHikashopshippingMelhorenvio extends hikashopShippingPlugin
{
	public $multiple = true;
	public $name = 'melhorenvio';
	public $use_cache = true;

	/**
	 * HikaShop renders these labels through Joomla's language service. Load the
	 * plugin language explicitly because HikaShop imports legacy shipping
	 * plugins outside Joomla's normal event bootstrap.
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_hikashopshipping_melhorenvio', JPATH_ADMINISTRATOR);
	}

	public $pluginConfig = array(
		'environment' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_ENVIRONMENT', 'list', array(
			'sandbox' => 'PLG_HIKASHOPSHIPPING_MELHORENVIO_SANDBOX',
			'production' => 'PLG_HIKASHOPSHIPPING_MELHORENVIO_PRODUCTION',
		)),
		'client_id' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_CLIENT_ID', 'input'),
		'client_secret' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_CLIENT_SECRET', 'input'),
		'client_secret_env' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_CLIENT_SECRET_ENV', 'input'),
		'oauth_authorize' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_OAUTH_AUTHORIZE', 'oauth_authorize'),
		'support_email' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SUPPORT_EMAIL', 'input'),
		'services' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SERVICES', 'input'),
		'trigger_statuses' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_TRIGGER_STATUSES', 'input'),
		'invoice_field' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_INVOICE_FIELD', 'input'),
		'document_field' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_DOCUMENT_FIELD', 'input'),
		'number_field' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_NUMBER_FIELD', 'input'),
		'district_field' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_DISTRICT_FIELD', 'input'),
		'sender_name' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_NAME', 'input'),
		'sender_company' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_COMPANY', 'input'),
		'sender_email' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_EMAIL', 'input'),
		'sender_phone' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_PHONE', 'input'),
		'sender_cnpj' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_CNPJ', 'input'),
		'sender_ie' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_IE', 'input'),
		'sender_cnae' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_CNAE', 'input'),
		'sender_address' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_ADDRESS', 'input'),
		'sender_number' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_NUMBER', 'input'),
		'sender_complement' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_COMPLEMENT', 'input'),
		'sender_district' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_DISTRICT', 'input'),
		'sender_city' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_CITY', 'input'),
		'sender_state' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_STATE', 'input'),
		'sender_postal_code' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_SENDER_POSTAL_CODE', 'input'),
		'own_hand' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_OWN_HAND', 'boolean', 0),
		'receipt' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_RECEIPT', 'boolean', 0),
		'additional_days' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_ADDITIONAL_DAYS', 'input', 0),
		'additional_price' => array('PLG_HIKASHOPSHIPPING_MELHORENVIO_ADDITIONAL_PRICE', 'input', 0),
		'debug' => array('DEBUG', 'boolean', 0),
	);

	public function getShippingDefaultValues(&$element)
	{
		$element->shipping_name = 'Melhor Envio';
		$element->shipping_description = 'Frete calculado em tempo real.';
		$element->shipping_params->environment = 'sandbox';
		$element->shipping_params->trigger_statuses = 'confirmed';
		$element->shipping_params->invoice_field = 'order_nf_key';
		$element->shipping_params->document_field = 'address_vat';
		$element->shipping_params->number_field = 'address_street2';
		$element->shipping_params->district_field = 'address_neighborhood';
		$element->shipping_params->additional_days = 0;
		$element->shipping_params->additional_price = 0;
	}

	/**
	 * Starts OAuth from the administrator application. Joomla is configured with
	 * separate frontend and administrator sessions, so this cannot rely on the
	 * public com_ajax authorization endpoint being authenticated.
	 */
	public function onShippingConfiguration(&$element)
	{
		$app = Factory::getApplication();

		if ($app->input->getCmd('melhorenvio_action') === 'authorize') {
			$this->assertAdministrator();
			Session::checkToken('get') or throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);

			$shippingId = $app->input->getInt('shipping_id');
			if ($shippingId < 1 || $shippingId !== (int) ($element->shipping_id ?? 0)) {
				throw new RuntimeException('Método de frete Melhor Envio não encontrado.', 404);
			}

			$app->redirect($this->authorizationUrl($shippingId));
			return;
		}

		parent::onShippingConfiguration($element);
	}

	public function pluginConfigDisplay($fieldType, $data, $type, $paramsType, $key, $element)
	{
		if ($fieldType !== 'oauth_authorize') {
			return '';
		}

		$shippingId = (int) ($element->shipping_id ?? 0);
		if ($shippingId < 1) {
			return '<p>' . htmlspecialchars(Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_OAUTH_SAVE_FIRST'), ENT_QUOTES, 'UTF-8') . '</p>';
		}

		$url = Uri::base() . 'index.php?' . http_build_query(array(
			'option' => 'com_hikashop',
			'ctrl' => 'plugins',
			'plugin_type' => 'shipping',
			'task' => 'edit',
			'name' => $this->name,
			'subtask' => 'shipping_edit',
			'shipping_id' => $shippingId,
			'melhorenvio_action' => 'authorize',
			Session::getFormToken() => '1',
		));

		return '<a class="btn btn-primary" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
			. htmlspecialchars(Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_OAUTH_AUTHORIZE_BUTTON'), ENT_QUOTES, 'UTF-8')
			. '</a><p class="small text-muted">'
			. htmlspecialchars(Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_OAUTH_AUTHORIZE_DESC'), ENT_QUOTES, 'UTF-8')
			. '</p>';
	}

	public function shippingMethods(&$main)
	{
		$methods = array();
		foreach ($this->csv($main->shipping_params->services ?? '') as $serviceId) {
			$methods[$main->shipping_id . '-me' . $serviceId] = Text::sprintf(
				'PLG_HIKASHOPSHIPPING_MELHORENVIO_SERVICE',
				$serviceId
			);
		}

		return $methods;
	}

	public function onShippingDisplay(&$order, &$dbrates, &$usable_rates, &$messages)
	{
		if (empty($order->shipping_address) || empty($order->products)) {
			return true;
		}

		if ($this->loadShippingCache($order, $usable_rates, $messages)) {
			return true;
		}

		$localRates = array();
		$localMessages = array();

		if (parent::onShippingDisplay($order, $dbrates, $localRates, $localMessages) === false) {
			return false;
		}

		$cacheRates = array();
		$cacheMessages = array();

		foreach ($localRates as $rate) {
			try {
				$builder = new PayloadBuilder($rate->shipping_params);
				$payload = $builder->buildQuote($order);
				$client = $this->client((int) $rate->shipping_id, $rate->shipping_params);
				$quotes = $client->request('POST', '/api/v2/me/shipment/calculate', $payload);
				$allowed = $this->csv($rate->shipping_params->services ?? '');

				foreach ($quotes as $quote) {
					if (!empty($quote['error']) || empty($quote['id'])) {
						continue;
					}

					$serviceId = (string) $quote['id'];

					if ($allowed && !in_array($serviceId, $allowed, true)) {
						continue;
					}

					$key = (int) $rate->shipping_id . '-me' . $serviceId;
					$quote['custom_price'] = max(0, (float) ($quote['custom_price'] ?? $quote['price'] ?? 0)
						+ (float) ($rate->shipping_params->additional_price ?? 0));
					$quote['custom_delivery_time'] = max(0, (int) ($quote['custom_delivery_time'] ?? $quote['delivery_time'] ?? 0)
						+ (int) ($rate->shipping_params->additional_days ?? 0));
					$newRate = clone $rate;
					$newRate->shipping_id = $key;
					$newRate->shipping_price = (float) ($quote['custom_price'] ?? $quote['price'] ?? 0);
					$newRate->shipping_name = trim(($quote['company']['name'] ?? 'Melhor Envio') . ' - ' . ($quote['name'] ?? $serviceId));
					$days = (int) ($quote['custom_delivery_time'] ?? $quote['delivery_time'] ?? 0);
					$newRate->shipping_description = $days > 0
						? Text::plural('PLG_HIKASHOPSHIPPING_MELHORENVIO_DELIVERY_DAYS', $days)
						: '';
					$usable_rates[$key] = $newRate;
					$cacheRates[$key] = $newRate;
					$this->rememberQuote($key, $builder->snapshot($payload, $quote));
				}
			} catch (Throwable $e) {
				$this->logSafe('Cotação indisponível', $e, $rate->shipping_params ?? null);
				$cacheMessages['melhorenvio_' . (int) $rate->shipping_id] = Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_QUOTE_ERROR');
			}
		}

		$this->setShippingCache($order, $cacheRates, $cacheMessages);

		foreach ($localMessages + $cacheMessages as $key => $message) {
			$messages[$key] = $message;
		}

		return true;
	}

	public function onShippingSave(&$cart, &$rates, &$shipping_id, $warehouse_id = null)
	{
		$ids = is_array($shipping_id) ? $shipping_id : array($shipping_id);

		foreach ($ids as $id) {
			if (preg_match('/^\d+-me\d+$/', (string) $id) && !$this->getRememberedQuote((string) $id)) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_QUOTE_EXPIRED'), 'warning');
				return false;
			}
		}

		return true;
	}

	public function onBeforeOrderCreate(&$order, &$do)
	{
		foreach ($this->selectedShippingIds($order) as $id) {
			if (!preg_match('/^(\d+)-me(\d+)$/', $id, $matches)) {
				continue;
			}

			$snapshot = $this->getRememberedQuote($id);

			if (!$snapshot) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_QUOTE_EXPIRED'), 'error');
				$do = false;
				return;
			}

			if (empty($order->order_shipping_params) || !is_object($order->order_shipping_params)) {
				$order->order_shipping_params = new stdClass();
			}

			$order->order_shipping_params->melhorenvio = array(
				'method_id' => (int) $matches[1],
				'service_id' => (int) $matches[2],
				'quote' => $snapshot,
			);
			return;
		}
	}

	public function onAfterOrderCreate(&$order, &$send_email)
	{
		$this->maybeProcessOrder($order);
	}

	public function onAfterOrderUpdate(&$order, &$send_email)
	{
		$this->maybeProcessOrder($order);
	}

	/**
	 * com_ajax endpoint. OAuth callback and webhook are public; operational
	 * actions require an authenticated administrator and a Joomla CSRF token.
	 */
	public function onAjaxMelhorenvio($event = null)
	{
		$app = Factory::getApplication();
		$action = $app->input->getCmd('action');
		$shippingId = $app->input->getInt('shipping_id');

		try {
			if ($action === 'webhook') {
				return $this->handleWebhook($shippingId);
			}

			if ($action === 'callback') {
				return $this->handleOAuthCallback($shippingId);
			}

			$this->assertAdministrator();

			if ($action === 'authorize') {
				Session::checkToken('get') or throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
				return array('authorization_url' => $this->authorizationUrl($shippingId));
			}

			Session::checkToken('post') or throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);

			if ($action === 'process') {
				$orderId = $app->input->post->getInt('order_id');
				$order = hikashop_get('class.order')->loadFullOrder($orderId, true, false);
				$this->processOrder($order, true);
				return array('ok' => true, 'shipment' => $this->repository()->getShipmentByOrder($orderId));
			}

			if ($action === 'tracking') {
				return $this->refreshTracking($app->input->post->getInt('order_id'));
			}

			if ($action === 'label') {
				return $this->labelUrl(
					$app->input->post->getInt('order_id'),
					$app->input->post->getString('label_id'),
					$app->input->post->getCmd('format', 'pdf')
				);
			}

			if ($action === 'cancel') {
				return $this->cancelLabel(
					$app->input->post->getInt('order_id'),
					$app->input->post->getString('label_id'),
					$app->input->post->getString('reason')
				);
			}

			if ($action === 'link') {
				return $this->linkUncertainLabel(
					$app->input->post->getInt('order_id'),
					$app->input->post->getString('label_id'),
					$app->input->post->getInt('package_index')
				);
			}

			throw new InvalidArgumentException('Ação inválida.', 400);
		} catch (Throwable $e) {
			$this->logSafe('Falha no endpoint', $e);
			throw $e;
		}
	}

	protected function maybeProcessOrder($order)
	{
		try {
			$this->processOrder($order, false);
		} catch (Throwable $e) {
			$this->logSafe('Processamento automático adiado', $e);
		}
	}

	protected function processOrder($order, $manual)
	{
		if (empty($order) || empty($order->order_id)) {
			return false;
		}

		$fullOrder = hikashop_get('class.order')->loadFullOrder((int) $order->order_id, true, false);
		$shipping = $this->extractStoredShipping($fullOrder);

		if (!$shipping) {
			return false;
		}

		$methodId = (int) $shipping['method_id'];
		$params = $this->loadMethodParams($methodId);
		$statuses = $this->csv($params->trigger_statuses ?? 'confirmed');

		if (!$manual && !in_array((string) $fullOrder->order_status, $statuses, true)) {
			return false;
		}

		$invoiceField = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($params->invoice_field ?? 'order_nf_key'));
		$invoiceKey = preg_replace('/\D+/', '', (string) ($fullOrder->{$invoiceField} ?? ''));
		$repository = $this->repository();
		$shipment = $repository->upsertShipment(
			(int) $fullOrder->order_id,
			$methodId,
			(int) $shipping['service_id'],
			$shipping['quote'],
			$invoiceKey
		);

		if ($shipment->state === 'generated') {
			return true;
		}

		if ($shipment->state === 'uncertain_cart') {
			throw new RuntimeException('A criação anterior ficou sem resposta. Confirme o ID no carrinho do Melhor Envio e vincule-o antes de reprocessar.');
		}

		if (strlen($invoiceKey) !== 44) {
			$repository->setShipmentState((int) $shipment->id, 'awaiting_invoice', 'Informe uma chave NF-e válida com 44 dígitos.');

			if ($manual) {
				throw new RuntimeException('O pedido ainda não possui uma chave NF-e válida com 44 dígitos.');
			}

			return false;
		}

		if (!$repository->acquireShipment((int) $shipment->id)) {
			return false;
		}

		$uncertainCart = false;

		try {
			$client = $this->client($methodId, $params);
			$labels = $repository->getLabels((int) $shipment->id);
			$builder = new PayloadBuilder($params);
			$payloads = $builder->buildCartPayloads($fullOrder, $shipping['quote'], $invoiceKey);
			$labelsByPackage = array();
			foreach ($labels as $label) {
				$labelsByPackage[(int) $label->package_index] = $label;
			}

			foreach ($payloads as $packageIndex => $payload) {
				if (isset($labelsByPackage[$packageIndex])) {
					continue;
				}

				try {
					$result = $client->request('POST', '/api/v2/me/cart', $payload);
				} catch (ApiException $e) {
					if ($e->getCode() === 0) {
						$uncertainCart = true;
					}
					throw $e;
				}
				$externalId = (string) ($result['id'] ?? '');

				if ($externalId === '') {
					throw new RuntimeException('A API não devolveu o identificador da etiqueta.');
				}

				$repository->addLabel((int) $shipment->id, $externalId, 'cart', (int) $packageIndex);
			}

			$labels = $repository->getLabels((int) $shipment->id);

			$checkoutIds = array();
			foreach ($labels as $label) {
				if (in_array($label->status, array('cart', 'pending'), true)) {
					$checkoutIds[] = $label->external_id;
				}
			}

			if ($checkoutIds) {
				$client->request('POST', '/api/v2/me/shipment/checkout', array('orders' => array_values($checkoutIds)));
				$repository->setLabelsState($checkoutIds, 'released');
			}

			$labels = $repository->getLabels((int) $shipment->id);
			$generateIds = array();
			foreach ($labels as $label) {
				if ($label->status === 'released') {
					$generateIds[] = $label->external_id;
				}
			}

			if ($generateIds) {
				$client->request('POST', '/api/v2/me/shipment/generate', array('orders' => array_values($generateIds)));
				$repository->setLabelsState($generateIds, 'generated');
			}

			$repository->setShipmentState((int) $shipment->id, 'generated', null);
			return true;
		} catch (Throwable $e) {
			$repository->setShipmentState(
				(int) $shipment->id,
				$uncertainCart ? 'uncertain_cart' : 'error',
				$this->safeError($e)
			);
			throw $e;
		} finally {
			$repository->releaseShipment((int) $shipment->id);
		}
	}

	protected function refreshTracking($orderId)
	{
		$repository = $this->repository();
		$shipment = $repository->getShipmentByOrder((int) $orderId);

		if (!$shipment) {
			throw new RuntimeException('Envio não encontrado.', 404);
		}

		$params = $this->loadMethodParams((int) $shipment->shipping_id);
		$labels = $repository->getLabels((int) $shipment->id);
		$ids = array_map(static fn($label) => $label->external_id, $labels);
		$result = $this->client((int) $shipment->shipping_id, $params)
			->request('POST', '/api/v2/me/shipment/tracking', array('orders' => $ids));

		foreach ($result as $externalId => $tracking) {
			$externalId = is_string($externalId) && !ctype_digit($externalId)
				? $externalId
				: (string) ($tracking['id'] ?? '');

			if ($externalId === '') {
				continue;
			}

			$repository->updateTracking(
				(string) $externalId,
				(string) ($tracking['status'] ?? ''),
				(string) ($tracking['tracking'] ?? ''),
				(string) ($tracking['tracking_url'] ?? '')
			);
			$this->saveHikaTracking($repository->getOrderIdByExternalId($externalId), (string) ($tracking['tracking'] ?? ''));
		}

		return array('ok' => true, 'tracking' => $result);
	}

	protected function labelUrl($orderId, $externalId, $format)
	{
		$format = in_array($format, array('pdf', 'zpl', 'jpeg'), true) ? $format : 'pdf';
		$label = $this->repository()->getLabelForOrder((int) $orderId, (string) $externalId);

		if (!$label || $label->status !== 'generated') {
			throw new RuntimeException('Etiqueta gerada não encontrada para este pedido.', 404);
		}

		$shipment = $this->repository()->getShipmentByOrder((int) $orderId);
		$params = $this->loadMethodParams((int) $shipment->shipping_id);
		$url = $this->client((int) $shipment->shipping_id, $params)->requestValue(
			'GET',
			'/api/v2/me/imprimir/' . $format . '/' . rawurlencode((string) $externalId)
		);

		if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
			throw new RuntimeException('O Melhor Envio não devolveu o arquivo da etiqueta.');
		}

		return array('ok' => true, 'label_url' => $url, 'format' => $format);
	}

	protected function cancelLabel($orderId, $externalId, $reason)
	{
		$repository = $this->repository();
		$label = $repository->getLabelForOrder((int) $orderId, (string) $externalId);

		if (!$label) {
			throw new RuntimeException('Etiqueta não encontrada para este pedido.', 404);
		}

		$shipment = $repository->getShipmentByOrder((int) $orderId);
		$params = $this->loadMethodParams((int) $shipment->shipping_id);
		$client = $this->client((int) $shipment->shipping_id, $params);
		$check = $client->request('POST', '/api/v2/me/shipment/cancellable', array('orders' => array((string) $externalId)));

		if (empty($check[$externalId]['cancellable'])) {
			throw new RuntimeException('A transportadora não permite mais cancelar esta etiqueta.');
		}

		$result = $client->request('POST', '/api/v2/me/shipment/cancel', array(
			'order' => array(
				'id' => (string) $externalId,
				'reason_id' => '2',
				'description' => mb_substr(trim((string) $reason) ?: 'Cancelamento solicitado pelo lojista.', 0, 255),
			),
		));

		if (empty($result[$externalId]['canceled'])) {
			throw new RuntimeException('O Melhor Envio não confirmou o cancelamento.');
		}

		$repository->setLabelsState(array((string) $externalId), 'cancelled');
		return array('ok' => true, 'cancelled' => true);
	}

	protected function linkUncertainLabel($orderId, $externalId, $packageIndex)
	{
		$repository = $this->repository();
		$shipment = $repository->getShipmentByOrder((int) $orderId);

		if (!$shipment || $shipment->state !== 'uncertain_cart' || trim((string) $externalId) === '') {
			throw new RuntimeException('Não existe criação incerta para vincular neste pedido.');
		}

		$repository->addLabel((int) $shipment->id, trim((string) $externalId), 'cart', max(0, (int) $packageIndex));
		$repository->setShipmentState((int) $shipment->id, 'pending', null);

		return array('ok' => true, 'linked' => true);
	}

	protected function authorizationUrl($shippingId)
	{
		$params = $this->loadMethodParams($shippingId);
		$state = $this->repository()->createOAuthState((int) $shippingId);

		return $this->client($shippingId, $params)->authorizationUrl(
			$this->callbackUrl($shippingId),
			$state
		);
	}

	protected function handleOAuthCallback($shippingId)
	{
		$app = Factory::getApplication();
		$state = $app->input->getString('state');
		$code = $app->input->getString('code');

		if ($code === '' || !$this->repository()->consumeOAuthState((int) $shippingId, $state)) {
			throw new RuntimeException('Retorno OAuth inválido.', 403);
		}

		$params = $this->loadMethodParams($shippingId);
		$tokens = $this->client($shippingId, $params)->exchangeAuthorizationCode($code, $this->callbackUrl($shippingId));
		$this->repository()->saveTokens($shippingId, (string) $params->environment, $tokens);

		return array('ok' => true, 'message' => Text::_('PLG_HIKASHOPSHIPPING_MELHORENVIO_AUTHORIZED'));
	}

	protected function handleWebhook($shippingId)
	{
		if ($shippingId < 1) {
			throw new InvalidArgumentException('shipping_id obrigatório.', 400);
		}

		$params = $this->loadMethodParams($shippingId);
		$secret = $this->clientSecret($params);
		$raw = file_get_contents('php://input') ?: '';
		$signature = (string) ($_SERVER['HTTP_X_ME_SIGNATURE'] ?? '');
		$binary = hash_hmac('sha256', $raw, $secret, true);
		$valid = hash_equals(base64_encode($binary), preg_replace('/^sha256=/i', '', $signature))
			|| hash_equals(bin2hex($binary), preg_replace('/^sha256=/i', '', $signature));

		if ($secret === '' || !$valid) {
			throw new RuntimeException('Assinatura inválida.', 401);
		}

		$payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		$eventName = (string) ($payload['event'] ?? $payload['name'] ?? 'unknown');
		$externalId = (string) ($payload['data']['id'] ?? $payload['data']['order_id'] ?? '');
		$hash = hash('sha256', $signature . '|' . $raw);
		$repository = $this->repository();

		if (!$repository->recordEvent($hash, $eventName, $externalId, $raw)) {
			return array('ok' => true, 'duplicate' => true);
		}

		if ($externalId !== '') {
			$repository->applyWebhook(
				$externalId,
				preg_replace('/^order\./', '', $eventName),
				(string) ($payload['data']['tracking'] ?? ''),
				(string) ($payload['data']['tracking_url'] ?? '')
			);
			$this->saveHikaTracking(
				$repository->getOrderIdByExternalId($externalId),
				(string) ($payload['data']['tracking'] ?? '')
			);
		}

		$repository->markEventProcessed($hash);
		return array('ok' => true);
	}

	protected function callbackUrl($shippingId)
	{
		return Uri::root() . 'index.php?option=com_ajax&plugin=melhorenvio&group=hikashopshipping&format=json&action=callback&shipping_id=' . (int) $shippingId;
	}

	protected function saveHikaTracking($orderId, $tracking)
	{
		if ((int) $orderId < 1 || trim((string) $tracking) === '') {
			return;
		}

		$update = new stdClass();
		$update->order_id = (int) $orderId;
		$update->order_shipping_number = mb_substr(trim((string) $tracking), 0, 255);
		hikashop_get('class.order')->save($update);
	}

	protected function client($shippingId, $params)
	{
		return new ApiClient(
			$this->repository(),
			(int) $shippingId,
			$params,
			$this->clientSecret($params)
		);
	}

	protected function clientSecret($params)
	{
		$envName = preg_replace('/[^A-Z0-9_]/i', '', (string) ($params->client_secret_env ?? ''));
		$fromEnvironment = $envName !== '' ? getenv($envName) : false;

		return $fromEnvironment !== false && $fromEnvironment !== ''
			? (string) $fromEnvironment
			: (string) ($params->client_secret ?? '');
	}

	protected function loadMethodParams($shippingId)
	{
		if ($shippingId < 1 || !$this->pluginParams($shippingId) || empty($this->plugin_params)) {
			throw new RuntimeException('Método de frete Melhor Envio não encontrado.', 404);
		}

		return $this->plugin_params;
	}

	protected function repository()
	{
		static $repository;

		if (!$repository) {
			$database = Factory::getContainer()->get(DatabaseInterface::class);
			$repository = new Repository($database, new Crypto((string) Factory::getConfig()->get('secret')));
		}

		return $repository;
	}

	protected function rememberQuote($key, array $snapshot)
	{
		$session = Factory::getApplication()->getSession();
		$quotes = (array) $session->get('ak.me.quotes', array());
		$quotes[$key] = $snapshot;
		$session->set('ak.me.quotes', $quotes);
	}

	protected function getRememberedQuote($key)
	{
		$quotes = (array) Factory::getApplication()->getSession()->get('ak.me.quotes', array());
		$snapshot = $quotes[$key] ?? null;

		if (!$snapshot || empty($snapshot['quoted_at']) || (time() - (int) $snapshot['quoted_at']) > 1800) {
			return null;
		}

		return $snapshot;
	}

	protected function selectedShippingIds($order)
	{
		$ids = array();

		if (!empty($order->cart->cart_shipping_ids)) {
			foreach ((array) $order->cart->cart_shipping_ids as $selected) {
				$ids[] = explode('@', (string) $selected, 2)[0];
			}
		}

		if (!empty($order->order_shipping_id)) {
			foreach ((array) $order->order_shipping_id as $selected) {
				$ids[] = explode('@', (string) $selected, 2)[0];
			}
		}

		return array_values(array_unique($ids));
	}

	protected function extractStoredShipping($order)
	{
		$params = $order->order_shipping_params ?? null;

		if (is_string($params)) {
			$params = hikashop_unserialize($params);
		}

		if (is_object($params)) {
			$params = (array) $params;
		}

		$shipping = $params['melhorenvio'] ?? null;

		if (is_object($shipping)) {
			$shipping = (array) $shipping;
		}

		return is_array($shipping) && !empty($shipping['quote']) ? $shipping : null;
	}

	protected function csv($value)
	{
		if (is_array($value)) {
			return array_values(array_filter(array_map('strval', $value), 'strlen'));
		}

		return array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
	}

	protected function assertAdministrator()
	{
		$user = Factory::getApplication()->getIdentity();

		if ($user->guest || !$user->authorise('core.manage', 'com_hikashop')) {
			throw new RuntimeException('Acesso negado.', 403);
		}
	}

	protected function safeError(Throwable $e)
	{
		$message = $e instanceof ApiException ? $e->getPublicMessage() : $e->getMessage();
		return mb_substr(preg_replace('/Bearer\s+[A-Za-z0-9._-]+/i', 'Bearer [redacted]', $message), 0, 2000);
	}

	protected function logSafe($context, Throwable $e, $params = null)
	{
		if ($params !== null && empty($params->debug)) {
			return;
		}

		$this->writeToLog($context . ': ' . $this->safeError($e));
	}

	protected function writeToLog($message)
	{
		static $configured = false;

		if (!$configured) {
			Log::addLogger(
				array('text_file' => 'plg_hikashopshipping_melhorenvio.php'),
				Log::ALL,
				array('plg_hikashopshipping_melhorenvio')
			);
			$configured = true;
		}

		if (!is_string($message)) {
			$message = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		Log::add(
			mb_substr((string) $message, 0, 4000),
			Log::WARNING,
			'plg_hikashopshipping_melhorenvio'
		);
	}
}
