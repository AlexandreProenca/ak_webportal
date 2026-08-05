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
	.hk-woo-card { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
	.hk-woo-header { text-align: center; margin-bottom: 30px; }
	.hk-woo-title { margin: 0; color: #333; font-weight: 600; font-size: 24px; }
	.hk-woo-icon { color: #96588a; margin-right: 10px; }
	.hk-woo-subtitle { color: #666; margin-top: 10px; font-size: 15px; }
	.hk-woo-grid-container { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
	.hk-woo-section-title { margin-top: 0; color: #495057; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
	.hk-woo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
	.hk-woo-grid-item { display: flex; align-items: center; color: #495057; font-size: 14px; }
	.hk-woo-check { color: #28a745; margin-right: 8px; font-size: 12px; }
	.hk-woo-warning { background: #fff3cd; color: #856404; padding: 15px 20px; border-radius: 6px; border-left: 4px solid #ffc107; margin-bottom: 30px; font-size: 14px; line-height: 1.5; }
	.hk-woo-warning-flex { display: flex; }
	.hk-woo-warning-icon { font-size: 18px; margin-right: 15px; padding-top: 2px; }
	.hk-woo-warning-title { font-weight: bold; margin-bottom: 5px; }
	.hk-woo-fieldset { border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; }
	.hk-woo-legend { width: auto; padding: 0 10px; font-weight: 600; color: #333; font-size: 16px; border-bottom: none; margin-bottom: 0; }
	.hk-woo-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.hk-woo-key { width: 200px; padding: 12px; font-weight: 500; color: #333; }
	.hk-woo-key-icon { color: #6c757d; margin-right: 5px; }
	.hk-woo-value { padding: 12px; }
	.hk-woo-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 400px; font-size: 14px; }
	.hk-woo-input-small { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 150px; font-size: 14px; }
	.hk-woo-help { color: #6c757d; font-size: 12px; margin-top: 5px; }
	.hk-woo-button-container { text-align: center; margin-top: 25px; }
	.hk-woo-import-btn { background-color: #96588a; border-color: #96588a; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
	.hk-woo-import-btn:hover { background-color: #824b78; border-color: #824b78; }
    button.btn.btn-primary.hk-woo-import-btn { display: flex; justify-content: center; align-items: center; margin: 0 auto;}
</style>

<div class="hk-woo-card">

    <!-- Header -->
    <div class="hk-woo-header">
        <h2 class="hk-woo-title">
            <span class="icon-loop hk-woo-icon"></span>
            <?php echo JText::sprintf('PRODUCTS_FROM_X', 'WooCommerce'); ?>
        </h2>
        <p class="hk-woo-subtitle">
            <?php echo JText::sprintf('X_IMPORT_DESC','WooCommerce','WooCommerce'); ?>
        </p>
    </div>

    <!-- Features Grid -->
    <div class="hk-woo-grid-container">
        <h4 class="hk-woo-section-title">
            <?php echo JText::_('MAIN_INFORMATION'); ?>
        </h4>
        <div class="hk-woo-grid">
            <?php 
            $functions = array('TAXATIONS','PRODUCT_CATEGORIES','PRODUCTS','PRODUCT_VARIATIONS','HIKA_IMAGES','PRICES','USERS','ADDRESSES','COUPONS','ORDERS','DOWNLOADS');
            foreach($functions as $v): ?>
                <div class="hk-woo-grid-item">
                    <span class="icon-checkmark hk-woo-check"></span>
                    <?php echo JText::_($v); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Warnings -->
    <div class="hk-woo-warning">
        <div class="hk-woo-warning-flex">
            <span class="icon-warning hk-woo-warning-icon"></span>
            <div>
                <div class="hk-woo-warning-title"><?php echo JText::_('HIKA_CAUTION'); ?></div>
                <?php
                if(defined('HIKASHOP_WORDPRESS')) {
                    echo JText::_('WOO_WARNING_DB_WP');
                } else {
                    echo JText::_('WOO_WARNING_DB') . '<br/>' . JText::_('WOO_WARNING_USERS');
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Connection Settings Form -->
    <fieldset class="hk-woo-fieldset">
        <legend class="hk-woo-legend">
            <?php echo JText::_('CONNECTION_SETTINGS'); ?>
        </legend>

        <table class="admintable table table-striped hk-woo-table">
            <tr>
                <td class="key hk-woo-key">
                    <span class="icon-database hk-woo-key-icon"></span>
                    <?php echo JText::_('DATABASE_NAME'); ?>
                </td>
                <td class="hk-woo-value">
                    <?php
                    $wooDbDefault = '';
                    $wooDbHelp = JText::_('LEAVE_EMPTY_TO_USE_CURRENT_DATABASE');
                    if(defined('HIKASHOP_WORDPRESS') && defined('DB_NAME')) {
                        $wooDbDefault = DB_NAME;
                        $wooDbHelp = JText::sprintf('USING_CURRENT_WORDPRESS_DATABASE_X', DB_NAME);
                    }
                    ?>
                    <input type="text" name="wooDatabase" value="<?php echo htmlspecialchars($wooDbDefault); ?>" class="inputbox hk-woo-input" placeholder="e.g. wordpress_db" />
                    <div class="hk-woo-help">
                        <?php echo $wooDbHelp; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="key hk-woo-key">
                    <span class="icon-cog hk-woo-key-icon"></span>
                    <?php echo JText::_('WOO_TABLE_PREFIX'); ?>
                </td>
                <td class="hk-woo-value">
                    <?php
                    $wooPrefixDefault = 'wp_';
                    if(defined('HIKASHOP_WORDPRESS'))
                        $wooPrefixDefault = JFactory::getDBO()->getPrefix();
                    ?>
                    <input type="text" name="wooPrefix" value="<?php echo htmlspecialchars($wooPrefixDefault); ?>" class="inputbox hk-woo-input-small" />
                </td>
            </tr>
            <tr>
                <td class="key hk-woo-key">
                    <span class="icon-folder hk-woo-key-icon"></span>
                    <?php echo JText::_('WORDPRESS_PATH'); ?>
                </td>
                <td class="hk-woo-value">
                    <?php
                    $wooPathDefault = '';
                    if(defined('HIKASHOP_WORDPRESS') && defined('ABSPATH'))
                        $wooPathDefault = rtrim(ABSPATH, '/\\');
                    ?>
                    <input type="text" name="wooPath" value="<?php echo htmlspecialchars($wooPathDefault); ?>" class="inputbox hk-woo-input" placeholder="/home/user/public_html/wordpress" />
                    <div class="hk-woo-help">
                        <?php echo JText::_('ABSOLUTE_PATH_TO_YOUR_WORDPRESS_INSTALLATION_FOR_IMAGES_FILES'); ?>
                    </div>
                </td>
            </tr>
        </table>
    </fieldset>

    <!-- Import Button -->
    <div class="hk-woo-button-container">
        <button type="button" class="btn btn-primary hk-woo-import-btn" onclick="Joomla.submitbutton('import');">
            <span class="icon-loop" style="margin-right: 8px;"></span>
            <?php echo JText::_('IMPORT'); ?>
        </button>
    </div>
</div>
