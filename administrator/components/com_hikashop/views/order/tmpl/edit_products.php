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
?><h1><?php echo JText::_('PRODUCT'); ?>
<?php
	if(!empty($this->orderProduct->product_id))
		echo ' : ' . (int)@$this->orderProduct->product_id . ' - ' . @$this->originalProduct->product_name;
?>
</h1>
<form action="<?php echo hikashop_completeLink('order&task=save&subtask=products&tmpl=component'); ?>" name="hikashop_order_product_form" id="hikashop_order_product_form" method="post" enctype="multipart/form-data">
	<dl class="hika_options">
		<dt class="hikashop_order_product_name"><label><?php echo JText::_('HIKA_NAME'); ?></label></dt>
		<dd class="hikashop_order_product_name">
			<input type="text" name="data[order][product][order_product_name]" value="<?php echo $this->escape(@$this->orderProduct->order_product_name); ?>" />
		</dd>

		<dt class="hikashop_order_product_code"><label><?php echo JText::_('PRODUCT_CODE'); ?></label></dt>
		<dd class="hikashop_order_product_code">
			<input type="text" name="data[order][product][order_product_code]" value="<?php echo $this->escape(@$this->orderProduct->order_product_code); ?>" />
		</dd>

		<dt class="hikashop_order_product_quantity"><label><?php echo JText::_('PRODUCT_QUANTITY'); ?></label></dt>
		<dd class="hikashop_order_product_quantity">
			<input type="text" name="data[order][product][order_product_quantity]" value="<?php echo @$this->orderProduct->order_product_quantity; ?>"
<?php
	if(!empty($this->allPrices)) {
		$data = array();
		foreach($this->allPrices as $price) {
			$data[] = array((int)$price->price_min_quantity, round($price->price_value,5));
		}
		if(count($data)){
			echo ' data-prices="'.json_encode($data).'" onchange="window.orderMgr.recalculatePrice(this);"';
		}
	}
?>
			/>
		</dd>
		<dt class="hikashop_order_product_price"><label><?php echo JText::_('UNIT_PRICE'); ?></label></dt>
		<dd class="hikashop_order_product_price">
			<input type="text" id="hikashop_order_product_price_input" onkeyup="window.orderMgr.updateTaxes(this);" onchange="window.orderMgr.updateTaxes(this);" name="data[order][product][order_product_price]" value="<?php echo @$this->orderProduct->order_product_price; ?>" />
		</dd>

		<dt class="hikashop_order_product_vat"><label><?php echo JText::_('VAT'); ?></label></dt>
		<dd class="hikashop_order_product_vat">
			<input type="hidden" id="hikashop_order_product_tax_input" name="data[order][product][order_product_tax]" value="<?php echo @$this->orderProduct->order_product_tax; ?>" />
			<div id="hikashop_order_product_tax_rows">
<?php
			$taxRows = array();
			if(!empty($this->orderProduct->order_product_tax_info) && is_array($this->orderProduct->order_product_tax_info)) {
				foreach($this->orderProduct->order_product_tax_info as $taxInfo) {
					$taxRows[] = $taxInfo;
				}
			}
			if(empty($taxRows)) {
				$emptyTax = new stdClass();
				$emptyTax->tax_namekey = '-1';
				$emptyTax->tax_amount = (float)@$this->orderProduct->order_product_tax;
				$taxRows[] = $emptyTax;
			}
			$currency_id = @$this->order->order_currency_id;
			foreach($taxRows as $i => $taxInfo) {
?>
				<div class="hikashop_tax_row" data-tax-index="<?php echo $i; ?>" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:4px;">
					<?php echo $this->ratesType->display('data[order][product][tax_namekeys]['.$i.']', @$taxInfo->tax_namekey, true, 'onchange="window.orderMgr.updateTaxes(this);"', true); ?>
					<span class="hikashop_tax_amount"><?php echo $this->currencyHelper->format(@$taxInfo->tax_amount, $currency_id); ?></span>
					<a href="#" class="btn btn-danger btn-mini hikashop_tax_remove" onclick="return window.orderMgr.removeTaxRow(this);"><i class="fa fa-minus"></i></a>
				</div>
<?php
			}
