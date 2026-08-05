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
$button_type = $this->config->get('action_button_type', 'button');
$attributes =  $this->params->get('attributes');

switch($button_type) {
	case 'a':
?>
	<a <?php echo $this->params->get('attributes'); ?> rel="nofollow" href="<?php echo $this->params->get('fallback_url'); ?>"><span><?php echo $this->params->get('content'); ?></span></a>
<?php
		break;
	case 'button':
	default:
		if(strpos($attributes,'onclick') === false) {
			$attributes .= ' onclick="window.location=this.getAttribute(\'data-href\')"';
		}
?>
	<button type="button" <?php echo $attributes; ?> data-href="<?php echo $this->params->get('fallback_url'); ?>"><span><?php echo $this->params->get('content'); ?></span></button>
<?php
		break;
}
?>
