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
$massaction = hikashop_get('class.massaction');
if(!empty($this->params->action)){
	$data = array();
	foreach($this->params->elements as $k1=>$element){
		$obj = new stdClass();
		foreach($this->params->action as $key=>$table){
			foreach($table as $column){
				if(isset($element->$column) && ($key===$k1 || $key===$this->params->table)){
					$square = $massaction->displayByType($this->params->types,$element,$column);
				}else{
					$square = '';
				}
				$obj->$column = $square;
			}
		}
		$data[] = $obj;
	}
	echo json_encode($data);
} else {
	echo json_encode(array('success' => false, 'message' => 'No action specified !'));
}
if($massaction->api() !== 'mcp') {
	exit;
}
