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
?><form action="<?php echo hikashop_completeLink('order&task=customer_save') ;?>" method="post" name="hikashop_form" id="hikashop_form">
<div class="hika_confirm">
	<?php echo JText::_('HIKA_CONFIRM_USER')?><br/>
	<table class="admintable table hika_options">
		<tbody>
			<tr>
				<td class="key"><label><?php echo JText::_('HIKA_NAME'); ?></label></td>
				<td id="hikashop_order_customer_name"><?php echo $this->rows->name; ?></td>
			</tr>
			<tr>
				<td class="key"><label><?php echo JText::_('HIKA_EMAIL'); ?></label></td>
				<td id="hikashop_order_customer_email"><?php echo $this->rows->user_email; ?></td>
			</tr>
			<tr>
				<td class="key"><label><?php echo JText::_('ID'); ?></label></td>
				<td id="hikashop_order_customer_id"><?php echo $this->rows->user_id; ?></td>
			</tr>
			<tr>
				<td class="key"><label><?php echo JText::_('SET_USER_ADDRESS'); ?></label></td>
				<td><?php echo JHTML::_('hikaselect.booleanlist', 'set_user_address', 'onchange="window.orderMgr.addressSwitch(this);"', 0); ?></td>
			</tr>
			<tr class="address_selector" style="display:none;">
				<td class="key"><label><?php echo JText::_('HIKASHOP_BILLING_ADDRESS'); ?></label></td>
				<td><?php
$default = 0;
$oldLabel = $this->isGuestUser ? JText::_('PREVIOUS_ORDER_ADDRESSES') : JText::_('OLD_ADDRESSES');
$hasOld = false;
?>
<select name="billing_address" class="hikashop_field_dropdown custom-select">
	<option value="0"><?php echo JText::_('NO_ADDRESS'); ?></option>
<?php
foreach($this->addresses as $address) {
	if(empty($address))
		continue;
	if(!empty($address->address_type) && !in_array($address->address_type, array('both', '', 'billing')))
		continue;
	if($address->address_default)
		$default = $address->address_id;
	$addr = $this->addressClass->miniFormat($address);
?>
	<option value="<?php echo $address->address_id; ?>"<?php echo ($address->address_id == $default) ? ' selected="selected"' : ''; ?>><?php echo $addr; ?></option>
<?php
}
if(!empty($this->unpublishedAddresses)) {
	foreach($this->unpublishedAddresses as $address) {
		if(empty($address))
			continue;
		if(!empty($address->address_type) && !in_array($address->address_type, array('both', '', 'billing')))
			continue;
		if(!$hasOld) {
			$hasOld = true;
			echo '<optgroup label="' . $this->escape($oldLabel) . '">';
		}
		$addr = $this->addressClass->miniFormat($address);
?>
	<option value="<?php echo $address->address_id; ?>"><?php echo $addr; ?></option>
<?php
	}
	if($hasOld)
		echo '</optgroup>';
}
?>
</select>
				</td>
			</tr>
			<tr class="address_selector" style="display:none;">
				<td class="key"><label><?php echo JText::_('HIKASHOP_SHIPPING_ADDRESS'); ?></label></td>
				<td><?php
$default = 0;
$hasOld = false;
?>
<select name="shipping_address" class="hikashop_field_dropdown custom-select">
	<option value="0"><?php echo JText::_('NO_ADDRESS'); ?></option>
<?php
foreach($this->addresses as $address) {
	if(empty($address))
		continue;
	if(!empty($address->address_type) && !in_array($address->address_type, array('both', '', 'shipping')))
		continue;
	if($address->address_default)
		$default = $address->address_id;
	$addr = $this->addressClass->miniFormat($address);
?>
	<option value="<?php echo $address->address_id; ?>"<?php echo ($address->address_id == $default) ? ' selected="selected"' : ''; ?>><?php echo $addr; ?></option>
<?php
}
if(!empty($this->unpublishedAddresses)) {
	foreach($this->unpublishedAddresses as $address) {
		if(empty($address))
			continue;
		if(!empty($address->address_type) && !in_array($address->address_type, array('both', '', 'shipping')))
			continue;
		if(!$hasOld) {
			$hasOld = true;
			echo '<optgroup label="' . $this->escape($oldLabel) . '">';
		}
		$addr = $this->addressClass->miniFormat($address);
?>
	<option value="<?php echo $address->address_id; ?>"><?php echo $addr; ?></option>
<?php
	}
	if($hasOld)
		echo '</optgroup>';
}
?>
</select>
			</td>
			</tr>
			<tr>
				<td class="key"><label><?php echo JText::_('HISTORY'); ?></label></td>
				<td>
					<span><input onchange="window.orderMgr.orderadditional_history_changed(this);" type="checkbox" id="hikashop_history_orderadditional_store" name="data[history][store_data]" value="1"/><label for="hikashop_history_orderadditional_store" style="display:inline-block"><?php echo JText::_('SET_HISTORY_MESSAGE');?></label></span><br/>
					<textarea id="hikashop_history_orderadditional_msg" name="data[history][history_data]" style="display:none;"></textarea>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="hidden" name="data[order][order_user_id]" value="<?php echo $this->rows->user_id; ?>"/>
	<input type="hidden" name="cid" value="<?php echo $this->order_id; ?>"/>
	<input type="hidden" name="order_id" value="<?php echo $this->order_id; ?>"/>
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="customer_save" />
	<input type="hidden" name="finalstep" value="1" />
	<input type="hidden" name="single" value="1" />
	<input type="hidden" name="ctrl" value="order" />
	<input type="hidden" name="tmpl" value="component" />
	<?php echo JHTML::_('form.token'); ?>
	<div class="hika_confirm_btn">
		<button onclick="hikashop.submitform('customer_save', 'hikashop_form');" class="btn"><img src="<?php echo HIKASHOP_IMAGES ?>ok.png" style="vertical-align:middle" alt=""/> <span><?php echo Jtext::_('OK'); ?></span></button>
	</div>
</div>
<script type="text/javascript">
if(!window.orderMgr)
	window.orderMgr = {};
window.orderMgr.orderadditional_history_changed = function(el) {
	var fields = ['hikashop_history_orderadditional_msg'], displayValue = '';
	if(!el.checked) displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}
window.orderMgr.addressSwitch = function(el) {
	var elements = document.querySelectorAll('.address_selector');
	var display = 'none';
	if(parseInt(el.value) == 1) {
		display = '';
	}
	for (var i = 0; i < elements.length; i++) {
		elements[i].style.display = display;
	}
}

</script>
</form>
