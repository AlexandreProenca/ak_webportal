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

class hikashopOperatorsinType extends hikashopType{
	var $js = '';
	function __construct(){
		$this->values = array();

		$this->values[] = JHTML::_('select.option', 'IN',JText::_('HIKA_IN'));
		$this->values[] = JHTML::_('select.option', 'NOT IN',JText::_('HIKA_NOT_IN'));
	}

	function display($map, $default='', $additionalClass=''){
		return JHTML::_('select.genericlist', $this->values, $map, 'class="custom-select '.$additionalClass.'" size="1" style="width:120px;" '.$this->js, 'value', 'text',$default);
	}

}
