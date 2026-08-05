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
class hikashopTemplateType extends hikashopType{
	function load($templates, $value=null){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', '',JText::_('ALL_TEMPLATES'));
		if(!empty($value) && !isset($templates[$value])) {
			$templates[] = strip_tags($value);
		}
		foreach($templates as $template){
			$this->values[] = JHTML::_('select.option', $template,$template);
		}
	}
	function display($map,$value,$templates){
		$this->load($templates, $value);
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1" onchange="document.adminForm.submit();return false;"', 'value', 'text', $value );
	}
}
