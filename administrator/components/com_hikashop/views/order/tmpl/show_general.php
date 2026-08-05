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
?>	<legend><?php echo JText::_('MAIN_INFORMATION'); ?></legend>
<?php
$show_url = 'order&task=show&subtask=general&cid='.$this->order->order_id;
$save_url = 'order&task=save&subtask=general&cid='.$this->order->order_id;
$update_url = 'order&task=edit&subtask=general&cid='.$this->order->order_id;
if(!isset($this->edit) || $this->edit !== true ) {
?>		<div class="hika_edit"><a href="<?php echo hikashop_completeLink($update_url, true);?>" class="btn btn-primary" onclick="return window.hikashop.get(this,'hikashop_order_field_general');"><i class="fas fa-pen"></i> <?php echo JText::_('HIKA_EDIT'); ?></a></div>
<?php
} else {
?>		<div class="hika_edit">
			<a href="<?php echo hikashop_completeLink($save_url, true);?>" class="btn btn-success" onclick="return window.orderMgr.saveGeneral(this,'hikashop_order_field_general');"><i class="fa fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?></a>
		<a href="<?php echo hikashop_completeLink($show_url, true);?>" class="btn btn-danger" onclick="return window.hikashop.get(this,'hikashop_order_field_general');"><i class="fa fa-times"></i> <?php echo JText::_('HIKA_CANCEL'); ?></a>
		</div>
<?php
}
?>
<!-- GENERAL TOP EXTRA DATA -->
<?php if(!empty($this->order->extraData->generalTop)) { echo implode("\r\n", $this->order->extraData->generalTop); } ?>
<!-- EO GENERAL TOP EXTRA DATA -->
	<table class="admintable table">
<!-- GENERAL TOP LINE EXTRA DATA -->
<?php if(!empty($this->order->extraData->generalTopLine)) { echo implode("\r\n", $this->order->extraData->generalTopLine); } ?>
<!-- EO GENERAL TOP LINE EXTRA DATA -->
<!-- ORDER NUMBER -->
		<tr class="hikashop_order_ordernumber">
			<td class="key"><label><?php echo JText::_('ORDER_NUMBER'); ?></label></td>
			<td><span><?php echo $this->order->order_number; ?></span></td>
		</tr>
<!-- EO ORDER NUMBER -->
<!-- INVOICE NUMBER -->
		<tr class="hikashop_order_invoicenumber">
			<td class="key"><label><?php echo JText::_('INVOICE_NUMBER'); ?></label></td>
			<td><span><?php echo @$this->order->order_invoice_number; ?></span></td>
		</tr>
<!-- EO INVOICE NUMBER -->
<!-- ORDER STATUS -->
		<tr class="hikashop_order_status">
			<td class="key"><label for="data[order][order_status]"><?php echo JText::_('ORDER_STATUS'); ?></label></td>
			<td class="hikashop_order_status"><?php
				if(!isset($this->edit) || $this->edit !== true ) {
					?><span><?php echo hikashop_orderStatus($this->order->order_status); ?></span><?php
				} else {
					$extra = 'onchange="window.orderMgr.status_changed(this);"';
					if(!empty($this->isLocked)) {
						echo $this->order_status->displayLocked('data[order][order_status]', $this->order->order_status, $extra);
					} else {
						echo $this->order_status->display('data[order][order_status]', $this->order->order_status, $extra);
					}
				}
			?></td>
		</tr>
<!-- EO ORDER STATUS -->
<!-- NOTIFY -->
<?php
$checked = '';
if($this->config->get('order_notify_customer_default', 0)) {
	$checked = ' checked=checked';
}
?>
		<tr id="hikashop_order_notify_line" style="display:none;" class="hikashop_order_notify">
			<td class="key"><label for="data[notify]"><?php echo JText::_('NOTIFICATION'); ?></label></td>
			<td><input type="checkbox" id="data[notify]" name="data[notify]" <?php echo $checked;?> /><label style="display:inline-block" for="data[notify]"><?php echo JText::_('NOTIFY_CUSTOMER'); ?></label></td>
		</tr>
