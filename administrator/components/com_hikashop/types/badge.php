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
class hikashopBadgeType extends hikashopType{
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 'topright',JText::_('HIKA_TOP_RIGHT'));
		$this->values[] = JHTML::_('select.option', 'topleft',JText::_('HIKA_TOP_LEFT'));
		$this->values[] = JHTML::_('select.option', 'bottomright',JText::_('HIKA_BOTTOM_RIGHT'));
		$this->values[] = JHTML::_('select.option', 'bottomleft',JText::_('HIKA_BOTTOM_LEFT'));
	}
	function display($map,$value){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value );
	}
}
