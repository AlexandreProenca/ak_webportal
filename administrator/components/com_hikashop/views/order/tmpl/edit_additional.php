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
?><h1><?php echo JText::_('ORDER_ADD_INFO'); ?></h1>
<form action="<?php echo hikashop_completeLink('order&task=save&subtask=additional&tmpl=component'); ?>" name="hikashop_order_additional_form" id="hikashop_order_additional_form" method="post" enctype="multipart/form-data">
<?php 
	if(isset($this->edit) && $this->edit === true) {
?>
<?php
		if(!empty($this->order->additional)) {
?>
	<input type="hidden" name="data[order][additional]" value="1"/>
<?php
		}
?>
	<table class="hikashop_order_additional_table adminlist table table-striped">
		<thead>
			<tr>
				<th class="title">
				</th>
				<th class="title">
					<?php echo JText::_('INFORMATION'); ?>
				</th>
				<th class="title" style="width:120px;">
					<?php echo JText::_('PRICE'); ?>
				</th>
				<th class="title">
					<?php echo JText::_('TAXES'); ?>
				</th>
				<th class="title" style="width:120px;">
					<?php echo JText::_('PRICE_WITH_TAX'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="additional_row_title">
					<?php echo JText::_('SUBTOTAL'); ?>
				</td>
				<td>
				</td>
				<td>
				<?php echo $this->currencyHelper->format($this->order->order_subtotal_no_vat,$this->order->order_currency_id); ?>
				</td>
				<td>
				</td>
				<td>
					<?php echo $this->currencyHelper->format($this->order->order_subtotal,$this->order->order_currency_id); ?>
				</td>
			</tr>
<?php
$currency_id = @$this->order->order_currency_id;
$additionalTaxTypes = array(
	'discount' => array(
		'label' => 'HIKASHOP_COUPON',
		'tax_info' => @$this->order->discount_tax_info,
		'tax_total' => @$this->order->order_discount_tax,
		'price_no_tax' => @$this->order->order_discount_price - @$this->order->order_discount_tax,
		'price_with_tax' => @$this->order->order_discount_price,
		'price_name' => 'data[order][order_discount_price]',
		'tax_name' => 'data[order][order_discount_tax]',
		'info_html' => '<input type="text" name="data[order][order_discount_code]" value="'.$this->escape(@$this->order->order_discount_code).'" />',
	),
	'shipping' => array(
		'label' => 'SHIPPING',
		'tax_info' => @$this->order->shipping_tax_info,
		'tax_total' => @$this->order->order_shipping_tax,
		'price_no_tax' => $this->order->order_shipping_price - @$this->order->order_shipping_tax,
		'price_with_tax' => @$this->order->order_shipping_price,
		'price_name' => 'data[order][order_shipping_price]',
		'tax_name' => 'data[order][order_shipping_tax]',
		'info_html' => (strpos($this->order->order_shipping_id, ';') === false) ? $this->shippingPlugins->display('data[order][shipping]',$this->order->order_shipping_method,$this->order->order_shipping_id) : '',
	),
	'payment' => array(
		'label' => 'HIKASHOP_PAYMENT',
		'tax_info' => @$this->order->payment_tax_info,
		'tax_total' => @$this->order->order_payment_tax,
		'price_no_tax' => $this->order->order_payment_price - @$this->order->order_payment_tax,
		'price_with_tax' => @$this->order->order_payment_price,
		'price_name' => 'data[order][order_payment_price]',
		'tax_name' => 'data[order][order_payment_tax]',
		'info_html' => $this->paymentPlugins->display('data[order][payment]',$this->order->order_payment_method,$this->order->order_payment_id),
	),
);
foreach($additionalTaxTypes as $type => $cfg) {
	$taxRows = array();
	if(!empty($cfg['tax_info']) && is_array($cfg['tax_info'])) {
		$taxRows = $cfg['tax_info'];
	}
	if(empty($taxRows)) {
		$emptyTax = new stdClass();
		$emptyTax->tax_namekey = '-1';
		$emptyTax->tax_amount = (float)$cfg['tax_total'];
		$taxRows[] = $emptyTax;
	}
?>
			<tr>
				<td class="additional_row_title">
					<?php echo JText::_($cfg['label']); ?>
				</td>
				<td>
					<?php echo $cfg['info_html']; ?>
				</td>
				<td>
					<input type="text" style="width:110px;" id="hikashop_order_<?php echo $type; ?>_price_input" name="hikashop_order_<?php echo $type; ?>_price_without_tax_input" onkeyup="window.orderMgr.updateAdditionalTaxes(this, '<?php echo $type; ?>');" value="<?php echo $cfg['price_no_tax']; ?>" />
				</td>
				<td>
					<input type="hidden" id="hikashop_order_<?php echo $type; ?>_tax_input" name="<?php echo $cfg['tax_name']; ?>" value="<?php echo $cfg['tax_total']; ?>" />
					<div id="hikashop_order_<?php echo $type; ?>_tax_rows">
<?php
	foreach($taxRows as $i => $taxInfo) {
?>
						<div class="hikashop_additional_tax_row" data-tax-type="<?php echo $type; ?>" data-tax-index="<?php echo $i; ?>" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:4px;">
							<?php echo $this->ratesType->display('data[order][order_'.$type.'_tax_namekeys]['.$i.']', @$taxInfo->tax_namekey, true, 'onchange="window.orderMgr.updateAdditionalTaxes(this, \''.$type.'\');"', true); ?>
							<span class="hikashop_tax_amount"><?php echo $this->currencyHelper->format(@$taxInfo->tax_amount, $currency_id); ?></span>
							<a href="#" class="btn btn-danger btn-mini hikashop_tax_remove" onclick="return window.orderMgr.removeAdditionalTaxRow(this, '<?php echo $type; ?>');"><i class="fa fa-minus"></i></a>
						</div>
<?php
	}
?>
					</div>
					<a href="#" class="hikabtn hikabtn-primary btn btn-primary btn-mini" onclick="return window.orderMgr.addAdditionalTaxRow('<?php echo $type; ?>');"><i class="fa fa-plus"></i> <?php echo JText::_('ADD'); ?></a>
				</td>
				<td>
					<input type="text" style="width:110px;" id="hikashop_order_<?php echo $type; ?>_price_with_tax_input" name="<?php echo $cfg['price_name']; ?>" onkeyup="window.orderMgr.updateAdditionalTaxes(this, '<?php echo $type; ?>');" value="<?php echo $cfg['price_with_tax']; ?>" />
				</td>
			</tr>
<?php
}
?>
<?php
	if(!empty($this->order->additional)) {
		foreach($this->order->additional as $additional) {
			if(!empty($additional->order_product_price)) {
				$additional->order_product_price = (float)$additional->order_product_price;
			}
			if(!empty($additional->order_product_price) || empty($additional->order_product_options)) {
				$name = 'order_product_price';
				$value = $additional->order_product_price;
			} else {
				$name = 'order_product_options';
				$value = $additional->order_product_options;
			}
?>
			<tr>
				<td class="additional_row_title">
					<?php echo JText::_($additional->order_product_name); ?>
					<input type="hidden" name="data[order][product][<?php echo $additional->order_product_name; ?>][order_product_id]" value="<?php echo $additional->order_product_id; ?>"/>
					<input type="hidden" name="data[order][product][<?php echo $additional->order_product_name; ?>][order_product_code]" value="order additional"/>
					<input type="hidden" name="data[order][product][<?php echo $additional->order_product_name; ?>][order_product_quantity]" value="0"/>
				</td>
				<td>
				</td>
				<td>
				</td>
				<td>
				</td>
				<td>
					<input type="text" name="data[order][product][<?php echo $additional->order_product_name; ?>][<?php echo $name; ?>]" value="<?php echo $value; ?>"/>
				</td>
			</tr>
<?php
		}
	}
?>
			<tr>
				<td class="additional_row_title">
					<?php echo JText::_('HIKASHOP_TOTAL'); ?>
				</td>
				<td>
				</td>
				<td>
				</td>
				<td>
				</td>
				<td>
					<span id="hikashop_order_total_display"><?php echo $this->currencyHelper->format($this->order->order_full_price,$this->order->order_currency_id); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
		if(strpos($this->order->order_shipping_id, ';') !== false) {
?>
			<table class="hikam_table table table-striped">
				<thead>
					<tr>
						<th><?php echo JText::_('WAREHOUSE'); ?></th>
						<th><?php echo JText::_('HIKASHOP_SHIPPING_METHOD'); ?></th>
						<th><?php echo JText::_('SHIPPING_PRICE'); ?></th>
						<th><?php echo JText::_('SHIPPING_TAX'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			$warehouses = array(
				JHTML::_('select.option', 0, JText::_('HIKA_NONE'))
			);
			$shipping_ids = explode(';', $this->order->order_shipping_id);
			foreach($shipping_ids as $shipping_key) {
				$shipping_warehouse = 0;
				if(strpos($shipping_key, '@') !== false)
					list($shipping_id, $shipping_warehouse) = explode('@', $shipping_key, 2);
				else
					$shipping_id = (int)$shipping_key;
				$warehouses[] = JHTML::_('select.option', $shipping_warehouse, $shipping_warehouse);
				$shipping_method = '';
				foreach($this->order->shippings as $s) {
					if((int)$s->shipping_id == $shipping_id) {
						$shipping_method = $s->shipping_type;
						break;
					}
				}
				$k = $shipping_id.'_'.$shipping_warehouse;
				$prices = @$this->order->order_shipping_params->prices[$shipping_key];
?>
					<tr>
						<td><?php echo $shipping_warehouse; ?></td>
						<td><?php echo $this->shippingPlugins->display('data[order][shipping]['.$shipping_warehouse.']',$shipping_method,$shipping_id, true, ' style="max-width:160px;"'); ?></td>
						<td><input type="text" name="data[order][order_shipping_prices][<?php echo $shipping_warehouse; ?>]" value="<?php echo @$prices->price_with_tax; ?>" /></td>
						<td><input type="text" name="data[order][order_shipping_taxs][<?php echo $shipping_warehouse; ?>]" value="<?php echo @$prices->tax; ?>" /></td>
					</tr>
<?php
			}
?>				</tbody>
			</table>
			<table class="hika_table table table-striped">
				<thead>
					<tr>
						<th><?php echo JText::_('PRODUCT'); ?></th>
						<th><?php echo JText::_('WAREHOUSE'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($this->order->products as $k => $product) {
				$map = 'data[order][warehouses]['.$product->order_product_id.']';
				$value = 0;
				if(strpos($product->order_product_shipping_id, '@') !== false)
					$value = substr($product->order_product_shipping_id, strpos($product->order_product_shipping_id, '@')+1);
?>
					<tr>
						<td><?php echo $product->order_product_name; ?></td>
						<td><?php echo JHTML::_('select.genericlist', $warehouses, $map, 'class="custom-select"', 'value', 'text', $value); ?></td>
					</tr>
<?php
			}
?>
				</tbody>
			</table>
<?php
	} ?>
	<dl class="hika_options">
<?php } else { ?>
	<dl class="hika_options">
		<dt class="hikashop_order_additional_subtotal"><label><?php echo JText::_('SUBTOTAL'); ?></label></dt>
		<dd class="hikashop_order_additional_subtotal"><span><?php echo $this->currencyHelper->format($this->order->order_subtotal,$this->order->order_currency_id); ?></span></dd>
		<dt class="hikashop_order_additional_coupon"><label><?php echo JText::_('HIKASHOP_COUPON'); ?></label></dt>
		<dd class="hikashop_order_additional_coupon"><span><?php echo $this->currencyHelper->format($this->order->order_discount_price*-1.0,$this->order->order_currency_id); ?> <?php echo $this->order->order_discount_code; ?></span></dd>
		<dt class="hikashop_order_additional_shipping"><label><?php echo JText::_('SHIPPING'); ?></label></dt>
		<dd class="hikashop_order_additional_shipping"><span><?php echo $this->currencyHelper->format($this->order->order_shipping_price, $this->order->order_currency_id); ?> - <?php
			if(empty($this->order->order_shipping_method))
				echo '<em>'.JText::_('HIKA_NONE').'</em>';
			else
				echo $this->order->order_shipping_method;
			?></span></dd>
		<dt class="hikashop_order_additional_payment_fee"><label><?php echo JText::_('HIKASHOP_PAYMENT'); ?></label></dt>
		<dd class="hikashop_order_additional_payment_fee"><span><?php echo $this->currencyHelper->format($this->order->order_payment_price, $this->order->order_currency_id); ?> - <?php
			if(empty($this->order->order_payment_method))
				echo '<em>'.JText::_('HIKA_NONE').'</em>';
			else
				echo $this->order->order_payment_method;
			?></span></dd>
		<dt class="hikashop_order_additional_total"><label><?php echo JText::_('HIKASHOP_TOTAL'); ?></label></dt>
		<dd class="hikashop_order_additional_total"><span><?php echo $this->currencyHelper->format($this->order->order_full_price,$this->order->order_currency_id); ?></span></dd>
<?php }
?>
<?php
	if(!empty($this->extra_data['additional'])) {
		foreach($this->extra_data['additional'] as $key => $content) {
?>		<dt class="hikashop_order_additional_<?php echo $key; ?>"><label><?php echo JText::_($content['title']); ?></label></dt>
		<dd class="hikashop_order_additional_<?php echo $key; ?>"><span><?php echo $content['data']; ?></span></dd>
<?php
		}
	}

	if(!empty($this->fields['order'])) {
		$editCustomFields = false;
		if(isset($this->edit) && $this->edit === true) {
			$editCustomFields = true;
		}
		$after = array();
		foreach($this->fields['order'] as $fieldName => $oneExtraField) {
?>
		<dt class="hikashop_order_additional_customfield hikashop_order_additional_customfield_<?php echo $fieldName; ?>"><?php echo $this->fieldsClass->getFieldName($oneExtraField);?></dt>
		<dd class="hikashop_order_additional_customfield hikashop_order_additional_customfield_<?php echo $fieldName; ?>"><span><?php
			if($editCustomFields) {
				$html = $this->fieldsClass->display($oneExtraField, @$this->order->$fieldName, 'data[orderfields]['.$fieldName.']');
				if($oneExtraField->field_type=='hidden') {
					$after[] = $thml;
					continue;
				}
				echo $html;
			} else {
				$oneExtraField->currentElement = $this->order;
				echo $this->fieldsClass->show($oneExtraField, @$this->order->$fieldName);
			}
		?></span></dd>
<?php
		}
		if(count($after)) {
			echo implode("\r\n", $after);
		}
	}

?>
		<dt class="hikashop_orderadditional_history"><label><?php echo JText::_('HISTORY'); ?></label></dt>
		<dd class="hikashop_orderadditional_history">
			<span><input onchange="window.orderMgr.orderadditional_history_changed(this);" type="checkbox" id="hikashop_history_orderadditional_store" name="data[history][store_data]" value="1"/><label for="hikashop_history_orderadditional_store" style="display:inline-block"><?php echo JText::_('SET_HISTORY_MESSAGE');?></label></span><br/>
			<textarea id="hikashop_history_orderadditional_msg" name="data[history][msg]" style="display:none;"></textarea>
		</dd>
		<dd class="hikashop_orderadditional_usermsg">
			<span><input onchange="window.orderMgr.orderadditional_usermsg_changed(this);" type="checkbox" id="hikashop_history_orderadditional_usermsg_send" name="data[history][usermsg_send]" value="1"/><label for="hikashop_history_orderadditional_usermsg_send" style="display:inline-block"><?php echo JText::_('SEND_USER_MESSAGE');?></label></span><br/>
			<textarea id="hikashop_history_orderadditional_usermsg" name="data[history][usermsg]" style="display:none;"></textarea>
		</dd>

		<a href="#save" class="btn btn-success" onclick="document.getElementById('hikashop_order_notify').value = 1;return window.hikashop.submitform('save','hikashop_order_additional_form');"><i class="fa fa-save"></i> <?php echo JText::_('HIKA_SAVE_AND_NOTIFY'); ?></a>
		<a href="#save" class="btn btn-success" onclick="return window.hikashop.submitform('save','hikashop_order_additional_form');"><i class="fa fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?></a>

<script type="text/javascript">
if(!window.orderMgr)
	window.orderMgr = {};
window.orderMgr.orderadditional_history_changed = function(el) {
	var fields = ['hikashop_history_orderadditional_msg'], displayValue = '';
	if(!el.checked) displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}
window.orderMgr.orderadditional_usermsg_changed = function(el) {
	var fields = ['hikashop_history_orderadditional_usermsg'], displayValue = '';
	if(!el.checked) displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}
<?php
$additionalTaxTemplates = array();
$additionalTaxIndexes = array();
foreach($additionalTaxTypes as $type => $cfg) {
	$taxRows = !empty($cfg['tax_info']) ? $cfg['tax_info'] : array();
	$additionalTaxIndexes[$type] = max(1, count($taxRows));
	$additionalTaxTemplates[$type] = $this->ratesType->display('data[order][order_'.$type.'_tax_namekeys][__IDX__]', '-1', true, 'onchange="window.orderMgr.updateAdditionalTaxes(this, \''.$type.'\');"', true);
}
?>
window.orderMgr.additionalTaxTemplates = <?php echo json_encode($additionalTaxTemplates); ?>;
window.orderMgr.additionalTaxIndexes = <?php echo json_encode($additionalTaxIndexes); ?>;
window.orderMgr.orderSubtotal = <?php echo (float)@$this->order->order_subtotal; ?>;

window.orderMgr.updateAdditionalTotal = function() {
	var subtotal = window.orderMgr.orderSubtotal;
	var shipping = parseFloat(document.getElementById('hikashop_order_shipping_price_with_tax_input').value.replace(",", ".")) || 0;
	var payment = parseFloat(document.getElementById('hikashop_order_payment_price_with_tax_input').value.replace(",", ".")) || 0;
	var discount = parseFloat(document.getElementById('hikashop_order_discount_price_with_tax_input').value.replace(",", ".")) || 0;
	var total = subtotal + shipping + payment - discount;
	var el = document.getElementById('hikashop_order_total_display');
	if(el) {
		window.Oby.xRequest(
			'index.php?option=com_hikashop&tmpl=component&ctrl=product&task=formatprice&price=' + total + '&currency=<?php echo (int)@$this->order->order_currency_id; ?>',
			{ mode: 'GET' },
			function(result) { if(result.responseText) el.innerHTML = result.responseText; }
		);
	}
}

window.orderMgr.getAdditionalSelectedKeys = function(type) {
	var rows = document.querySelectorAll('#hikashop_order_'+type+'_tax_rows .hikashop_additional_tax_row');
	var keys = [];
	for(var i = 0; i < rows.length; i++) {
		var select = rows[i].querySelector('select');
		if(select && select.value && select.value != '-1')
			keys.push(select.value);
	}
	return keys;
}
window.orderMgr.addAdditionalTaxRow = function(type) {
	var container = document.getElementById('hikashop_order_'+type+'_tax_rows');
	var idx = window.orderMgr.additionalTaxIndexes[type]++;
	var html = window.orderMgr.additionalTaxTemplates[type].replace(/__IDX__/g, idx);
	var div = document.createElement('div');
	div.className = 'hikashop_additional_tax_row';
	div.setAttribute('data-tax-type', type);
	div.setAttribute('data-tax-index', idx);
	div.setAttribute('style', 'display:inline-flex;align-items:center;gap:4px;margin-bottom:4px;');
	div.innerHTML = html +
		' <span class="hikashop_tax_amount">0</span>' +
		' <a href="#" class="btn btn-danger btn-mini hikashop_tax_remove" onclick="return window.orderMgr.removeAdditionalTaxRow(this, \''+type+'\');"><i class="fa fa-minus"></i></a>';
	container.appendChild(div);
	window.orderMgr.updateAdditionalDisabledOptions(type);
	return false;
}
window.orderMgr.removeAdditionalTaxRow = function(el, type) {
	var row = el.closest('.hikashop_additional_tax_row');
	if(row) {
		row.parentNode.removeChild(row);
		window.orderMgr.updateAdditionalDisabledOptions(type);
		window.orderMgr.updateAdditionalTaxes(document.getElementById('hikashop_order_'+type+'_price_input'), type);
	}
	return false;
}
window.orderMgr.updateAdditionalDisabledOptions = function(type) {
	var selected = window.orderMgr.getAdditionalSelectedKeys(type);
	var rows = document.querySelectorAll('#hikashop_order_'+type+'_tax_rows .hikashop_additional_tax_row');
	for(var i = 0; i < rows.length; i++) {
		var select = rows[i].querySelector('select');
		if(!select) continue;
		for(var j = 0; j < select.options.length; j++) {
			var opt = select.options[j];
			if(opt.value == '-1') continue;
			opt.disabled = (selected.indexOf(opt.value) !== -1 && opt.value != select.value);
		}
	}
}
window.orderMgr.updateAdditionalTaxes = function(el, type) {
	window.orderMgr.updateAdditionalDisabledOptions(type);
	var priceInput = document.getElementById('hikashop_order_'+type+'_price_input');
	var priceWithTaxInput = document.getElementById('hikashop_order_'+type+'_price_with_tax_input');
	var taxInput = document.getElementById('hikashop_order_'+type+'_tax_input');
	var rows = document.querySelectorAll('#hikashop_order_'+type+'_tax_rows .hikashop_additional_tax_row');

	var price = parseFloat(priceInput.value.replace(",", ".")) || 0;
	var conversion = 0;
	if(el.id == priceWithTaxInput.id) {
		conversion = 1;
		price = parseFloat(priceWithTaxInput.value.replace(",", ".")) || 0;
	}

	var rateKeys = [];
	for(var i = 0; i < rows.length; i++) {
		var select = rows[i].querySelector('select');
		if(select && select.value && select.value != '-1')
			rateKeys.push(select.value);
	}

	if(rateKeys.length == 0) {
		if(conversion) priceInput.value = price;
		else priceWithTaxInput.value = price;
		taxInput.value = 0;
		for(var i = 0; i < rows.length; i++) {
			var span = rows[i].querySelector('.hikashop_tax_amount');
			if(span) span.textContent = '0';
		}
		window.orderMgr.updateAdditionalTotal();
		return;
	}

	var url = 'index.php?option=com_hikashop&tmpl=component&ctrl=product&task=getprice&nofloat=1&price=' + price + '&conversion=' + conversion + '&currency=<?php echo (int)@$this->order->order_currency_id; ?>';
	for(var i = 0; i < rateKeys.length; i++) {
		url += '&rate_namekeys[]=' + encodeURIComponent(rateKeys[i]);
	}

	window.Oby.xRequest(url, { mode: 'GET'}, function(result) {
		if(result.responseText) {
			try {
				var data = JSON.parse(result.responseText);
				if(conversion) priceInput.value = data.price;
				else priceWithTaxInput.value = data.price_with_tax;
				taxInput.value = data.total_tax;
				var taxIdx = 0;
				for(var i = 0; i < rows.length; i++) {
					var select = rows[i].querySelector('select');
					var span = rows[i].querySelector('.hikashop_tax_amount');
					if(select && select.value && select.value != '-1' && span && data.taxes[taxIdx] !== undefined) {
						span.innerHTML = data.taxes[taxIdx].tax_amount_formatted || data.taxes[taxIdx].tax_amount;
						taxIdx++;
					} else if(span) {
						span.textContent = '0';
					}
				}
			} catch(e) {}
		}
		window.orderMgr.updateAdditionalTotal();
	});
}
<?php if(!empty($this->extra_data['js'])) { echo $this->extra_data['js']; } ?>
</script>
	</dl>
	<input type="hidden" name="data[notify]" id="hikashop_order_notify" value="0" />
	<input type="hidden" name="data[additional]" value="1" />
	<input type="hidden" name="data[customfields]" value="1" />
	<input type="hidden" name="cid[]" value="<?php echo @$this->order->order_id; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="subtask" value="additional" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="ctrl" value="order" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
