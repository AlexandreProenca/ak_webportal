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
$orderClass = hikashop_get('class.order');
$supportedColumns = $orderClass->getExportColumns(false);

$virtualColumns = array(
	'billing_address_full_name' => 'billing_address_full_name',
	'shipping_address_full_name' => 'shipping_address_full_name',
	'billing_address_house_number' => 'billing_address_house_number',
	'shipping_address_house_number' => 'shipping_address_house_number',
	'billing_address_street_name' => 'billing_address_street_name',
	'shipping_address_street_name' => 'shipping_address_street_name',
);
$supportedColumns = array_merge($supportedColumns, $virtualColumns);

hikashop_get('helper.order_import');
$importHelper = new hikashopOrderImportHelper();
$matches = $importHelper->autoMatchColumns($this->import_header, $supportedColumns);

$previewRows = array_slice($this->import_data, 0, 3);
?>
<div class="hikashop_order_import_step2">
	<form action="index.php?option=com_hikashop&ctrl=order" method="post" name="adminForm" id="adminForm">
		<div class="hikashop_backend_tile_edition">
			<div class="hikashop_tile_block">
				<div class="hikashop_tile_title">
					<?php echo JText::_('ORDER_IMPORT_STEP_2'); ?>
				</div>
				<div class="hikashop_tile_content">
					<p><?php echo JText::sprintf('ORDER_IMPORT_MODE_DETECTED', JText::_('ORDER_IMPORT_MODE_' . strtoupper($this->import_mode))); ?></p>

					<table class="table table-striped">
						<thead>
							<tr>
								<th><?php echo JText::_('CSV_COLUMN'); ?></th>
								<th><?php echo JText::_('DATA_PREVIEW'); ?></th>
								<th><?php echo JText::_('HIKASHOP_COLUMN'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($this->import_header as $col):
								$col = trim($col);
								$match = isset($matches[$col]) ? $matches[$col] : '';

								$previews = array();
								foreach($previewRows as $previewRow) {
									if(isset($previewRow[$col]) && $previewRow[$col] !== '')
										$previews[] = htmlspecialchars($previewRow[$col]);
								}
							?>
							<tr>
								<td><strong><?php echo htmlspecialchars($col); ?></strong></td>
								<td class="small"><?php echo !empty($previews) ? implode('<br/>', $previews) : '<em>-</em>'; ?></td>
								<td>
									<select name="mapping[<?php echo htmlspecialchars($col); ?>]" class="custom-select">
										<option value="ignore"><?php echo JText::_('HIKASHOP_IGNORE'); ?></option>
										<optgroup label="<?php echo JText::_('ORDER_IMPORT_CREATE_FIELD'); ?>">
											<option value="__new_order_field__"><?php echo JText::_('ORDER_IMPORT_NEW_ORDER_FIELD'); ?></option>
											<option value="__new_item_field__"><?php echo JText::_('ORDER_IMPORT_NEW_ITEM_FIELD'); ?></option>
											<option value="__new_user_field__"><?php echo JText::_('ORDER_IMPORT_NEW_USER_FIELD'); ?></option>
											<option value="__new_address_field__"><?php echo JText::_('ORDER_IMPORT_NEW_ADDRESS_FIELD'); ?></option>
										</optgroup>
										<optgroup label="<?php echo JText::_('HIKASHOP_FIELDS'); ?>">
										<?php foreach($supportedColumns as $hkCol): ?>
										<option value="<?php echo htmlspecialchars($hkCol); ?>" <?php if($hkCol == $match) echo 'selected="selected"'; ?>>
											<?php echo htmlspecialchars($hkCol); ?>
										</option>
										<?php endforeach; ?>
										</optgroup>
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
		<input type="hidden" name="import_mode" value="<?php echo htmlspecialchars($this->import_mode); ?>" />
		<textarea name="import_content" style="display:none;"><?php echo htmlspecialchars($this->import_content); ?></textarea>
		<input type="hidden" name="import_format" value="<?php echo htmlspecialchars($this->import_format); ?>" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
</div>
