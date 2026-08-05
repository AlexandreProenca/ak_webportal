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
?><style>
	.hk-ec-card { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
	.hk-ec-header { text-align: center; margin-bottom: 30px; }
	.hk-ec-title { margin: 0; color: #333; font-weight: 600; font-size: 24px; }
	.hk-ec-icon { color: #c7000b; margin-right: 10px; }
	.hk-ec-subtitle { color: #666; margin-top: 10px; font-size: 15px; }
	.hk-ec-grid-container { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
	.hk-ec-section-title { margin-top: 0; color: #495057; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
	.hk-ec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
	.hk-ec-grid-item { display: flex; align-items: center; color: #495057; font-size: 14px; }
	.hk-ec-check { color: #28a745; margin-right: 8px; font-size: 12px; }
	.hk-ec-warning { background: #fff3cd; color: #856404; padding: 15px 20px; border-radius: 6px; border-left: 4px solid #ffc107; margin-bottom: 30px; font-size: 14px; line-height: 1.5; }
	.hk-ec-warning-flex { display: flex; }
	.hk-ec-warning-icon { font-size: 18px; margin-right: 15px; padding-top: 2px; }
	.hk-ec-warning-title { font-weight: bold; margin-bottom: 5px; }
	.hk-ec-fieldset { border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; }
	.hk-ec-legend { width: auto; padding: 0 10px; font-weight: 600; color: #333; font-size: 16px; border-bottom: none; margin-bottom: 0; }
	.hk-ec-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.hk-ec-key { width: 200px; padding: 12px; font-weight: 500; color: #333; }
	.hk-ec-key-icon { color: #6c757d; margin-right: 5px; }
	.hk-ec-value { padding: 12px; }
	.hk-ec-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 400px; font-size: 14px; }
	.hk-ec-input-small { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 150px; font-size: 14px; }
	.hk-ec-help { color: #6c757d; font-size: 12px; margin-top: 5px; }
	.hk-ec-button-container { text-align: center; margin-top: 25px; }
	.hk-ec-import-btn { background-color: #c7000b; border-color: #c7000b; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
	.hk-ec-import-btn:hover { background-color: #a50009; border-color: #a50009; }
	button.btn.btn-primary.hk-ec-import-btn { display: flex; justify-content: center; align-items: center; margin: 0 auto;}
</style>

<div class="hk-ec-card">

	<!-- Header -->
	<div class="hk-ec-header">
		<h2 class="hk-ec-title">
			<span class="icon-loop hk-ec-icon"></span>
			<?php echo JText::sprintf('PRODUCTS_FROM_X', 'EC-CUBE'); ?>
		</h2>
		<p class="hk-ec-subtitle">
			<?php echo JText::sprintf('X_IMPORT_DESC','EC-CUBE','EC-CUBE'); ?>
		</p>
	</div>

	<!-- Features Grid -->
	<div class="hk-ec-grid-container">
		<h4 class="hk-ec-section-title">
			<?php echo JText::_('MAIN_INFORMATION'); ?>
		</h4>
		<div class="hk-ec-grid">
			<?php
			$functions = array('TAXATIONS','PRODUCT_CATEGORIES','PRODUCTS','PRICES','HIKA_IMAGES','USERS','ADDRESSES','ORDERS');
			foreach($functions as $v): ?>
				<div class="hk-ec-grid-item">
					<span class="icon-checkmark hk-ec-check"></span>
					<?php echo JText::_($v); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Warnings -->
	<div class="hk-ec-warning">
		<div class="hk-ec-warning-flex">
			<span class="icon-warning hk-ec-warning-icon"></span>
			<div>
				<div class="hk-ec-warning-title"><?php echo JText::_('HIKA_CAUTION'); ?></div>
				<?php echo JText::_('EC_WARNING_DB'); ?>
			</div>
		</div>
	</div>

	<!-- Connection Settings Form -->
	<fieldset class="hk-ec-fieldset">
		<legend class="hk-ec-legend">
			<?php echo JText::_('CONNECTION_SETTINGS'); ?>
		</legend>

		<table class="admintable table table-striped hk-ec-table">
			<tr>
				<td class="key hk-ec-key">
					<span class="icon-database hk-ec-key-icon"></span>
					<?php echo JText::_('DATABASE_NAME'); ?>
				</td>
				<td class="hk-ec-value">
					<input type="text" name="ecDatabase" value="" class="inputbox hk-ec-input" placeholder="e.g. eccube_db" />
					<div class="hk-ec-help">
						<?php echo JText::_('EC_DATABASE_HELP'); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="key hk-ec-key">
					<span class="icon-cog hk-ec-key-icon"></span>
					<?php echo JText::_('EC_TABLE_PREFIX'); ?>
				</td>
				<td class="hk-ec-value">
					<input type="text" name="ecPrefix" value="dtb_" class="inputbox hk-ec-input-small" />
					<div class="hk-ec-help">
						<?php echo JText::_('EC_TABLE_PREFIX_HELP'); ?>
					</div>
				</td>
			</tr>
		<tr>
				<td class="key hk-ec-key">
					<span class="icon-folder hk-ec-key-icon"></span>
					<?php echo JText::_('EC_INSTALLATION_PATH'); ?>
				</td>
				<td class="hk-ec-value">
					<input type="text" name="ecPath" value="" class="inputbox hk-ec-input" placeholder="/home/user/public_html/eccube" />
					<div class="hk-ec-help">
						<?php echo JText::_('EC_INSTALLATION_PATH_HELP'); ?>
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<!-- Import Button -->
	<div class="hk-ec-button-container">
		<button type="button" class="btn btn-primary hk-ec-import-btn" onclick="Joomla.submitbutton('import');">
			<span class="icon-loop" style="margin-right: 8px;"></span>
			<?php echo JText::_('IMPORT'); ?>
		</button>
	</div>
</div>
