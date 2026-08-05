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
?><br/>
<span id="hikashop_checkout_status">
<?php
	$array = array();
	if(!empty($this->shipping_data)) {
		$names = array();
		foreach($this->shipping_data as $shipping) {
			$names[] = $shipping->shipping_name;
		}
		$array[] = JText::sprintf('HIKASHOP_SHIPPING_METHOD_CHOSEN', '<span class="label label-info">'.implode(', ', $names).'</span>');
	}

	if(!empty($this->payment_data)) {
		$array[]= JText::sprintf('HIKASHOP_PAYMENT_METHOD_CHOSEN', '<span class="label label-info">'.$this->payment_data->payment_name.'</span>');
	}
	echo implode('<br/>', $array);
?>
</span>
<div class="clear_both"></div>
