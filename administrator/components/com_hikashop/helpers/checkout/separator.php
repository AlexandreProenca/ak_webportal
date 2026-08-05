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
include_once HIKASHOP_HELPER . 'checkout.php';

class hikashopCheckoutSeparatorHelper extends hikashopCheckoutHelperInterface {
		protected $params = array(
		'type' =>  array(
			'name' => 'TYPE_OF_SEPARATION',
			'type' => 'radio',
			'default' => 'vertical',
		),
	);

	public function getParams() {
		$values = array(
			JHTML::_('select.option', 'vertical', JText::_('VERTICAL')),
			JHTML::_('select.option', 'horizontal', JText::_('HORIZONTAL'))
		);
		$this->params['type']['values'] = $values;

		return parent::getParams();
	}


	public function haveEmptyContent(&$controller, &$params) {
		return true;
	}

}
