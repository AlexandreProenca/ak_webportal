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

class HikashopUcpRouter {

	var $params = null;

	function __construct(&$params) {
		$this->params = $params;
	}

	function dispatch($path, $basePath) {
		require_once __DIR__.'/UcpAuth.php';
		$auth = new HikashopUcpAuth($this->params);
		if(!$auth->validate())
			return;

		$relativePath = trim(substr($path, strlen($basePath)), '/');
		$method = $_SERVER['REQUEST_METHOD'];

		if($relativePath === 'checkout-sessions' && $method === 'POST') {
			$this->_loadCheckout()->create();
			return;
		}

		if(preg_match('#^checkout-sessions/([-_a-zA-Z0-9]+)/complete$#', $relativePath, $m)) {
			$this->_loadCheckout()->complete($m[1]);
			return;
		}

		if(preg_match('#^checkout-sessions/([-_a-zA-Z0-9]+)/cancel$#', $relativePath, $m)) {
			$this->_loadCheckout()->cancel($m[1]);
			return;
		}

		if(preg_match('#^checkout-sessions/([-_a-zA-Z0-9]+)$#', $relativePath, $m) && $method === 'GET') {
			$this->_loadCheckout()->get($m[1]);
			return;
		}

		if(preg_match('#^checkout-sessions/([-_a-zA-Z0-9]+)$#', $relativePath, $m) && $method === 'PUT') {
			$this->_loadCheckout()->update($m[1]);
			return;
		}

		if($relativePath === 'payment-methods' && $method === 'GET') {
			$this->_loadPayment()->listMethods();
			return;
		}

		if($relativePath === 'search' && $method === 'POST') {
			$this->_loadCatalog()->search();
			return;
		}

		if(preg_match('#^orders/([-_a-zA-Z0-9]+)$#', $relativePath, $m) && $method === 'GET') {
			$this->_loadOrders()->get($m[1]);
			return;
		}

		if(preg_match('#^orders/([-_a-zA-Z0-9]+)/status$#', $relativePath, $m) && $method === 'POST') {
			$this->_loadOrders()->updateStatus($m[1]);
			return;
		}

		$this->_outputError('Not Found', 404, 'Not Found');
	}

	private function _loadCheckout() {
		require_once __DIR__.'/UcpMapper.php';
		require_once __DIR__.'/UcpCheckout.php';
		return new HikashopUcpCheckout($this->params);
	}

	private function _loadCatalog() {
		require_once __DIR__.'/UcpMapper.php';
		require_once __DIR__.'/UcpCatalog.php';
		$mapper = new HikashopUcpMapper($this->params);
		$catalog = new HikashopUcpCatalog($this->params);
		$catalog->setMapper($mapper);
		return $catalog;
	}

	private function _loadPayment() {
		require_once __DIR__.'/UcpMapper.php';
		require_once __DIR__.'/UcpPayment.php';
		return new HikashopUcpPayment($this->params);
	}

	private function _loadOrders() {
		require_once __DIR__.'/UcpOrders.php';
		return new HikashopUcpOrders($this->params);
	}

	private function _outputError($message, $code = 500, $httpText = 'Internal Server Error') {
		header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$httpText, true, $code);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => $message));
		exit;
	}
}
