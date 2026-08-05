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
if(!defined('HIKASHOP_WORDPRESS')) {
	echo '<p>This page is only available on WordPress.</p>';
	return;
}
?>
<p><?php echo JText::_('INSTALL_HIKASHOP_PLUGIN_DESC'); ?></p>
<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="ctrl" value="plugins" />
	<input type="hidden" name="task" value="install" />
	<input type="hidden" name="plugin_type" value="<?php echo $this->plugin_type; ?>" />
	<?php echo JHTML::_('form.token'); ?>
	<table class="admintable table">
		<tr>
			<td class="key"><label for="hikashop_plugin_zip"><?php echo JText::_('HIKA_FILE'); ?></label></td>
			<td><input type="file" id="hikashop_plugin_zip" name="hikashop_plugin_zip" accept=".zip" required /></td>
		</tr>
	</table>
	<input type="submit" class="btn btn-primary" value="<?php echo JText::_('INSTALL_HIKASHOP_PLUGIN'); ?>" />
</form>
