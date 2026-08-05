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
	.hk-j2-card { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
	.hk-j2-header { text-align: center; margin-bottom: 30px; }
	.hk-j2-title { margin: 0; color: #333; font-weight: 600; font-size: 24px; }
	.hk-j2-icon { color: #96588a; margin-right: 10px; }
	.hk-j2-subtitle { color: #666; margin-top: 10px; font-size: 15px; }
	.hk-j2-grid-container { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
	.hk-j2-section-title { margin-top: 0; color: #495057; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
	.hk-j2-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
	.hk-j2-grid-item { display: flex; align-items: center; color: #495057; font-size: 14px; }
	.hk-j2-check { color: #28a745; margin-right: 8px; font-size: 12px; }
	.hk-j2-fieldset { border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; }
	.hk-j2-legend { width: auto; padding: 0 10px; font-weight: 600; color: #333; font-size: 16px; border-bottom: none; margin-bottom: 0; }
	.hk-j2-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.hk-j2-key { width: 200px; padding: 12px; font-weight: 500; color: #333; }
	.hk-j2-key-icon { color: #6c757d; margin-right: 5px; }
	.hk-j2-value { padding: 12px; }
	.hk-j2-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; max-width: 400px; font-size: 14px; }
	.hk-j2-button-container { text-align: center; margin-top: 25px; }
	.hk-j2-import-btn { background-color: #96588a; border-color: #96588a; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px; cursor: pointer; transition: background 0.2s; display: flex; justify-content: center; align-items: center; margin: 0 auto; }
	.hk-j2-import-btn:hover { background-color: #824b78; border-color: #824b78; }
	button.btn.btn-primary.hk-j2-import-btn { display: flex; justify-content: center; align-items: center; margin: 0 auto;}
</style>

<div class="hk-j2-card">

    <!-- Header -->
    <div class="hk-j2-header">
        <h2 class="hk-j2-title">
            <span class="icon-loop hk-j2-icon"></span>
            <?php echo JText::sprintf('PRODUCTS_FROM_X', 'J2Store'); ?>
        </h2>
        <p class="hk-j2-subtitle">
            <?php echo JText::sprintf('X_IMPORT_DESC','J2Store','J2Store'); ?>
        </p>
    </div>

    <!-- Features Grid -->
    <div class="hk-j2-grid-container">
        <h4 class="hk-j2-section-title">
            <?php echo JText::_('MAIN_INFORMATION'); ?>
        </h4>
        <div class="hk-j2-grid">
            <?php 
            $functions = array('TAXATIONS','MANUFACTURERS','PRODUCT_CATEGORIES','PRODUCTS','PRICES','USERS','COUPONS','ORDERS','DOWNLOADS');
            foreach($functions as $v): ?>
                <div class="hk-j2-grid-item">
                    <span class="icon-checkmark hk-j2-check"></span>
                    <?php echo JText::_($v); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Connection Settings Form -->
    <fieldset class="hk-j2-fieldset">
        <legend class="hk-j2-legend">
            <?php echo JText::_('CONNECTION_SETTINGS'); ?>
        </legend>

        <table class="admintable table table-striped hk-j2-table">
            <tr>
                <td class="key hk-j2-key">
                    <span class="icon-cog hk-j2-key-icon"></span>
                    <?php echo JText::_('J2STORE_TABLE_PREFIX'); ?>
                </td>
                <td class="hk-j2-value">
                    <input type="text" name="j2Prefix" value="#__j2store_" class="inputbox hk-j2-input" />
                </td>
            </tr>
        </table>
    </fieldset>

    <!-- Import Button -->
    <div class="hk-j2-button-container">
        <button type="button" class="btn btn-primary hk-j2-import-btn" onclick="Joomla.submitbutton('import');">
            <span class="icon-loop" style="margin-right: 8px;"></span>
            <?php echo JText::_('IMPORT'); ?>
        </button>
    </div>
</div>
