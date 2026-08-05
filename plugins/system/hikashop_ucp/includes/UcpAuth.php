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

class HikashopUcpAuth {

	var $params = null;

	function __construct(&$params) {
		$this->params = $params;
	}

	function validate() {
		$apikeys = $this->_getApiKeys();
		if(empty($apikeys))
			return true;

		$headers = function_exists('getallheaders') ? getallheaders() : array();
		if(empty($headers['X-API-KEY']) && !empty($_SERVER['HTTP_X_API_KEY']))
			$headers['X-API-KEY'] = $_SERVER['HTTP_X_API_KEY'];

		$requestKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : '';

		$valid = false;
		foreach($apikeys as $apikey) {
			if(hash_equals($apikey, $requestKey)) {
				$valid = true;
				break;
			}
		}

		if(!$valid && $this->params->get('webmcp_enabled', 0)) {
			$sessionName = JFactory::getSession()->getName();
			if(!empty($sessionName) && !empty($_COOKIE[$sessionName]))
				$valid = true;
		}

		if(!$valid) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden', true, 403);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('error' => 'Invalid API Key'));
			exit;
		}

		return true;
	}

	private function _getApiKeys() {
		$keys = array();

		$entries = $this->params->get('api_keys', '');
		if(!empty($entries)) {
			if(is_string($entries))
				$entries = json_decode($entries);
			if(!empty($entries) && (is_array($entries) || is_object($entries))) {
				foreach($entries as $entry) {
					$entry = (object)$entry;
					if(!empty($entry->key))
						$keys[] = trim($entry->key);
				}
			}
		}

		$singleKey = $this->params->get('api_key', '');
		if(!empty($singleKey))
			$keys[] = trim($singleKey);

		return $keys;
	}
}
