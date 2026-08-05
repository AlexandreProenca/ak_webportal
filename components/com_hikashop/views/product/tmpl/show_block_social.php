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
$pluginsClass = hikashop_get('class.plugins');
$plugin = $pluginsClass->getByName('system', 'hikashopsocial');
if (!empty($plugin)) {
	echo '{hikashop_social}';
} else { //backward compatibility added on 31/07/2014
	$plugin = $pluginsClass->getByName('content', 'hikashopsocial');
	if (!empty($plugin)) {
		echo '{hikashop_social}';
	}
}
