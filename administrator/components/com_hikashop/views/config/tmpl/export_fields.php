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
defined('_JEXEC') or die('Restricted access');
$config = hikashop_config();
$selected_cols_str = $config->get($this->type.'_export_columns', '');
if(strlen($selected_cols_str) > 0) {
	$selected_cols = explode(',', $selected_cols_str);
} else {
	$selected_cols = array_keys($this->columns); 
}
?>
<script type="text/javascript">
	Joomla.submitbutton = function(pressbutton) {
		var form = document.getElementById('adminForm');
		if (pressbutton == 'cancel') {
			window.parent.hikashop.closeBox();
			return;
		}
		form.submit();
	}

	function hikashopCheckAll(source) {
		var checkboxes = document.getElementsByName('columns[]');
		for(var i=0, n=checkboxes.length;i<n;i++) {
			checkboxes[i].checked = source.checked;
		}
	}
</script>
<form action="index.php?option=com_hikashop&ctrl=config&task=save_export_fields" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="type" value="<?php echo $this->type; ?>" />
	<input type="hidden" name="tmpl" value="component" />
	<?php echo JHtml::_('form.token'); ?>

	<div class="hikashop_export_fields" style="padding: 10px;">
		<h3 style="margin-top:0"><?php echo JText::sprintf('EXPORT_FIELDS_FOR', JText::_(strtoupper($this->type.'s'))); ?></h3>
		<div class="btn-toolbar" style="text-align:right; margin-bottom: 10px;">
			 <button class="btn btn-success" onclick="Joomla.submitbutton('save'); return false;">
				<?php echo JText::_('HIKA_SAVE'); ?>
			 </button>
			 <button class="btn" onclick="Joomla.submitbutton('cancel'); return false;">
				<?php echo JText::_('HIKA_CANCEL'); ?>
			 </button>
		</div>

		<div style="height: 400px; overflow: auto; border: 1px solid #ccc;">
			<?php 
			if($this->type == 'order') {
				echo JText::_('ONE_PRODUCT_PER_ROW').' '.JHtml::_('select.booleanlist', 'order_export_one_product_per_row', '', $config->get('order_export_one_product_per_row', 0));
				echo '<br/><br/>';
			}
			?>
			<table class="table table-striped" style="margin-bottom: 0;">
				<thead>
					<tr>
						<th width="20"><input type="checkbox" name="toggle" value="" onclick="hikashopCheckAll(this);" <?php echo count($selected_cols) == count($this->columns) ? 'checked="checked"' : ''; ?> /></th>
						<th><?php echo JText::_('FIELD_COLUMN'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach($this->columns as $col => $label): ?>
					<tr>
						<td>
							<input type="checkbox" id="cb<?php echo $col; ?>" name="columns[]" value="<?php echo htmlspecialchars($col); ?>" <?php echo in_array($col, $selected_cols) ? 'checked="checked"' : ''; ?> />
						</td>
						<td>
							<label for="cb<?php echo $col; ?>"><?php echo htmlspecialchars($label); ?></label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</form>