<!-- EO NOTIFY -->
<!-- CREATION DATE -->
		<tr class="hikashop_order_created">
			<td class="key"><label><?php echo JText::_('DATE'); ?></label></td>
			<td><span><?php echo hikashop_getDate($this->order->order_created,'%Y-%m-%d %H:%M');?></span></td>
		</tr>
<!-- EO CREATION DATE -->
<!-- ORDER ID -->
		<tr class="hikashop_order_id">
			<td class="key"><label><?php echo JText::_('ID'); ?></label></td>
			<td><span><?php echo $this->order->order_id; ?></span></td>
		</tr>
<!-- EO ORDER ID -->
<!-- ORDER IP -->
<?php
if(!empty($this->order->order_ip) && $this->config->get('order_ip', 1)) {
?>
		<tr class="hikashop_order_ip">
			<td class="key"><label><?php echo JText::_('HIKA_IP'); ?></label></td>
			<td><span><?php echo $this->order->order_ip; ?></span></td>
		</tr>
<?php
}
?>
<!-- EO ORDER IP -->
<!-- HISTORY MESSAGE -->
<?php
if(isset($this->edit) && $this->edit === true ) {
?>
		<tr class="hikashop_order_history">
			<td class="key"><label><?php echo JText::_('HISTORY'); ?></label></td>
			<td>
				<span><input onchange="window.orderMgr.general_history_changed(this);" type="checkbox" id="hikashop_history_general_store" name="data[history][store_data]" value="1"/><label for="hikashop_history_general_store" style="display:inline-block"><?php echo JText::_('SET_HISTORY_MESSAGE');?></label></span><br/>
				<textarea id="hikashop_history_general_msg" name="data[history][msg]" style="display:none;"></textarea>
			</td>
		</tr>
<?php
}
?>
<!-- EO HISTORY MESSAGE -->
<!-- EXTRA DATA -->
<?php
if(!empty($this->extra_data['general'])) {
	foreach($this->extra_data['general'] as $key => $content) {
?>	<tr class="hikashop_order_<?php echo $key; ?>">
		<td class="key"><label><?php echo JText::_($content['title']); ?></label></td>
		<td><?php echo $content['data'] ?></td>
	</tr>
<?php
	}
}
?>
<!-- EO EXTRA DATA -->
<!-- GENERAL BOTTOM LINE EXTRA DATA -->
<?php if(!empty($this->order->extraData->generalBottomLine)) { echo implode("\r\n", $this->order->extraData->generalBottomLine); } ?>
<!-- EO GENERAL BOTTOM LINE EXTRA DATA -->
	</table>
<!-- GENERAL BOTTOM EXTRA DATA -->
<?php if(!empty($this->order->extraData->generalBottom)) { echo implode("\r\n", $this->order->extraData->generalBottom); } ?>
<!-- EO GENERAL BOTTOM EXTRA DATA -->
<?php
if(isset($this->edit) && $this->edit === true ) {
?>
<script type="text/javascript">
window.orderMgr.status_changed = function(el) {
	var fields = ['hikashop_order_notify_line'], displayValue = '';
	if(el.value == '<?php echo $this->order->order_status; ?>')
		displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}

window.orderMgr.saveGeneral = function(el, target) {
	var data = window.Oby.getFormData(target);
	window.hikashop.xRequest(el.getAttribute('href'), {update: target, mode: 'POST', data: data}, function() {
		for(var method in window.orderMgr) {
			if(method.indexOf('update') === 0 && typeof window.orderMgr[method] === 'function') {
				window.orderMgr[method]();
			}
		}
	});
	return false;
}
window.orderMgr.general_history_changed = function(el) {
	var fields = ['hikashop_history_general_msg'], displayValue = '';
	if(!el.checked) displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}
<?php if(!empty($this->extra_data['js'])) { echo $this->extra_data['js']; } ?>
</script>
	<input type="hidden" name="data[general]" value="1"/>
	<?php echo JHTML::_('form.token')."\r\n";
}
