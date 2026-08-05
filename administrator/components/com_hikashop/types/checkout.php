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
class hikashopCheckoutType extends hikashopType {
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 0,JText::_('NO_CHECKOUT_PROGRESS'));
		$this->values[] = JHTML::_('select.option', 1,JText::_('CHECKOUT_PROGRESS'));
		$this->values[] = JHTML::_('select.option', 2,JText::_('CHECKOUT_PROGRESS_WITHOUT_END'));
	}
	function display($map,$value){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', (int)$value );
	}
}
