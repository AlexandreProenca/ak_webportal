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
$type = $this->plugin_type;
$classType = 'class.'.$type;
$class = hikashop_get($classType);
$supportedColumns = array();
if(method_exists($class, 'getExportColumns')) {
	$supportedColumns = $class->getExportColumns(false);
} else {
	$db = JFactory::getDBO();
	if(!HIKASHOP_J30){
		$columnsTable = $db->getTableFields(hikashop_table($type));
		$columnsArray = reset($columnsTable);
	} else {
		$columnsArray = $db->getTableColumns(hikashop_table($type));
	}
	$supportedColumns = array_combine(array_keys($columnsArray), array_keys($columnsArray));
}
?>
<div class="hikashop_plugins_import_step2">
	<form action="index.php?option=com_hikashop&ctrl=plugins" method="post" name="adminForm" id="adminForm">
		<div class="hikashop_backend_tile_edition">
			<div class="hikashop_tile_block">
				<div class="hikashop_tile_title">
					<?php echo JText::_('IMPORT_CSV_STEP_2'); ?>
				</div>
				<div class="hikashop_tile_content">
					<p><?php echo JText::_('PLUGINS_IMPORT_MAPPING_DESC'); ?></p>

					<table class="table table-striped">
						<thead>
							<tr>
								<th><?php echo JText::_('CSV_COLUMN'); ?></th>
								<th><?php echo JText::_('HIKASHOP_COLUMN'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($this->csv_header as $col): 
								$col = trim($col);
								$match = '';
								$normalized = strtolower(preg_replace('/[\s\-]+/', '_', $col));
								if(isset($supportedColumns[$normalized])) {
									$match = $normalized;
								} elseif(isset($supportedColumns[$type.'_'.$normalized])) {
									$match = $type.'_'.$normalized;
								}
							?>
							<tr>
								<td><?php echo htmlspecialchars($col); ?></td>
								<td>
									<select name="mapping[<?php echo htmlspecialchars($col); ?>]">
										<option value="ignore"><?php echo JText::_('HIKASHOP_IGNORE'); ?></option>
										<?php foreach($supportedColumns as $hkCol): ?>
										<option value="<?php echo $hkCol; ?>" <?php if($hkCol == $match) echo 'selected="selected"'; ?>>
											<?php echo $hkCol; ?>
										</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="control-group">
						<div class="controls">
							<button type="submit" class="btn btn-success"><?php echo JText::_('IMPORT'); ?></button>
							<button type="button" class="btn" onclick="document.adminForm.task.value='import'; document.adminForm.submit();"><?php echo JText::_('HIKA_BACK'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" value="importprocess" />
		<input type="hidden" name="tmpl" value="component" />
		<input type="hidden" name="plugin_type" value="<?php echo $this->plugin_type; ?>" />
		<textarea name="csv_content" style="display:none;"><?php echo htmlspecialchars($this->csv_content); ?></textarea>
		<?php echo JHTML::_('form.token'); ?>
	</form>
</div>
