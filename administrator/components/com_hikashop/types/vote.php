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
class hikashopVoteType extends hikashopType{

	var $values = array();

	function load() {
		$this->values['product'] =  JHTML::_('select.option', 'product', JText::_('PRODUCT'));
	}

	function display($map, $value, $extra = '') {
		if(empty($this->values))
			$this->load();
		$values = $this->values;
		return JHTML::_('select.genericlist', $values, $map, $extra, 'value', 'text', $value);
	}
}
