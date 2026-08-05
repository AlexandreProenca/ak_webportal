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

class HikashopUcpMcp {

	var $params = null;

	function __construct(&$params) {
		$this->params = $params;
	}

	function handle() {
		$massactionDir = JPATH_PLUGINS.'/system/hikashopmassaction';
		if(!is_dir($massactionDir))
			$massactionDir = JPATH_PLUGINS.'/plg_system_hikashopmassaction';
		$vendorPath = $massactionDir.'/vendor/autoload.php';
		$transportPath = $massactionDir.'/HikashopMcpTransport.php';

		if(!file_exists($vendorPath) || !file_exists($transportPath)) {
			header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable', true, 503);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('error' => 'MCP dependencies not available. The HikaShop Mass Action plugin with MCP support must be installed.'));
			exit;
		}

		require_once $vendorPath;
		require_once $transportPath;

		if(!class_exists('HikashopMcpTransport')) {
			header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable', true, 503);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('error' => 'MCP transport not available. PHP 8.1+ is required.'));
			exit;
		}

		require_once __DIR__.'/UcpMapper.php';
		require_once __DIR__.'/UcpCheckout.php';
		require_once __DIR__.'/UcpPayment.php';

		$params = $this->params;
		$checkout = new HikashopUcpCheckout($params);
		$payment = new HikashopUcpPayment($params);

		$serverBuilder = \Mcp\Server::builder()
			->setServerInfo('HikaShop UCP', '1.0');

		$serverBuilder->addTool(
			function($toolParams) use ($checkout) {
				return $this->_wrapTool(function() use ($toolParams, $checkout) {
					$_SERVER['REQUEST_METHOD'] = 'POST';
					$this->_injectInput($toolParams);
					$checkout->create();
				});
			},
			'create_checkout',
			'Create a new UCP checkout session with line items',
			null,
			array(
				'type' => 'object',
				'properties' => array(
					'line_items' => array(
						'type' => 'array',
						'description' => 'Array of line items with item.id and quantity',
						'items' => array(
							'type' => 'object',
							'properties' => array(
								'item' => array(
									'type' => 'object',
									'properties' => array(
										'id' => array('type' => 'string', 'description' => 'Product ID')
									)
								),
								'quantity' => array('type' => 'integer', 'description' => 'Quantity')
							)
						)
					)
				)
			)
		);

		$serverBuilder->addTool(
			function($toolParams) use ($checkout) {
				return $this->_wrapTool(function() use ($toolParams, $checkout) {
					$id = isset($toolParams['id']) ? $toolParams['id'] : '';
					$checkout->get($id);
				});
			},
			'get_checkout',
			'Get a checkout session by ID',
			null,
			array(
				'type' => 'object',
				'properties' => array(
					'id' => array('type' => 'string', 'description' => 'Checkout session ID or global ID')
				)
			)
		);

		$serverBuilder->addTool(
			function($toolParams) use ($checkout) {
				return $this->_wrapTool(function() use ($toolParams, $checkout) {
					$id = isset($toolParams['id']) ? $toolParams['id'] : '';
					unset($toolParams['id']);
					$_SERVER['REQUEST_METHOD'] = 'PUT';
					$this->_injectInput($toolParams);
					$checkout->update($id);
				});
			},
			'update_checkout',
			'Update a checkout session with buyer info, shipping address, or shipping selection',
			null,
			array(
				'type' => 'object',
				'properties' => array(
					'id' => array('type' => 'string', 'description' => 'Checkout session ID'),
					'buyer' => array(
						'type' => 'object',
						'description' => 'Buyer information (first_name, last_name, email)',
						'properties' => array(
							'first_name' => array('type' => 'string'),
							'last_name' => array('type' => 'string'),
							'email' => array('type' => 'string')
						)
					),
					'fulfillment' => array(
						'type' => 'object',
						'description' => 'Fulfillment/shipping details'
					)
				)
			)
		);

		$serverBuilder->addTool(
			function($toolParams) use ($checkout) {
				return $this->_wrapTool(function() use ($toolParams, $checkout) {
					$_SERVER['REQUEST_METHOD'] = 'POST';
					$id = isset($toolParams['id']) ? $toolParams['id'] : '';
					unset($toolParams['id']);
					$this->_injectInput($toolParams);
					$checkout->complete($id);
				});
			},
			'complete_checkout',
			'Complete a checkout session and create an order. Use payment_method_id for hosted checkout URL flow, or payment_data for credential-based flow.',
			null,
			array(
				'type' => 'object',
				'properties' => array(
					'id' => array('type' => 'string', 'description' => 'Checkout session ID'),
					'payment_method_id' => array('type' => 'string', 'description' => 'Payment method global ID for hosted checkout URL flow (from list_payment_methods)'),
					'payment_data' => array(
						'type' => 'object',
						'description' => 'Payment credential data (for direct credential flow like Google Pay)'
					)
				)
			)
		);

		$serverBuilder->addTool(
			function($toolParams) use ($checkout) {
				return $this->_wrapTool(function() use ($toolParams, $checkout) {
					$id = isset($toolParams['id']) ? $toolParams['id'] : '';
					$checkout->cancel($id);
				});
			},
			'cancel_checkout',
			'Cancel a checkout session',
			null,
			array(
				'type' => 'object',
				'properties' => array(
					'id' => array('type' => 'string', 'description' => 'Checkout session ID')
				)
			)
		);

		$serverBuilder->addTool(
			function($toolParams) use ($payment) {
				return $this->_wrapTool(function() use ($payment) {
					$payment->listMethods();
				});
			},
			'list_payment_methods',
			'List available payment methods that support hosted checkout URLs',
			null,
			array(
				'type' => 'object',
				'properties' => new stdClass()
			)
		);

		try {
			$transport = new HikashopMcpTransport();
			$server = $serverBuilder->build();
			$server->run($transport);
		} catch(\Throwable $e) {
			if(function_exists('hikashop_writeToLog'))
				hikashop_writeToLog($e->getMessage(), 'UCP MCP Server Error');
			header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
			echo 'Server Error: '.$e->getMessage();
		}
		exit;
	}

	private function _wrapTool($callback) {
		ob_start();
		try {
			$callback();
		} catch(\Throwable $e) {
			ob_end_clean();
			return new \Mcp\Schema\Result\CallToolResult(array(
				new \Mcp\Schema\Content\TextContent('Error: '.$e->getMessage())
			));
		}
		$output = ob_get_clean();

		return new \Mcp\Schema\Result\CallToolResult(array(
			new \Mcp\Schema\Content\TextContent($output)
		));
	}

	private function _injectInput($data) {
		$GLOBALS['_hikashop_ucp_mcp_input'] = json_encode($data);
	}
}