?>
			</div>
			<a href="#" class="hikabtn hikabtn-primary btn btn-primary btn-mini" onclick="return window.orderMgr.addTaxRow();"><i class="fa fa-plus"></i> <?php echo JText::_('ADD'); ?></a>
		</dd>
		<dt class="hikashop_order_product_price"><label><?php echo JText::_('PRICE_WITH_TAX'); ?></label></dt>
		<dd class="hikashop_order_product_price">
			<input type="text" id="hikashop_order_product_price_with_tax_input" onkeyup="window.orderMgr.updateTaxes(this);" name="order_product_price_with_tax" value="<?php echo (@$this->orderProduct->order_product_price + @$this->orderProduct->order_product_tax); ?>" />
		</dd>

		<dt class="hikashop_order_product_weight"><label><?php echo JText::_('PRODUCT_WEIGHT'); ?></label></dt>
		<dd class="hikashop_order_product_weight">
			<input type="text" id="hikashop_order_product_weight_input" style="width:120px;" name="data[order][product][order_product_weight]" value="<?php echo @$this->orderProduct->order_product_weight; ?>" />
			<?php echo $this->weightType->display( "data[order][product][order_product_weight_unit]" , @$this->orderProduct->order_product_weight_unit, '', 'style="width:80px; margin-bottom:9px"'); ?>
		</dd>
		<dt class="hikashop_order_product_width"><label><?php echo JText::_('PRODUCT_WIDTH'); ?></label></dt>
		<dd class="hikashop_order_product_width">
			<input type="text" id="hikashop_order_product_width_input" name="data[order][product][order_product_width]" value="<?php echo @$this->orderProduct->order_product_width; ?>" />
		</dd>
		<dt class="hikashop_order_product_length"><label><?php echo JText::_('PRODUCT_LENGTH'); ?></label></dt>
		<dd class="hikashop_order_product_length">
			<input type="text" id="hikashop_order_product_length_input" name="data[order][product][order_product_length]" value="<?php echo @$this->orderProduct->order_product_length; ?>" />
		</dd>
		<dt class="hikashop_order_product_height"><label><?php echo JText::_('PRODUCT_HEIGHT'); ?></label></dt>
		<dd class="hikashop_order_product_height">
			<input type="text" id="hikashop_order_product_height_input" name="data[order][product][order_product_height]" value="<?php echo @$this->orderProduct->order_product_height; ?>" />
		</dd>
		<dt class="hikashop_order_product_dimension_unit"><label><?php echo JText::_('DIMENSIONS_UNIT'); ?></label></dt>
		<dd class="hikashop_order_product_dimension_unit">
			<?php echo $this->volumeType->display( "data[order][product][order_product_dimension_unit]" , @$this->orderProduct->order_product_dimension_unit); ?>
		</dd>


<?php
	if(!empty($this->extra_data['products'])) {
		foreach($this->extra_data['products'] as $key => $content) {
?>		<dt class="hikashop_order_product_<?php echo $key; ?>"><label><?php echo JText::_($content['title']); ?></label></dt>
		<dd class="hikashop_order_product_<?php echo $key; ?>"><?php echo $content['data']; ?></dd>
<?php
		}
	}

	if(!empty($this->fields['item'])) {
		$editCustomFields = true;
		$after = array();
		foreach($this->fields['item'] as $fieldName => $oneExtraField) {
?>
		<dt class="hikashop_order_product_customfield hikashop_order_product_customfield_<?php echo $fieldName; ?>"><?php echo $this->fieldsClass->getFieldName($oneExtraField);?></dt>
		<dd class="hikashop_order_product_customfield hikashop_order_product_customfield_<?php echo $fieldName; ?>"><span><?php
			if($editCustomFields) {
				$html = $this->fieldsClass->display($oneExtraField, @$this->orderProduct->$fieldName, 'data[order][product]['.$fieldName.']',false,'',true);
				if($oneExtraField->field_type=='hidden') {
					$after[] = $html;
					continue;
				}
				echo $html;
			} else {
				$oneExtraField->currentElement = $this->orderProduct;
				echo $this->fieldsClass->show($oneExtraField, @$this->orderProduct->$fieldName);
			}
		?></span></dd>
<?php
		}
		if(count($after)) {
			echo implode("\r\n", $after);
		}
	}
?>
		<dt class="hikashop_orderproduct_history"><label><?php echo JText::_('HISTORY'); ?></label></dt>
		<dd class="hikashop_orderproduct_history">
			<span><input onchange="window.orderMgr.orderproduct_history_changed(this);" type="checkbox" id="hikashop_history_orderproduct_store" name="data[history][store_data]" value="1"/><label for="hikashop_history_orderproduct_store" style="display:inline-block"><?php echo JText::_('SET_HISTORY_MESSAGE');?></label></span><br/>
			<textarea id="hikashop_history_orderproduct_msg" name="data[history][msg]" style="display:none;"></textarea>
		</dd>
<?php
$templateSelect = $this->ratesType->display('data[order][product][tax_namekeys][__IDX__]', '-1', true, 'onchange="window.orderMgr.updateTaxes(this);"', true);
?>
<script type="text/javascript">
if(!window.orderMgr)
	window.orderMgr = {};
window.orderMgr.taxRowTemplate = <?php echo json_encode($templateSelect); ?>;
window.orderMgr.taxRowIndex = <?php echo count($taxRows); ?>;

