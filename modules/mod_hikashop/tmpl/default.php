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
?><?php if(!empty($html)){ ?>
<div id="hikashop_module_<?php echo $module->id;?>" class="hikashop_module <?php echo (!empty($module->params) && is_array($module->params) ? @$module->params['moduleclass_sfx'] : ''); ?>">
<?php echo $html; ?>
</div>
<?php } ?>
