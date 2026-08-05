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
?><div class="hikashop_generate_copies">
	<h3><?php echo JText::_('GENERATE_SHIPPING_COPIES'); ?></h3>

	<div class="restriction_selector" style="margin-bottom: 20px;">
		<label><?php echo JText::_('SELECT_RESTRICTION_TYPE'); ?>:</label>
		<select id="restriction_type" class="custom-select" onchange="changeRestrictionType(this);" data-prev="zone">
			<option value="zone"><?php echo JText::_('ZONE'); ?></option>
			<option value="weight"><?php echo JText::_('PRODUCT_WEIGHT'); ?></option>
			<option value="volume"><?php echo JText::_('PRODUCT_VOLUME'); ?></option>
			<option value="postcoderegex"><?php echo JText::_('SHIPPING_ZIP_REGEX'); ?></option>
			<option value="warehouse"><?php echo JText::_('WAREHOUSE'); ?></option>
			<option value="quantity"><?php echo JText::_('PRODUCT_QUANTITY'); ?></option>
			<option value="price"><?php echo JText::_('PRICE'); ?></option>
			<option value="currency"><?php echo JText::_('CURRENCY'); ?></option>
		</select>
	</div>

	<table class="table table-striped" id="generator_table">
		<thead>
			<tr>
				<th width="30%"><?php echo JText::_('RESTRICTION_TYPE'); ?></th>
				<th><?php echo JText::_('PRICE'); ?></th>
				<th><?php echo JText::_('DISCOUNT_PERCENT_AMOUNT'); ?></th>
				<th><?php echo JText::_('HIKA_FORMULA'); ?></th>
				<th width="50"></th>
			</tr>
		</thead>
		<tbody id="generator_rows">
			<!-- Rows will be added here -->
		</tbody>
	</table>

	<div class="btn-group" style="margin-top: 10px;">
		<button type="button" class="btn btn-success" onclick="addGeneratorRow();">
			<i class="icon-plus"></i> <?php echo JText::_('ADD_ROW'); ?>
		</button>
	</div>

	<div style="margin-top: 30px; text-align: center;">
		<button type="button" class="btn btn-primary btn-large" onclick="processGeneration();">
			<?php echo JText::_('GENERATE_COPIES'); ?>
		</button>
	</div>
</div>

<script type="text/javascript">
var rowCount = 0;
var defaultPrice = <?php echo json_encode($this->default_price); ?>;
var defaultPercentage = <?php echo json_encode($this->default_percentage); ?>;
var defaultFormula = <?php echo json_encode((string)$this->default_formula); ?>;
var shippingIds = <?php echo json_encode($this->ids); ?>;

function addGeneratorRow() {
	var tbody = document.getElementById('generator_rows');
	var tr = document.createElement('tr');
	tr.id = 'gen_row_' + rowCount;

	var type = document.getElementById('restriction_type').value;

	var html = '';

	html += '<td>';
	html += '<input type="hidden" name="rows[' + rowCount + '][type]" value="' + type + '" />';

	var useContainer = (type == 'zone' || type == 'warehouse' || type == 'currency');

	if(useContainer) {
		html += '<div id="container_' + rowCount + '"></div>';
	} else {
		var tplId = type + '_template';
		if(type == 'postcoderegex') tplId = 'postcoderegex_template';

		var tplElement = document.getElementById(tplId);
		if(tplElement) {
			var tpl = tplElement.innerHTML;
			html += tpl.replace(/template_id/g, rowCount);
		}
	}
	html += '</td>';

	html += '<td><input type="text" name="rows[' + rowCount + '][price]" value="' + defaultPrice + '" class="input-small" style="width: 70px;" /></td>';

	html += '<td><input type="text" name="rows[' + rowCount + '][percentage]" value="' + defaultPercentage + '" class="input-small" style="width: 50px; display: inline;" />&nbsp;%</td>';

	html += '<td><input type="text" name="rows[' + rowCount + '][formula]" value="' + defaultFormula + '" class="input-small" /></td>';

	html += '<td><a href="javascript:void(0);" onclick="removeGenRow(' + rowCount + ');" class="btn btn-danger btn-small"><i class="icon-trash"></i></a></td>';

	tr.innerHTML = html;
	tbody.appendChild(tr);

	if(useContainer) {
		var tplId = '';
		if(type == 'zone') tplId = 'zone_namebox_template';
		else if(type == 'warehouse') tplId = 'warehouse_template';
		else if(type == 'currency') tplId = 'currency_template';

		var template = document.getElementById(tplId).innerHTML;
		template = template.replace(/template_id/g, rowCount);
		template = template.replace(/picker_template_id/g, 'picker_' + rowCount);

		var container = document.getElementById('container_' + rowCount);
		container.innerHTML = template;

		var scripts = container.getElementsByTagName("script");
		for(var i=0; i<scripts.length; i++) {
			if(scripts[i].innerText) {
				window.eval(scripts[i].innerText);
			}
		}
	}

	rowCount++;
}

