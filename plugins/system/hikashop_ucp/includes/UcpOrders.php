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

class HikashopUcpOrders {

	var $params = null;

	function __construct(&$params) {
		$this->params = $params;
	}

	function get($id) {
		header($_SERVER['SERVER_PROTOCOL'].' 501 Not Implemented', true, 501);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'status' => 'error',
			'messages' => array(
				array('type' => 'error', 'message' => 'Order retrieval is not yet implemented')
			)
		));
		exit;
	}

	function updateStatus($id) {
		header($_SERVER['SERVER_PROTOCOL'].' 501 Not Implemented', true, 501);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'status' => 'error',
			'messages' => array(
				array('type' => 'error', 'message' => 'Order status update is not yet implemented')
			)
		));
		exit;
	}
}