window.orderMgr.recalculatePrice = function(el) {
	var qty = parseInt(el.value);
	if (isNaN(qty))
		return;
	var priceInput = document.getElementById('hikashop_order_product_price_input');
	if(!priceInput)
		return;
	var prices = el.getAttribute('data-prices');
	if(!prices)
		return;
	prices = JSON.parse(prices);
	if(!prices)
		return;
	var priceToUse = 0;
	var minQty = 0;
	for (var i = 0; i < prices.length; i++) {
		var price = prices[i];
		if(price[0] <= qty && (price[0] > minQty || minQty == 0)) {
			minQty = price[0];
			priceToUse = price[1];
		}
	}
	if(priceToUse) {
		priceInput.value = priceToUse;
		priceInput.dispatchEvent(new Event('change'));
	}
}
window.orderMgr.getSelectedTaxKeys = function() {
	var rows = document.querySelectorAll('#hikashop_order_product_tax_rows .hikashop_tax_row');
	var keys = [];
	for(var i = 0; i < rows.length; i++) {
		var select = rows[i].querySelector('select');
		if(select && select.value && select.value != '-1')
			keys.push(select.value);
	}
	return keys;
}
window.orderMgr.addTaxRow = function() {
	var container = document.getElementById('hikashop_order_product_tax_rows');
	var idx = window.orderMgr.taxRowIndex++;
	var html = window.orderMgr.taxRowTemplate.replace(/__IDX__/g, idx);
	var div = document.createElement('div');
	div.className = 'hikashop_tax_row';
	div.setAttribute('data-tax-index', idx);
	div.setAttribute('style', 'display:inline-flex;align-items:center;gap:4px;margin-bottom:4px;');
	div.innerHTML = html +
		' <span class="hikashop_tax_amount">0</span>' +
		' <a href="#" class="btn btn-danger btn-mini hikashop_tax_remove" onclick="return window.orderMgr.removeTaxRow(this);"><i class="fa fa-minus"></i></a>';
	container.appendChild(div);
	window.orderMgr.updateDisabledOptions();
	return false;
}
window.orderMgr.updateDisabledOptions = function() {
	var selected = window.orderMgr.getSelectedTaxKeys();
	var rows = document.querySelectorAll('#hikashop_order_product_tax_rows .hikashop_tax_row');
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
window.orderMgr.removeTaxRow = function(el) {
	var row = el.closest('.hikashop_tax_row');
	if(row) {
		row.parentNode.removeChild(row);
		window.orderMgr.updateDisabledOptions();
		window.orderMgr.updateTaxes(document.getElementById('hikashop_order_product_price_input'));
	}
	return false;
}
window.orderMgr.updateTaxes = function(el) {
	window.orderMgr.updateDisabledOptions();
	var priceInput = document.getElementById('hikashop_order_product_price_input');
	var priceWithTaxInput = document.getElementById('hikashop_order_product_price_with_tax_input');
	var taxInput = document.getElementById('hikashop_order_product_tax_input');
	var rows = document.querySelectorAll('#hikashop_order_product_tax_rows .hikashop_tax_row');

	var price = parseFloat(priceInput.value.replace(",", ".")) || 0;
	var conversion = 0;

	if(el.id == priceWithTaxInput.id) {
		conversion = 1;
		price = parseFloat(priceWithTaxInput.value.replace(",", ".")) || 0;
	}

	var rateKeys = [];
	var selects = [];
	for(var i = 0; i < rows.length; i++) {
		var select = rows[i].querySelector('select');
		if(select && select.value && select.value != '-1') {
			rateKeys.push(select.value);
			selects.push(select);
		}
	}

	if(rateKeys.length == 0) {
		if(conversion) {
			priceInput.value = price;
		} else {
			priceWithTaxInput.value = price;
		}
		taxInput.value = 0;
		for(var i = 0; i < rows.length; i++) {
			var span = rows[i].querySelector('.hikashop_tax_amount');
			if(span) span.textContent = '0';
		}
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
				if(conversion) {
					priceInput.value = data.price;
				} else {
					priceWithTaxInput.value = data.price_with_tax;
				}
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
	});
}
window.orderMgr.orderproduct_history_changed = function(el) {
	var fields = ['hikashop_history_orderproduct_msg'], displayValue = '';
	if(!el.checked) displayValue = 'none';
	window.hikashop.setArrayDisplay(fields, displayValue);
}
<?php if(!empty($this->extra_data['js'])) { echo $this->extra_data['js']; } ?>
</script>
	</dl>
<div style="clear:both;"></div>
	<a class="btn btn-success" href="#save" onclick="return window.hikashop.submitform('save','hikashop_order_product_form');"><i class="fa fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?></a>
	<input type="hidden" name="data[order][history][history_type]" value="modification" />
	<input type="hidden" name="data[order][product][order_product_id]" value="<?php echo @$this->orderProduct->order_product_id;?>" />
	<input type="hidden" name="data[order][product][product_id]" value="<?php echo @$this->orderProduct->product_id;?>" />
	<input type="hidden" name="data[order][product][order_id]" value="<?php echo @$this->orderProduct->order_id;?>" />
<?php
	if(!empty($this->afterParams->parent_id)) {
?>
	<input type="hidden" name="data[order][product][order_product_option_parent_id]" value="<?php echo $this->afterParams->parent_id;?>" />
<?php
	}
?>
	<input type="hidden" name="data[products]" value="1" />
	<input type="hidden" name="cid[]" value="<?php echo @$this->orderProduct->order_id; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="subtask" value="products" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="ctrl" value="order" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
