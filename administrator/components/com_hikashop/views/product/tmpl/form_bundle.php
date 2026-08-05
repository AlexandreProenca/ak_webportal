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
$form_key = '';
$input_key = 'product';
if(!empty($this->editing_variant)) {
	$form_key = '_variant';
	$input_key = 'variant';
}
?>
<table id="hikashop_product_characteristics_table<?php echo $form_key; ?>" class="adminlist table table-striped" style="width:100%">
	<thead>
		<tr>
			<th class="title"><?php
				echo JText::_('HIKA_NAME');
			?></th>
			<th class="title"><?php
				echo JText::_('PRODUCT_QUANTITY');
			?></th>
			<th style="width:40px;text-align:center">
				<a href="#" onclick="return window.productMgr.newBundle('<?php echo $form_key; ?>');" title="<?php echo JText::_('ADD'); ?>"><i class="fa fa-plus"></i></a>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr id="hikashop_bundle_add_zone<?php echo $form_key; ?>" style="display:none;">
			<td colspan="3">
<dl>
	<dt><?php echo JText::_('PRODUCT_NAME'); ?></dt>
	<dd><?php
		echo $this->nameboxType->display(
			null,
			null,
			hikashopNameboxType::NAMEBOX_SINGLE,
			'product',
			array(
				'id' => 'hikashop_bundle_nb_add'.$form_key,
				'default_text' => 'PLEASE_SELECT',
				'variants' => 1,
			)
		);
	?></dd>
	<dt><?php echo JText::_('PRODUCT_QUANTITY'); ?></dt>
	<dd>
		<input type="text" size="5" style="width:70px;" id="hikashop_bundle_qty_add<?php echo $form_key; ?>" name="" value="1"/>
	</dd>
</dl>
<div style="float:right">
	<button onclick="return window.productMgr.addBundle('<?php echo $form_key; ?>', '<?php echo $input_key; ?>');" class="btn btn-success"><i class="fa fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ;?></button>
</div>
<button onclick="return window.productMgr.cancelNewBundle('<?php echo $form_key; ?>');" class="btn btn-danger"><i class="fa fa-times"></i> <?php echo JText::_('HIKA_CANCEL'); ;?></button>
<div style="clear:both"></div>
			</td>
		</tr>
	</tfoot>
	<tbody>
<?php
	$k = 0;
	if(!empty($this->product->bundle)) {
		foreach($this->product->bundle as $bundle) {
			$pid = (int)$bundle->product_related_id;
?>
		<tr class="row<?php echo $k ?>">
			<td><?php
				$desc = JText::_('PRODUCT_ID') . ': ' . $pid;
				if(empty($bundle->product_name))
					$bundle->product_name = $desc;
				echo hikashop_hktooltip($desc, $bundle->product_name, $bundle->product_name);
			?></td>
			<td>
				<input type="text" size="5" style="width:70px;" name="data[<?php echo $input_key; ?>][bundle][<?php echo $pid; ?>]" value="<?php echo max((int)$bundle->product_related_quantity, 1); ?>"/>
			</td>
			<td style="text-align:center">
				<a href="#delete" onclick="window.hikashop.deleteRow(this); return false;"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
<?php
			$k = 1 - $k;
		}
	}
?>
		<tr id="hikashop_bundle_row_template<?php echo $form_key; ?>" class="row<?php echo $k ?>" style="display:none;">
			<td>{NAME}</td>
			<td style="text-align:center">
				<input type="text" size="5" style="width:70px;" name="{INPUT_NAME}" value="{VALUE}"/>
			</td>
			<td style="text-align:center">
				<a href="#delete" onclick="window.hikashop.deleteRow(this); return false;"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
window.productMgr.newBundle = function(key) {
	var w = window, d = document, el = null;
	w.oNameboxes['hikashop_bundle_nb_add'+key].clear();
	el = d.getElementById('hikashop_bundle_qty_add'+key);
	if(el) el.value = '1';
	el = d.getElementById('hikashop_bundle_add_zone'+key);
	if(el) el.style.display = '';
	return false;
};
window.productMgr.cancelNewBundle = function(key) {
	var w = window, d = document, o = w.Oby;
	var el = d.getElementById('hikashop_bundle_add_zone'+key);
	if(el) el.style.display = 'none';
	return false;
};
window.productMgr.addBundle = function(key, input_key) {
	var w = window, d = document, o = w.Oby, c = null, cv = null, ct = null,
		el = d.getElementById('hikashop_bundle_nb_add'+key+'_valuehidden');
	if(el) {
		c = parseInt(el.value);
		el = d.getElementById('hikashop_bundle_nb_add'+key+'_valuetext');
		if(el) ct = el.innerHTML;
	}
	el = d.getElementById('hikashop_bundle_qty_add'+key);
	if(el) cv = parseInt(el.value);

	if(c === null || isNaN(c) || c === 0 || isNaN(cv) || cv === 0)
		return false;

	var htmlblocks = { NAME: ct, ID: c, INPUT_NAME: 'data['+input_key+'][bundle][' + c + ']', VALUE: cv };
	w.hikashop.dupRow('hikashop_bundle_row_template'+key, htmlblocks);
	w.productMgr.cancelNewBundle(key);
	return false;
};
</script>