function changeRestrictionType(elem) {
	var tbody = document.getElementById('generator_rows');
	var needsConfirm = false;

	if(tbody.children.length > 1) {
		needsConfirm = true;
	} else if(tbody.children.length == 1) {
		var tr = tbody.children[0];
		var id = tr.id.split('_')[2];

		var price = tr.querySelector('input[name="rows[' + id + '][price]"]').value;
		var percentage = tr.querySelector('input[name="rows[' + id + '][percentage]"]').value;
		var formula = tr.querySelector('input[name="rows[' + id + '][formula]"]').value;

		if(price != defaultPrice || percentage != defaultPercentage || formula != defaultFormula) {
			needsConfirm = true;
		} else {
			var firstCell = tr.cells[0];
			var inputs = firstCell.querySelectorAll('input, select');
			for(var k=0; k<inputs.length; k++) {
				if(inputs[k].name && inputs[k].name.indexOf('[type]') !== -1) continue;
				if(inputs[k].name && inputs[k].name.indexOf('unit') !== -1) continue;
				if(inputs[k].name && inputs[k].name.indexOf('price_use_tax') !== -1) continue; // Default tax checked
				if(inputs[k].value != '') {
					needsConfirm = true;
					break;
				}
			}
		}
	}

	if(needsConfirm) {
		if(!confirm('<?php echo JText::_('CONFIRM_RESTRICTION_CHANGE', true); ?>')) {
			elem.value = elem.getAttribute('data-prev') || 'zone';
			return;
		}
	}
	elem.setAttribute('data-prev', elem.value);
	tbody.innerHTML = '';
	addGeneratorRow();
}

function removeGenRow(id) {
	var row = document.getElementById('gen_row_' + id);
	if(row) row.parentNode.removeChild(row);
}

function processGeneration() {
	var rows = [];
	var inputs = document.querySelectorAll('#generator_rows tr');

	for(var i=0; i<inputs.length; i++) {
		var tr = inputs[i];
		var idParts = tr.id.split('_');
		var id = idParts[2]; // gen_row_X

		var typeInput = tr.querySelector('input[name="rows[' + id + '][type]"]');
		var type = typeInput ? typeInput.value : 'zone';

		var row = {
			type: type,
			price: tr.querySelector('input[name="rows[' + id + '][price]"]').value,
			percentage: tr.querySelector('input[name="rows[' + id + '][percentage]"]').value,
			formula: tr.querySelector('input[name="rows[' + id + '][formula]"]').value
		};

		if(type == 'zone') {
			var zoneInput = tr.querySelector('input[name="rows[' + id + '][zone_namekey]"]');
			if(zoneInput) row.zone_namekey = zoneInput.value;
		} else if(type == 'weight') {
			row.min_weight = tr.querySelector('input[name="rows[' + id + '][min_weight]"]').value;
			row.max_weight = tr.querySelector('input[name="rows[' + id + '][max_weight]"]').value;
			row.weight_unit = tr.querySelector('[name="rows[' + id + '][weight_unit]"]').value;
		} else if(type == 'volume') {
			row.min_volume = tr.querySelector('input[name="rows[' + id + '][min_volume]"]').value;
			row.max_volume = tr.querySelector('input[name="rows[' + id + '][max_volume]"]').value;
			row.volume_unit = tr.querySelector('[name="rows[' + id + '][volume_unit]"]').value;
		} else if(type == 'postcode') {
			row.zip_prefix = tr.querySelector('input[name="rows[' + id + '][zip_prefix]"]').value;
		} else if(type == 'postcoderegex') {
			row.zip_regex = tr.querySelector('input[name="rows[' + id + '][zip_regex]"]').value;
		} else if(type == 'warehouse') {
			var whInput = tr.querySelector('input[name="rows[' + id + '][warehouse_filter]"]');
			if(whInput) row.warehouse_filter = whInput.value;
		} else if(type == 'quantity') {
			row.min_quantity = tr.querySelector('input[name="rows[' + id + '][min_quantity]"]').value;
			row.max_quantity = tr.querySelector('input[name="rows[' + id + '][max_quantity]"]').value;
		} else if(type == 'price') {
			row.min_price = tr.querySelector('input[name="rows[' + id + '][min_price]"]').value;
			row.max_price = tr.querySelector('input[name="rows[' + id + '][max_price]"]').value;
			var taxInput = tr.querySelector('input[name="rows[' + id + '][price_use_tax]"]:checked');
			row.price_use_tax = taxInput ? taxInput.value : 1;
		} else if(type == 'currency') {
			var currInput = tr.querySelector('input[name="rows[' + id + '][currency]"]');
			if(currInput) row.currency = currInput.value;
		}

		rows.push(row);
	}

	if(rows.length === 0) return;

	var postData = '';
	for(var j=0; j<shippingIds.length; j++) {
		if(postData != '') postData += '&';
		postData += 'shipping_ids[]=' + encodeURIComponent(shippingIds[j]);
	}
	for(var i=0; i<rows.length; i++) {
		var row = rows[i];
		for(var key in row) {
			if(row.hasOwnProperty(key)) {
				postData += '&rows[' + i + '][' + key + ']=' + encodeURIComponent(row[key]);
			}
		}
	}

	window.Oby.xRequest('index.php?option=com_hikashop&ctrl=plugins&task=generatecopies&format=raw', { mode: 'POST', data: postData }, function(xhr) {
		var resp = JSON.parse(xhr.responseText);
		if(resp.status == 'success') {
			var msg = '<?php echo JText::_('X_SHIPPING_METHODS_GENERATED'); ?>'.replace('%s', resp.data.success);
			alert(msg);
			window.parent.location.reload();
			window.parent.hikashop.closeBox();
		} else {
			alert(resp.message);
		}
	});
}
</script>

