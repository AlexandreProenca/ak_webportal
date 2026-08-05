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
hikashop_cleanBuffers();

$config =& hikashop_config();
$format = $config->get('export_format','csv');
$separator = $config->get('csv_separator',';');
$force_quote = $config->get('csv_force_quote',1);
$force_text = $config->get('csv_force_text', false);
$decimal_separator = $config->get('csv_decimal_separator','.');

$export = hikashop_get('helper.spreadsheet');
$export->init($format, 'hikashop_export', $separator, $force_quote, $decimal_separator, $force_text);

$classOrder = hikashop_get('class.order');
$selected_fields = $classOrder->getExportColumns(true);
$export_products = false;

$columns = array();
if(!empty($this->orders)){
	$maxProd = 0;
	$productFields = null;
	foreach($this->orders as $order){
		$orderColumns = array_keys(get_object_vars($order));
		foreach($orderColumns as $column) {
			if(is_array($order->$column))
				continue;
			if(!isset($columns[$column])) {
				$columns[$column] = $column;
			}
		}
		if(empty($order->products))
			continue;
		$nbProd = count($order->products);
		if($maxProd < $nbProd){
			$maxProd = $nbProd;
			if(empty($productFields)){
				$productFields = array_keys(get_object_vars(reset($order->products)));
			}
		}
	}

	$products_per_row = $config->get('order_export_one_product_per_row', false);
	if($selected_fields !== null) {
		$columns = array_intersect_key($columns, $selected_fields);
	}

	if($maxProd && !empty($productFields)) {
		if($selected_fields !== null) {
			$productFields = array_intersect($productFields, array_keys($selected_fields));
		}

		if(!empty($productFields)) {
			if($products_per_row) {
				foreach($productFields as $field) {
					$columns[$field] = $field;
				}
			} else {
				$export_products = true;
				for($i=1;$i<=$maxProd;$i++){
					foreach($productFields as $field){
						$columns['item_'.$i.'_'.$field] = 'item_'.$i.'_'.$field;
					}
				}
			}
		}
	}
	$export->writeLine($columns);

	foreach($this->orders as $row){
		if(!empty($row->user_created)) $row->user_created = hikashop_getDate($row->user_created,'%Y-%m-%d %H:%M:%S');
		if(!empty($row->order_created)) $row->order_created = hikashop_getDate($row->order_created,'%Y-%m-%d %H:%M:%S');
		if(!empty($row->order_modified)) $row->order_modified = hikashop_getDate($row->order_modified,'%Y-%m-%d %H:%M:%S');
		if(!empty($row->order_modified)) $row->order_invoice_created = hikashop_getDate($row->order_invoice_created,'%Y-%m-%d %H:%M:%S');


		if($export_products && $maxProd && !empty($productFields)){
			for($i=1;$i<=$maxProd;$i++){
				$prod =& $row->products[$i-1];
				foreach($productFields as $field){
					$n = 'item_'.$i.'_'.$field;
					$row->$n = @$prod->$field;
				}
			}
		}

		if($products_per_row && !empty($row->products)) {
			foreach($row->products as $product) {
				foreach($productFields as $field) {
					$row->$field = @$product->$field;
				}
				$line = array();
				foreach($columns as $c) {
					$line[] = @$row->$c;
				}
				$export->writeLine($line);
			}
		} else {
			$line = array();
			foreach($columns as $c) {
				$line[] = @$row->$c;
			}
			$export->writeLine($line);
		}
	}
}

$export->send();
exit;
