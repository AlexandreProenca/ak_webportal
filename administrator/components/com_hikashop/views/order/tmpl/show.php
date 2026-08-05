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
?><script type="text/javascript">
<!--
window.orderMgr = {
	updateAdditionnal: function(){},
	updateHistory: function(){},
	updateShipping: function(){},
	updateBilling: function(){}
};
<?php if(!empty($this->extra_data['js'])) { echo $this->extra_data['js']; } ?>
//-->
</script>
<div class="iframedoc" id="iframedoc"></div>
<!-- TOP END EXTRA DATA -->
<?php if(!empty($this->order->extraData->topEnd)) { echo implode("\r\n", $this->order->extraData->topEnd); } ?>
<!-- EO TOP END EXTRA DATA -->
<div id="page-order" class="hk-row-fluid hikashop_backend_order_show">
	<div class="hkc-md-6">
			<fieldset class="hika_field adminform" id="hikashop_order_field_general"><?php
				echo $this->loadTemplate('general');
			?></fieldset>
			<fieldset class="hika_field adminform" id="hikashop_order_field_user">
<?php
echo $this->loadTemplate('user');
?>
			</fieldset>
	</div>
	<div class="hkc-md-6">
			<fieldset class="hika_field adminform" id="hikashop_order_field_additional">
<?php
echo $this->loadTemplate('additional');
?>
			</fieldset>
<?php if(!empty($this->order->partner)){ ?>
		<fieldset class="hika_field adminform" id="htmlfieldset_partner">
			<legend><?php echo JText::_('PARTNER'); ?></legend>
				<div class="hika_edit"><?php
					echo $this->popup->display(
						'<i class="fas fa-pen"></i> ' . JText::_('HIKA_EDIT'),
						'HIKA_EDIT',
						hikashop_completeLink('order&task=partner&order_id='.$this->order->order_id,true),
						'hikashop_edit_partner',
						760, 480, 'class="btn btn-primary"', '', 'link'
					);
				?></div>
<!-- PARTNER TOP EXTRA DATA -->
<?php if(!empty($this->order->extraData->partnerTop)) { echo implode("\r\n", $this->order->extraData->partnerTop); } ?>
<!-- EO PARTNER TOP EXTRA DATA -->
				<table class="admintable table">
<!-- PARTNER TOP LINE EXTRA DATA -->
<?php if(!empty($this->order->extraData->partnerTopLine)) { echo implode("\r\n", $this->order->extraData->partnerTopLine); } ?>
<!-- EO PARTNER TOP LINE EXTRA DATA -->
<!-- PARTNER EMAIL -->
					<tr>
						<td class="key"><?php echo JText::_('PARTNER_EMAIL'); ?></td>
						<td>
							<?php echo $this->order->partner->user_email;?>
							<a href="<?php echo hikashop_completeLink('user&task=edit&cid[]='. $this->order->partner->user_id.'&order_id='.$this->order->order_id); ?>">
								<i class="fa fa-chevron-right"></i>
							</a>
						</td>
					</tr>
<!-- EO PARTNER EMAIL -->
<!-- PARTNER NAME -->
<?php if(!empty($this->order->partner->name)){ ?>
					<tr>
						<td class="key"><?php echo JText::_('PARTNER_NAME'); ?></td>
						<td><?php
							echo $this->order->partner->name;
						?></td>
					</tr>
<?php } ?>
<!-- EO PARTNER NAME -->
<!-- PARTNER FEES -->
					<tr>
						<td class="key"><?php echo JText::_('PARTNER_FEE'); ?></td>
						<td><?php echo $this->currencyHelper->format($this->order->order_partner_price,$this->order->order_partner_currency_id); ?></td>
					</tr>
<!-- EO PARTNER FEES -->
<!-- PARTNER PAYMENT STATUS -->
					<tr>
						<td class="key"><?php echo JText::_('PARTNER_PAYMENT_STATUS'); ?></td>
						<td><?php
							if(empty($this->order->order_partner_paid)) {
								echo '<span class="label label-warning">'.JText::_('NOT_PAID').'</span>';
								if(!HIKASHOP_BACK_RESPONSIVE)
									echo ' <i class="fa fa-times-circle"></i>';
							} else {
								echo '<span class="label label-success">'.JText::_('PAID').'</span>';
								if(!HIKASHOP_BACK_RESPONSIVE)
									echo ' <i class="fa fa-check"></i>';
							}
						?></td>
					</tr>
<!-- EO PARTNER PAYMENT STATUS -->
<!-- PARTNER BOTTOM LINE EXTRA DATA -->
<?php if(!empty($this->order->extraData->partnerBottomLine)) { echo implode("\r\n", $this->order->extraData->partnerBottomLine); } ?>
<!-- EO PARTNER BOTTOM LINE EXTRA DATA -->
				</table>
<!-- PARTNER BOTTOM EXTRA DATA -->
<?php if(!empty($this->order->extraData->partnerBottom)) { echo implode("\r\n", $this->order->extraData->partnerBottom); } ?>
<!-- EO PARTNER BOTTOM EXTRA DATA -->
			</fieldset>
<?php } ?>
	</div>
</div>
<!-- BEFORE ADDRESS EXTRA DATA -->
<?php if(!empty($this->order->extraData->beforeAddress)) { echo implode("\r\n", $this->order->extraData->beforeAddress); } ?>
<!-- EO BEFORE ADDRESS EXTRA DATA -->
<div class="hk-row-fluid">
	<div class="hkc-md-6">
			<fieldset class="hika_field adminform" id="hikashop_order_field_billing_address">
<?php
	$this->type = 'billing';
	echo $this->loadTemplate('address');
?>
			</fieldset>
	</div>
	<div class="hkc-md-6">
			<fieldset class="hika_field adminform" id="hikashop_order_field_shipping_address">
<?php
	if(empty($this->order->override_shipping_address)) {
		$this->type = 'shipping';
		echo $this->loadTemplate('address');
	} else {
		echo $this->order->override_shipping_address;
	}

?>
			</fieldset>
	</div>
</div>
<!-- AFTER ADDRESS EXTRA DATA -->
<?php if(!empty($this->order->extraData->afterAddress)) { echo implode("\r\n", $this->order->extraData->afterAddress); } ?>
<!-- EO AFTER ADDRESS EXTRA DATA -->
			<fieldset class="hika_field adminform" id="hikashop_order_products">
<?php
echo $this->loadTemplate('products');
?>
			</fieldset>
<!-- AFTER PRODUCT EXTRA DATA -->
<?php if(!empty($this->order->extraData->afterProduct)) { echo implode("\r\n", $this->order->extraData->afterProduct); } ?>
<!-- EO AFTER PRODUCT EXTRA DATA -->
<?php
	JPluginHelper::importPlugin('hikashop');
	$app = JFactory::getApplication();
	$app->triggerEvent('onAfterOrderProductsListingDisplay', array(&$this->order, 'order_back_show'));
?>
<!-- AFTER PLUGIN EXTRA DATA -->
<?php if(!empty($this->order->extraData->afterPlugin)) { echo implode("\r\n", $this->order->extraData->afterPlugin); } ?>
<!-- EO AFTER PLUGIN EXTRA DATA -->
			<fieldset class="hika_field adminform" id="hikashop_order_field_history">
<?php
echo $this->loadTemplate('history');
?>
			</fieldset>
<!-- AFTER HISTORY EXTRA DATA -->
<?php if(!empty($this->order->extraData->afterHistory)) { echo implode("\r\n", $this->order->extraData->afterHistory); } ?>
<!-- EO AFTER HISTORY EXTRA DATA -->
