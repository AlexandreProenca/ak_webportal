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
?><div class="hikashop_check_end" id="hikashop_check_end">
	<span class="hikashop_check_end_message" id="hikashop_check_end_message">
		<?php
		echo JText::_('ORDER_IS_COMPLETE').'<br/>'.
		JText::sprintf('PLEASE_SEND_CHECK',$this->amount).'<br/>'.
		$this->information.'<br/>'.
		JText::sprintf('INCLUDE_ORDER_NUMBER_TO_CHECK',$this->order_number).'<br/>'.
		JText::_('THANK_YOU_FOR_PURCHASE');?>
	</span>
</div>
<?php
if(!empty($this->payment_params->return_url)){
	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration("window.hikashop.ready( function() {window.location='".addslashes($this->payment_params->return_url)."'});");
}
