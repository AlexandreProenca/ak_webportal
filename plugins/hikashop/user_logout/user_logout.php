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
class plgHikashopUser_logout extends JPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		if(isset($this->params))
			return;

		$plugin = JPluginHelper::getPlugin('hikashop', 'user_logout');
		$this->params = new JRegistry($plugin->params);
	}

	function onUserAccountDisplay(&$buttons){
		global $Itemid;
		$url_itemid = '';
		if(!empty($Itemid)) {
			$url_itemid = '&Itemid='.$Itemid;
		}
		$url = JRoute::_('index.php?option=com_users&task=user.logout&'.hikashop_getFormToken().'=1'.$url_itemid);

		$my = array(
			'joomla_user_logout' => array(
				'link' => $url,
				'level' => 0,
				'image' => 'user2',
				'text' => JText::_('HIKA_LOGOUT'),
				'description' => JText::_('HIKA_LOGOUT_DESCRIPTION'),
				'fontawesome' => ''.
					'<i class="fa fa-user fa-stack-2x"></i>'
			)
		);
		$buttons = array_merge($buttons, $my);
		return true;
	}

}
