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
class AiViewAi extends hikashopView {
	var $ctrl = 'ai';
	var $nameListing = 'HIKA_AI_ASSIST';
	var $nameForm = 'HIKA_AI_ASSIST';
	var $icon = 'ai';

	function display($tpl = null, $params = null) {
		$this->paramBase = HIKASHOP_COMPONENT . '.' . $this->getName();
		$function = $this->getLayout();
		if(method_exists($this, $function))
			$this->$function($params);
		parent::display($tpl);
	}

	function assist($params = null) {
		$this->assignRef('params', $params);
	}
}
