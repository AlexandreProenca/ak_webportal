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
$zoneClass = hikashop_get('class.zone');
$supportedColumns = $zoneClass->getImportSupportedColumns();
?>
<div class="hikashop_zone_import_csv_step2">
	<form action="index.php?option=com_hikashop&ctrl=zone" method="post" name="adminForm" id="adminForm">
		<div class="hikashop_backend_tile_edition">
			<div class="hikashop_tile_block">
				<div class="hikashop_tile_title">
					<?php echo JText::_('IMPORT_CSV_STEP_2'); ?>
				</div>
				<div class="hikashop_tile_content">
					<p><?php echo JText::_('ZONE_IMPORT_MAPPING_DESC'); ?></p>

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
								} elseif(isset($supportedColumns['zone_'.$normalized])) {
									$match = 'zone_'.$normalized;
								} else {
									if($normalized == 'name') $match = 'zone_name';
									elseif($normalized == 'code' || $normalized == 'code_2' || $normalized == 'code_3') {
										$len = 0;
										if(!empty($this->csv_data) && isset($this->csv_data[0][$col])) {
											$len = strlen(trim($this->csv_data[0][$col]));
										}

										if($len == 3) $match = 'zone_code_3';
										else $match = 'zone_code_2';
									}
									elseif($normalized == 'published' || $normalized == 'active') $match = 'zone_published';
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
							<button type="button" class="btn" onclick="document.adminForm.task.value='importcsv'; document.adminForm.submit();"><?php echo JText::_('HIKA_BACK'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" value="doprocesscsv" />
		<input type="hidden" name="tmpl" value="component" />
		<input type="hidden" name="main_id" value="<?php echo $this->main_id; ?>" />
		<textarea name="csv_content" style="display:none;"><?php echo htmlspecialchars($this->csv_content); ?></textarea>
		<?php echo JHTML::_('form.token'); ?>
	</form>
</div>
