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
class hikashopButtonType extends hikashopType{
	function load($value){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 'normal',JText::_('NORMAL'));
		if($value=='rounded'){
			$this->values[] = JHTML::_('select.option', 'rounded',JText::_('ROUNDED'));
		}
		$this->values[] = JHTML::_('select.option', 'css',JText::_('CSS'));
	}
	function display($map,$value){
		$this->load($value);
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value );
	}
}
