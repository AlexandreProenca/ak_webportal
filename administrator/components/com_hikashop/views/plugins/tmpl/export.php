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

$app = JFactory::getApplication();
$type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'shipping');
if(!in_array($type, array('shipping', 'payment'))) {
	$type = 'shipping';
}

$config =& hikashop_config();
$format = $config->get('export_format','csv');
$separator = $config->get('csv_separator',';');
$force_quote = $config->get('csv_force_quote',1);
$force_text = $config->get('csv_force_text', false);
$decimal_separator = $config->get('csv_decimal_separator','.');

$export = hikashop_get('helper.spreadsheet');
$export->init($format, 'hikashop_'.$type.'_export', $separator, $force_quote, $decimal_separator, $force_text);

$classType = 'class.'.$type;
$class = hikashop_get($classType);
$columns = array();
if(method_exists($class, 'getExportColumns')) {
	$columns = $class->getExportColumns(true);
} else {
	$db = JFactory::getDBO();
	if(!HIKASHOP_J30){
		$columnsTable = $db->getTableFields(hikashop_table($type));
		$columnsArray = reset($columnsTable);
	} else {
		$columnsArray = $db->getTableColumns(hikashop_table($type));
	}
	$columns = array_combine(array_keys($columnsArray), array_keys($columnsArray));
}

$export->writeline($columns);

$db = JFactory::getDBO();
$query = 'SELECT * FROM '.hikashop_table($type).' ORDER BY '.$type.'_ordering ASC';
$db->setQuery($query);
$rows = $db->loadObjectList();

if(!empty($rows)) {
	$keys = array_keys($columns);
	foreach($rows as $row) {
		$data = array();
		foreach($keys as $col) {
			$val = isset($row->$col) ? $row->$col : '';
			$data[] = $val;
		}
		$export->writeline($data);
	}
}

$export->send();
exit;
