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
?><div class="hikashop_plugins_import">
	<form action="index.php?option=com_hikashop&ctrl=plugins" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
		<div class="hikashop_backend_tile_edition">
			<div class="hikashop_tile_block">
				<div class="hikashop_tile_title">
					<?php echo JText::_('IMPORT_CSV_STEP_1'); ?>
				</div>
				<div class="hikashop_tile_content">
					<p><?php echo JText::_('PLUGINS_IMPORT_CSV_DESC'); ?></p>

					<div class="control-group">
						<div class="control-label">
							<label for="csv_file"><?php echo JText::_('SELECT_CSV_FILE'); ?></label>
						</div>
						<div class="controls">
							<input type="file" name="csv_file" id="csv_file" class="inputbox" />
						</div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<label for="csv_content"><?php echo JText::_('OR_PASTE_CSV_CONTENT'); ?></label>
						</div>
						<div class="controls">
							<textarea name="csv_content" id="csv_content" rows="10" style="width:100%;" placeholder="<?php echo htmlspecialchars("column1,column2\nvalue1,value2"); ?>"><?php if(!empty($this->csv_content)) echo htmlspecialchars($this->csv_content); ?></textarea>
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
		<input type="hidden" name="plugin_type" value="<?php echo $this->plugin_type; ?>" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
</div>
