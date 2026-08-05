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
?><h2><?php echo JText::sprintf('PAY_ORDER_X_NOW', $this->order->order_number); ?></h2>
<?php
global $Itemid;
$url_itemid = (!empty($Itemid) ? '&Itemid=' . $Itemid : '');
$order_id = $this->order->order_id;
$availablePayments = $this->paymentPluginType->methods['payment'][$order_id];
$currencyClass = hikashop_get('class.currency');
?>
<form class="hikashop_pay_form" action="<?php echo hikashop_completeLink('order&task=pay&order_id='.$this->order->order_id.$url_itemid); ?>" method="post">
<!-- TOTAL AMOUNT -->
<?php if($this->payment_selector == 1) { ?>
<div class="hika_options large">
	<legend><?php echo JText::_('HIKASHOP_TOTAL'); ?></legend>
	<span><?php
		echo $this->currencyClass->format($this->order->order_full_price, $this->order->order_currency_id);
	?></span>
</div>
<?php } else { ?>
<dl class="hika_options large">
	<dt><?php echo JText::_('HIKASHOP_TOTAL'); ?></dt>
	<dd><?php
		echo $this->currencyClass->format($this->order->order_full_price, $this->order->order_currency_id);
	?></dd>
</dl>
<?php } ?>
<!-- EO TOTAL AMOUNT -->
<!-- PAYMENT METHOD -->
<?php if(empty($this->new_payment_method)) { ?>
	<?php if($this->payment_selector == 1) { ?>
<legend><?php echo JText::_('PAYMENT_METHOD'); ?></legend>
<table style="width:100%" class="hikashop_payment_methods_table table table-bordered table-striped table-hover">
	<tbody>
<?php
	foreach ($availablePayments as $key => $payment) {
		$selected = $payment->payment_id == $this->order->order_payment_id ? 'checked="checked"' : "";
?>	<tr><td>
		<input id="payment_radio_<?php echo $payment->payment_id; ?>" class="hikashop_paylater_payment_radio" type="radio" name="new_payment_method" 
		value="<?php echo $payment->payment_type;?>_<?php echo $payment->payment_id;?>" <?php echo $selected; ?>/>
		<label for="payment_radio_<?php echo $payment->payment_id; ?>" style="cursor:pointer;">
			<span class="hikashop_paylater_payment_name"><?php echo $payment->payment_name; ?></span>
		</label>
		<span class="hikashop_checkout_payment_cost">
<?php	if ($payment->payment_price != 0)
			echo $currencyClass->format($payment->payment_price, $this->order->order_currency_id);
?>		</span>
		<span class="hikashop_checkout_payment_images">
<?php	
		$images = explode(',', $payment->payment_images);
		foreach($images as $image) {
			$img = $this->checkoutHelper->getPluginImage($image, 'payment');
			if(empty($img))
				continue;
?>			<img src="<?php echo $img->url; ?>" alt=""/>
<?php	} 
?>		</span>
		<div class="hikashop_checkout_payment_description">
			<?php echo $payment->payment_description; ?>
		</div>		
	</td></tr>
<?php
	}
?>
	</tbody>
</table>
	<?php } else { ?>
<dl class="hika_options large">
	<dt><?php echo JText::_('PAYMENT_METHOD'); ?></dt>
	<dd><?php
		echo $this->paymentPluginType->display('new_payment_method', $this->order->order_payment_method, $this->order->order_payment_id, false);
	?></dd>
</dl>
	<?php } ?>
<?php } ?>
<!-- EO PAYMENT METHOD -->
<div class="hikashop_checkout_buttons">
	<div class="buttons_right">
<!-- NEXT BUTTON -->
		<button type="submit" class="btn btn-primary"><?php echo JText::_('HIKA_NEXT'); ?></button>
<!-- EO NEXT BUTTON -->
	</div>
	<div style="clear:both;"></div>
</div>
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="pay" />
	<input type="hidden" name="order_id" value="<?php echo $this->order->order_id; ?>" />
	<input type="hidden" name="ctrl" value="<?php echo hikaInput::get()->getCmd('ctrl'); ?>" />
	<input type="hidden" name="order_token" value="<?php echo htmlspecialchars(hikaInput::get()->getString('order_token', ''), ENT_QUOTES, 'UTF-8'); ?>" />
	<?php echo JHTML::_('form.token'); ?>
</form>