<!-- Hidden Template for Zone Namebox -->
<div id="zone_namebox_template" style="display:none;">
	<?php
	echo $this->nameboxType->display(
		'rows[template_id][zone_namekey]',
		'',
		hikashopNameboxType::NAMEBOX_SINGLE,
		'zone',
		array(
			'delete' => true,
			'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
			'zone_types' => array('country' => 'COUNTRY', 'state' => 'STATE', 'tax' => 'TAXES'),
			'id' => 'zone_picker_template_id' 
		)
	);
	?>
</div>

<!-- Hidden Template for Weight -->
<div id="weight_template" style="display:none;">
	<input type="text" name="rows[template_id][min_weight]" placeholder="<?php echo JText::_('SHIPPING_MIN_WEIGHT'); ?>" style="width: 150px;" /> 
	<input type="text" name="rows[template_id][max_weight]" placeholder="<?php echo JText::_('SHIPPING_MAX_WEIGHT'); ?>" style="width: 150px;" /> 
	<?php echo $this->weight->display('rows[template_id][weight_unit]', ''); ?>
</div>

<!-- Hidden Template for Volume -->
<div id="volume_template" style="display:none;">
	<input type="text" name="rows[template_id][min_volume]" placeholder="<?php echo JText::_('SHIPPING_MIN_VOLUME'); ?>" style="width: 150px;" /> 
	<input type="text" name="rows[template_id][max_volume]" placeholder="<?php echo JText::_('SHIPPING_MAX_VOLUME'); ?>" style="width: 150px;" /> 
	<?php echo $this->volume->display('rows[template_id][volume_unit]', ''); ?>
</div>

<!-- Hidden Template for Post Code -->
<div id="postcode_template" style="display:none;">
	<input type="text" name="rows[template_id][zip_prefix]" placeholder="<?php echo JText::_('SHIPPING_PREFIX'); ?>" />
</div>

<!-- Hidden Template for Post Code Regex -->
<div id="postcoderegex_template" style="display:none;">
	<input type="text" name="rows[template_id][zip_regex]" placeholder="<?php echo JText::_('SHIPPING_ZIP_REGEX'); ?>" />
</div>

<!-- Hidden Template for Warehouse -->
<div id="warehouse_template" style="display:none;">
	<?php echo $this->warehouseType->displayMultiple('rows[template_id][warehouse_filter]', '', true); ?>
</div>

<!-- Hidden Template for Quantity -->
<div id="quantity_template" style="display:none;">
	<input type="text" name="rows[template_id][min_quantity]" placeholder="<?php echo JText::_('SHIPPING_MIN_QUANTITY'); ?>" style="width: 150px;" />
	<input type="text" name="rows[template_id][max_quantity]" placeholder="<?php echo JText::_('SHIPPING_MAX_QUANTITY'); ?>" style="width: 150px;" />
</div>

<!-- Hidden Template for Price -->
<div id="price_template" style="display:none;">
	<input type="text" name="rows[template_id][min_price]" placeholder="<?php echo JText::_('SHIPPING_MIN_PRICE'); ?>" style="width: 150px;" />
	<input type="text" name="rows[template_id][max_price]" placeholder="<?php echo JText::_('SHIPPING_MAX_PRICE'); ?>" style="width: 150px;" />
	<label style="display:inline-block; margin-left:10px;"><?php echo JText::_('WITH_TAX'); ?>: 
	<?php echo JHTML::_('hikaselect.booleanlist', "rows[template_id][price_use_tax]" , '', 1); ?>
	</label>
</div>

<!-- Hidden Template for Currency -->
<div id="currency_template" style="display:none;">
	<?php
	echo $this->nameboxType->display(
		'rows[template_id][currency]',
		'',
		hikashopNameboxType::NAMEBOX_MULTIPLE,
		'currency',
		array(
			'delete' => true,
			'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
			'id' => 'currency_picker_template_id' 
		)
	);
	?>
</div>

<script>
addGeneratorRow();
</script>
