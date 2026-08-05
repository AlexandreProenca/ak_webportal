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
?><div class="hikashop_column_matching_content">
	<div style="margin-bottom:15px;">
		<h2><?php echo JText::_('CONFIGURE_COLUMNS'); ?></h2>
	</div>
	<div style="margin-bottom:15px;">
		<?php echo JText::_('COLUMN_MATCHING_DESCRIPTION'); ?>
	</div>
	<table class="adminlist table table-striped" style="margin-bottom:15px;">
		<thead>
			<tr>
				<th><?php echo JText::_('CSV_COLUMN'); ?></th>
				<th><?php echo JText::_('HIKASHOP_COLUMN'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
foreach($this->csvColumns as $idx => $csvCol) {
	$matched = isset($this->autoMatched[$csvCol]) ? $this->autoMatched[$csvCol] : '';
?>
			<tr>
				<td><strong><?php echo htmlspecialchars($csvCol); ?></strong></td>
				<td>
					<select class="hika_col_select inputbox form-select" name="mapping[<?php echo htmlspecialchars($csvCol); ?>]" data-csv="<?php echo htmlspecialchars($csvCol); ?>" style="width:100%;">
						<option value="ignore">-- <?php echo JText::_('HIKASHOP_IGNORE'); ?> --</option>
<?php
	foreach($this->supportedColumns as $key => $label) {
		$selected = ($key === $matched) ? ' selected="selected"' : '';
?>
						<option value="<?php echo htmlspecialchars($key); ?>"<?php echo $selected; ?>><?php echo htmlspecialchars($label); ?><?php if($key !== $label) echo ' (' . htmlspecialchars($key) . ')'; ?></option>
<?php
	}
?>
					</select>
				</td>
			</tr>
<?php
}
?>
			<tr>
				<td><strong><?php echo JText::_('MAPPING_NAME'); ?></strong></td>
				<td>
					<input type="text" id="hika_mapping_name" name="mapping_name" value="<?php echo isset($this->loadedMappingName) ? htmlspecialchars($this->loadedMappingName) : ''; ?>" placeholder="<?php echo JText::_('MAPPING_NAME'); ?>" class="inputbox form-control" style="width:100%;" />
				</td>
			</tr>
		</tbody>
	</table>

	<input type="hidden" name="prefix" value="<?php echo $this->prefix; ?>" />
</div>

<div class="hikashop_column_matching_footer" style="text-align:right; padding-top:15px; border-top:1px solid #ddd;">
	<button type="button" class="btn" onclick="window.hikashop.closeBox();">
		<?php echo JText::_('HIKA_CANCEL'); ?>
	</button>
	<button type="button" class="btn btn-primary" onclick="apply();">
		<?php echo JText::_('HIKA_APPLY'); ?>
	</button>
</div>

<script type="text/javascript">


var loadedMapping = <?php echo json_encode(isset($this->loadedMapping) ? $this->loadedMapping : null); ?>;
var prefix = "<?php echo $this->prefix; ?>";

document.addEventListener('DOMContentLoaded', function() {
	var mapping = null;
	if(loadedMapping) {
		mapping = loadedMapping;
	} else {
		if(window.parent && window.parent.document) {
			var parentInput = window.parent.document.getElementById(prefix + "_mapping");
			if(parentInput && parentInput.value) {
				try {
					mapping = JSON.parse(parentInput.value);
				} catch(e) { console.error("Invalid mapping JSON", e); }
			}
		}
	}

	if(mapping) {
		var selectors = document.querySelectorAll(".hika_col_select");
		for(var i=0; i<selectors.length; i++) {
			var sel = selectors[i];
			var col = sel.getAttribute("data-csv");
			if(mapping[col]) {
				sel.value = mapping[col];
			}
		}
	}
});

function apply() {
	var prefix = document.querySelector("input[name=prefix]").value;
	var mapping = {};
	var selectors = document.querySelectorAll(".hika_col_select");
	for(var i=0; i<selectors.length; i++) {
		var sel = selectors[i];
		var csvCol = sel.getAttribute("data-csv");
		var hikaCol = sel.value;
		if(csvCol && hikaCol) {
			mapping[csvCol] = hikaCol;
		}
	}

	var name = document.getElementById("hika_mapping_name").value;
	if(!name || name.replace(/^\s+|\s+$/g, '') === '') {
		alert("<?php echo JText::sprintf('PLEASE_FILL_THE_FIELD', JText::_('MAPPING_NAME'), true); ?>");
		return;
	}

	if(window.parent && window.parent.hikaImport && window.parent.hikaImport.handlePopupMapping) {
		window.parent.hikaImport.handlePopupMapping(mapping, prefix, name);
	} else {
		alert('Parent window not found');
	}
}
</script>
