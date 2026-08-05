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
class hikashopSlide_paginationType extends hikashopType{
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 'no_pagination',JText::_('HIKASHOP_NO'));
		$this->values[] = JHTML::_('select.option', 'numbers',JText::_('NUMBERS'));
		$this->values[] = JHTML::_('select.option', 'thumbnails',JText::_('THUMBNAILS'));
		$this->values[] = JHTML::_('select.option', 'rounds',JText::_('DOTS'));
	}
	function display($map,$value, $options=''){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1" '.$options, 'value', 'text', $value );
	}
}
