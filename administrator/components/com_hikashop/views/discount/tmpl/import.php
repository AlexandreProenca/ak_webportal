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
?><div class="hikashop_discount_import">
	<form action="index.php?option=com_hikashop&ctrl=discount" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
		<div class="hikashop_backend_tile_edition">
			<div class="hikashop_tile_block">
				<div class="hikashop_tile_title">
					<?php echo JText::_('DISCOUNT_IMPORT_STEP_1'); ?>
				</div>
				<div class="hikashop_tile_content">
					<p><?php echo JText::_('DISCOUNT_IMPORT_CSV_DESC'); ?></p>

					<?php
					$importType = !empty($this->import_type) ? $this->import_type : 'coupon';
					?>
					<div class="control-group">
						<div class="control-label">
							<label for="import_type"><?php echo JText::_('DISCOUNT_IMPORT_TYPE_LABEL'); ?></label>
						</div>
						<div class="controls">
							<select name="import_type" id="import_type" class="custom-select">
								<option value="coupon" <?php if($importType === 'coupon') echo 'selected="selected"'; ?>><?php echo JText::_('COUPON'); ?></option>
								<option value="discount" <?php if($importType === 'discount') echo 'selected="selected"'; ?>><?php echo JText::_('DISCOUNT'); ?></option>
							</select>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<label for="import_file"><?php echo JText::_('SELECT_FILE'); ?></label>
						</div>
						<div class="controls">
							<input type="file" name="import_file" id="import_file" class="inputbox" accept=".csv,.tsv,.txt,.xml" />
						</div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<label for="import_content"><?php echo JText::_('OR_PASTE_CONTENT'); ?></label>
						</div>
						<div class="controls">
							<textarea name="import_content" id="import_content" rows="10" style="width:100%;" placeholder="<?php echo htmlspecialchars("discount_code,discount_percent_amount,discount_end\nWELCOME10,10,2026-12-31"); ?>"><?php if(!empty($this->import_content)) echo htmlspecialchars($this->import_content); ?></textarea>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<button type="submit" class="btn btn-primary"><?php echo JText::_('NEXT'); ?></button>
							<button type="button" class="btn" onclick="window.parent.hikashop.closeBox();"><?php echo JText::_('HIKA_CANCEL'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" value="step2import" />
		<input type="hidden" name="tmpl" value="component" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
</div>
