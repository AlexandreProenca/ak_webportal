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
if(empty($this->buttons))
	return;

$active = !empty($this->cpanel_active_page) ? $this->cpanel_active_page : '';
?>
<div class="hika_cpanel_side_bar hkc-md-3">
	<div class="hika_cpanel_icons">
<?php
foreach($this->buttons as $name => $btn) {
	$data = isset($btn['counter']) ? $btn['counter'] : false;
	$activeClass = ($name === $active) ? ' hika_cpanel_icon_active' : '';
?>
		<a class="hika_cpanel_icon hikashop_cpanel_<?php echo $name; ?>_div<?php echo $activeClass; ?>" href="<?php echo hikashop_level($btn['level']) ? $btn['link'] : '#'; ?>" data-toggle="hk-tooltip" data-title="<?php echo htmlspecialchars('<strong>'.$btn['text'].'</strong><br/>'.$btn['description']); ?>">
<?php
		if (!empty($btn['fontawesome'])) {
?>
			<span class="hk-icon fa-stack fa-2x <?php echo $btn['image']; ?>"><?php echo $btn['fontawesome']; ?></span>
<?php
		} else {
?>
			<span class="hkicon-48 icon-48-<?php echo $btn['image']; ?>" title="<?php echo $btn['text']; ?>"></span>
<?php
		}
?>
			<span class="hikashop_cpanel_button_text"><?php echo $btn['text'];?></span>
		</a>
<?php
}
?>
	</div>
</div>
