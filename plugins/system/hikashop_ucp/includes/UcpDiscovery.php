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

class HikashopUcpDiscovery {

	var $params = null;

	function __construct(&$params) {
		$this->params = $params;
	}

	function getProfile() {
		$basePath = trim($this->params->get('base_path', '/ucp/v1'), '/');
		$siteUrl = rtrim(HIKASHOP_LIVE, '/');

		$ucpVersion = '2026-01-11';

		$shoppingService = array(
			'version' => $ucpVersion,
			'spec' => 'https://ucp.dev/specification/overview',
			'rest' => array(
				'schema' => 'https://ucp.dev/services/shopping/rest.openapi.json',
				'endpoint' => $siteUrl.'/'.$basePath
			)
		);

		$mcpEnabled = $this->params->get('mcp_enabled', 0);
		if($mcpEnabled) {
			$mcpSlug = trim($this->params->get('mcp_slug', 'ucp-mcp'), '/');
			$shoppingService['mcp'] = array(
				'schema' => 'https://ucp.dev/services/shopping/mcp.openrpc.json',
				'endpoint' => $siteUrl.'/'.$mcpSlug
			);
		}

		$profile = array(
			'ucp' => array(
				'version' => $ucpVersion,
				'services' => array(
					'dev.ucp.shopping' => $shoppingService
				),
				'capabilities' => array(
					array(
						'name' => 'dev.ucp.shopping.catalog',
						'version' => $ucpVersion,
						'spec' => 'https://ucp.dev/specification/catalog',
						'schema' => 'https://ucp.dev/schemas/shopping/catalog.json'
					),
					array(
						'name' => 'dev.ucp.shopping.checkout',
						'version' => $ucpVersion,
						'spec' => 'https://ucp.dev/specification/checkout',
						'schema' => 'https://ucp.dev/schemas/shopping/checkout.json'
					),
					array(
						'name' => 'dev.ucp.shopping.order',
						'version' => $ucpVersion,
						'spec' => 'https://ucp.dev/specification/order',
						'schema' => 'https://ucp.dev/schemas/shopping/order.json'
					),
					array(
						'name' => 'dev.ucp.shopping.payment',
						'version' => $ucpVersion,
						'features' => array('hosted_checkout_url')
					),
				)
			),
			'links' => array()
		);

		$privacyUrl = $this->params->get('privacy_policy_url', '');
		if(!empty($privacyUrl))
			$profile['links'][] = array('type' => 'privacy_policy', 'url' => $privacyUrl);

		$termsUrl = $this->params->get('terms_url', '');
		if(!empty($termsUrl))
			$profile['links'][] = array('type' => 'terms_of_service', 'url' => $termsUrl);

		$gpayHandler = $this->_getGooglePayHandler();
		if(!empty($gpayHandler))
			$profile['payment'] = array('handlers' => array($gpayHandler));

		if($this->params->get('webmcp_enabled', 0)) {
			$profile['webmcp'] = array('enabled' => true);
		}

		header('Content-Type: application/json; charset=utf-8');
		header('Access-Control-Allow-Origin: *');
		echo json_encode($profile);
		exit;
	}

	private function _getGooglePayHandler() {
		if(!$this->params->get('gpay_enabled', 0))
			return null;

		$gatewayConfig = self::detectGooglePayGateway();
		if(empty($gatewayConfig))
			return null;

		$gateway = $gatewayConfig['gateway'];
		$gatewayMerchantId = $gatewayConfig['gatewayMerchantId'];

		$cardNetworks = $this->params->get('gpay_card_networks', array());
		if(is_string($cardNetworks))
			$cardNetworks = explode(',', $cardNetworks);
		$cardNetworks = array_filter(array_values($cardNetworks));
		if(empty($cardNetworks))
			$cardNetworks = array('VISA', 'MASTERCARD');

		$authMethods = $this->params->get('gpay_auth_methods', array());
		if(is_string($authMethods))
			$authMethods = explode(',', $authMethods);
		$authMethods = array_filter(array_values($authMethods));
		if(empty($authMethods))
			$authMethods = array('PAN_ONLY', 'CRYPTOGRAM_3DS');

		$siteUrl = rtrim(HIKASHOP_LIVE, '/');
		$parsed = parse_url($siteUrl);
		$domain = isset($parsed['host']) ? $parsed['host'] : 'localhost';

		$handler = array(
			'id' => 'gpay',
			'name' => 'com.google.pay',
			'version' => '2026-01-11',
			'spec' => 'https://pay.google.com/gp/p/ucp/2026-01-11/',
			'config_schema' => 'https://pay.google.com/gp/p/ucp/2026-01-11/schemas/config.json',
			'instrument_schemas' => array(
				'https://pay.google.com/gp/p/ucp/2026-01-11/schemas/card_payment_instrument.json'
			),
			'config' => array(
				'api_version' => 2,
				'api_version_minor' => 0,
				'environment' => $this->params->get('gpay_environment', 'TEST'),
				'merchant_info' => array(
					'merchant_name' => $this->params->get('gpay_merchant_name', ''),
					'merchant_id' => $this->params->get('gpay_merchant_id', ''),
					'merchant_origin' => $domain
				),
				'allowed_payment_methods' => array(
					array(
						'type' => 'CARD',
						'parameters' => array(
							'allowed_auth_methods' => $authMethods,
							'allowed_card_networks' => $cardNetworks
						),
						'tokenization_specification' => array(
							'type' => 'PAYMENT_GATEWAY',
							'parameters' => array(
								'gateway' => $gateway,
								'gatewayMerchantId' => $gatewayMerchantId
							)
						)
					)
				)
			)
		);

		return $handler;
	}

	static public function detectGooglePayGateway() {
		if(HIKASHOP_J50 && !class_exists('JPluginHelper'))
			class_alias('Joomla\\CMS\\Plugin\\PluginHelper', 'JPluginHelper');

		JPluginHelper::importPlugin('hikashoppayment');

		$db = JFactory::getDBO();
		$db->setQuery(
			'SELECT payment_id, payment_type FROM ' . hikashop_table('payment')
			. ' WHERE payment_published = 1'
		);
		$methods = $db->loadObjectList();
		if(empty($methods))
			return null;

		$app = JFactory::getApplication();
		$checked = array();

		foreach($methods as $method) {
			$type = $method->payment_type;

			$jPlugin = JPluginHelper::getPlugin('hikashoppayment', $type);
			if(empty($jPlugin))
				continue;

			$className = 'plgHikashoppayment' . ucfirst($type);
			if(!class_exists($className))
				continue;

			if(isset($checked[$type]))
				continue;

			$instance = new $className($app, (array)$jPlugin);
			if(!method_exists($instance, 'getUcpGooglePayConfig')) {
				$checked[$type] = true;
				continue;
			}

			$instance->pluginParams($method->payment_id);
			$config = $instance->getUcpGooglePayConfig();
			if(!empty($config) && !empty($config['gateway']) && !empty($config['gatewayMerchantId']))
				return $config;
		}

		return null;
	}
}
