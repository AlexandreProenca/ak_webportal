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

class HikashopUcpPayment {

	var $params = null;
	var $mapper = null;

	function __construct(&$params) {
		$this->params = $params;
		$this->mapper = new HikashopUcpMapper($params);
	}

	function getAvailableMethods($cart = null) {
		$db = JFactory::getDBO();
		$db->setQuery('SELECT * FROM '.hikashop_table('payment').' WHERE payment_published = 1 ORDER BY payment_ordering');
		$methods = $db->loadObjectList();
		if(empty($methods))
			return array();

		$available = array();
		foreach($methods as $method) {
			if(!empty($method->payment_params) && is_string($method->payment_params))
				$method->payment_params = hikashop_unserialize($method->payment_params);

			$plugin = hikashop_import('hikashoppayment', $method->payment_type);
			if(empty($plugin))
				continue;
			if(!method_exists($plugin, 'getPaymentURL'))
				continue;

			$reflection = new ReflectionMethod($plugin, 'getPaymentURL');
			if($reflection->getDeclaringClass()->getName() === 'hikashopPaymentPlugin')
				continue;

			$available[] = array(
				'id' => $this->mapper->globalId('PaymentMethod', $method->payment_id),
				'name' => hikashop_translate($method->payment_name),
				'type' => $method->payment_type,
				'description' => !empty($method->payment_description) ? hikashop_translate($method->payment_description) : '',
			);
		}
		return $available;
	}

	function generatePaymentURL($order, $paymentMethodId) {
		$db = JFactory::getDBO();
		$db->setQuery('SELECT * FROM '.hikashop_table('payment').' WHERE payment_id = '.(int)$paymentMethodId.' AND payment_published = 1');
		$method = $db->loadObject();

		if(empty($method))
			return array('error' => 'Payment method not found or not published');

		if(!empty($method->payment_params) && is_string($method->payment_params))
			$method->payment_params = hikashop_unserialize($method->payment_params);

		$plugin = hikashop_import('hikashoppayment', $method->payment_type);
		if(empty($plugin))
			return array('error' => 'Payment plugin not available: '.$method->payment_type);

		if(!method_exists($plugin, 'getPaymentURL'))
			return array('error' => 'Payment plugin does not support URL generation');

		$reflection = new ReflectionMethod($plugin, 'getPaymentURL');
		if($reflection->getDeclaringClass()->getName() === 'hikashopPaymentPlugin')
			return array('error' => 'Payment plugin does not support URL generation');

		$url = $plugin->getPaymentURL($order, $method);

		if($url === null)
			return array('error' => 'Payment plugin does not support URL generation');

		if($url === false) {
			$error = !empty($plugin->last_error) ? $plugin->last_error : 'Payment URL generation failed';
			return array('error' => $error);
		}

		return array('url' => $url);
	}

	function listMethods() {
		$methods = $this->getAvailableMethods();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'payment_methods' => $methods
		));
		exit;
	}
}
