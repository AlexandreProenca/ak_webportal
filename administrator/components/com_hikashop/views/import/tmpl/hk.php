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
	.hk-hkimport-card { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
	.hk-hkimport-header { text-align: center; margin-bottom: 30px; }
	.hk-hkimport-title { margin: 0; color: #333; font-weight: 600; font-size: 24px; }
	.hk-hkimport-icon { color: #297F93; margin-right: 10px; }
	.hk-hkimport-subtitle { color: #666; margin-top: 10px; font-size: 15px; }
	.hk-hkimport-grid-container { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
	.hk-hkimport-section-title { margin-top: 0; color: #495057; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
	.hk-hkimport-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
	.hk-hkimport-grid-item { display: flex; align-items: center; color: #495057; font-size: 14px; }
	.hk-hkimport-check { color: #28a745; margin-right: 8px; font-size: 12px; }
	.hk-hkimport-warning { background: #fff3cd; color: #856404; padding: 15px 20px; border-radius: 6px; border-left: 4px solid #ffc107; margin-bottom: 30px; font-size: 14px; line-height: 1.5; }
	.hk-hkimport-warning-flex { display: flex; }
	.hk-hkimport-warning-icon { font-size: 18px; margin-right: 15px; padding-top: 2px; }
	.hk-hkimport-warning-title { font-weight: bold; margin-bottom: 5px; }
	.hk-hkimport-fieldset { border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; }
	.hk-hkimport-legend { width: auto; padding: 0 10px; font-weight: 600; color: #333; font-size: 16px; border-bottom: none; margin-bottom: 0; }
	.hk-hkimport-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.hk-hkimport-key { width: 200px; padding: 12px; font-weight: 500; color: #333; }
	.hk-hkimport-key-icon { color: #6c757d; margin-right: 5px; }
	.hk-hkimport-value { padding: 12px; }
	.hk-hkimport-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 400px; font-size: 14px; }
	.hk-hkimport-input-small { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 150px; font-size: 14px; }
	.hk-hkimport-help { color: #6c757d; font-size: 12px; margin-top: 5px; }
	.hk-hkimport-button-container { text-align: center; margin-top: 25px; }
	.hk-hkimport-import-btn { background-color: #297F93; border-color: #297F93; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
	.hk-hkimport-import-btn:hover { background-color: #1f6575; border-color: #1f6575; }
	button.btn.btn-primary.hk-hkimport-import-btn { display: flex; justify-content: center; align-items: center; margin: 0 auto;}
	.hk-hkimport-radio-group { display: flex; gap: 20px; align-items: center; }
	.hk-hkimport-radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
</style>

<div class="hk-hkimport-card">

	<!-- Header -->
	<div class="hk-hkimport-header">
		<h2 class="hk-hkimport-title">
			<span class="icon-loop hk-hkimport-icon"></span>
			<?php echo JText::sprintf('PRODUCTS_FROM_X', 'HikaShop'); ?>
		</h2>
		<p class="hk-hkimport-subtitle">
			<?php echo JText::sprintf('X_IMPORT_DESC','HikaShop','HikaShop'); ?>
		</p>
	</div>

	<!-- Features Grid -->
	<div class="hk-hkimport-grid-container">
		<h4 class="hk-hkimport-section-title">
			<?php echo JText::_('MAIN_INFORMATION'); ?>
		</h4>
		<div class="hk-hkimport-grid">
			<?php
			$functions = array('TAXATIONS','PRODUCT_CATEGORIES','PRODUCTS','PRODUCT_VARIATIONS','HIKA_IMAGES','PRICES','USERS','ADDRESSES','COUPONS','ORDERS','DOWNLOADS');
			foreach($functions as $v): ?>
				<div class="hk-hkimport-grid-item">
					<span class="icon-checkmark hk-hkimport-check"></span>
					<?php echo JText::_($v); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Warnings -->
	<div class="hk-hkimport-warning">
		<div class="hk-hkimport-warning-flex">
			<span class="icon-warning hk-hkimport-warning-icon"></span>
			<div>
				<div class="hk-hkimport-warning-title"><?php echo JText::_('HIKA_CAUTION'); ?></div>
				<?php echo JText::_('HK_IMPORT_WARNING_DB'); ?>
			</div>
		</div>
	</div>

	<!-- Connection Settings Form -->
	<fieldset class="hk-hkimport-fieldset">
		<legend class="hk-hkimport-legend">
			<?php echo JText::_('CONNECTION_SETTINGS'); ?>
		</legend>

		<table class="admintable table table-striped hk-hkimport-table">
			<tr>
				<td class="key hk-hkimport-key">
					<span class="icon-screen hk-hkimport-key-icon"></span>
					<?php echo JText::_('SOURCE_CMS_TYPE'); ?>
				</td>
				<td class="hk-hkimport-value">
					<div class="hk-hkimport-radio-group">
						<label>
							<input type="radio" name="hkCmsType" value="joomla" checked="checked" onclick="updateHkCmsType('joomla');" />
							Joomla
						</label>
						<label>
							<input type="radio" name="hkCmsType" value="wordpress" onclick="updateHkCmsType('wordpress');" />
							WordPress
						</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="key hk-hkimport-key">
					<span class="icon-database hk-hkimport-key-icon"></span>
					<?php echo JText::_('DATABASE_NAME'); ?>
				</td>
				<td class="hk-hkimport-value">
					<input type="text" name="hkDatabase" value="" class="inputbox hk-hkimport-input" placeholder="e.g. joomla_db" />
					<div class="hk-hkimport-help">
						<?php echo JText::_('HK_IMPORT_DATABASE_HELP'); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="key hk-hkimport-key">
					<span class="icon-cog hk-hkimport-key-icon"></span>
					<?php echo JText::_('TABLE_PREFIX'); ?>
				</td>
				<td class="hk-hkimport-value">
					<input type="text" name="hkCmsPrefix" value="jos_" class="inputbox hk-hkimport-input-small" />
					<div class="hk-hkimport-help">
						<?php echo JText::_('HK_IMPORT_PREFIX_HELP'); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="key hk-hkimport-key">
					<span class="icon-folder hk-hkimport-key-icon"></span>
					<?php echo JText::_('SOURCE_SITE_PATH'); ?>
				</td>
				<td class="hk-hkimport-value">
					<input type="text" name="hkPath" value="" class="inputbox hk-hkimport-input" placeholder="/home/user/public_html/joomla" />
					<div class="hk-hkimport-help">
						<?php echo JText::_('HK_IMPORT_PATH_HELP'); ?>
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<!-- Import Button -->
	<div class="hk-hkimport-button-container">
		<button type="button" class="btn btn-primary hk-hkimport-import-btn" onclick="Joomla.submitbutton('import');">
			<span class="icon-loop" style="margin-right: 8px;"></span>
			<?php echo JText::_('IMPORT'); ?>
		</button>
	</div>
</div>

<script type="text/javascript">
function updateHkCmsType(type) {
	var cmsPrefixInput = document.querySelector('[name=hkCmsPrefix]');
	if(cmsPrefixInput) cmsPrefixInput.value = (type === 'wordpress') ? 'wp_' : 'jos_';
}
</script>
