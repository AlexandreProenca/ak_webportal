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
class WithdrawalViewWithdrawal extends hikashopView {
    var $type = 'withdrawal';
    var $ctrl = 'withdrawal';
    var $nameForm = 'HIKA_WITHDRAW_FROM_CONTRACT';
    var $icon = 'out'; 

    function display($tpl = null) {
		$app = JFactory::getApplication();
		$pathway = $app->getPathway();
		$pathway->addItem(JText::_('HIKA_WITHDRAW_FROM_CONTRACT'));

		$menus	= $app->getMenu();
		$menu	= $menus->getActive();
		$show_page_heading = true;
		$url = 'withdrawal';

		$params = null;
		if(!empty($menu) && method_exists($menu, 'getParams')) {
			$params = $menu->getParams();
			$show_page_heading = $params->get('show_page_heading');
		}
		if(is_null($show_page_heading)) {
			$com_menus = JComponentHelper::getParams('com_menus');
			if(!empty($com_menus))
				$show_page_heading = $com_menus->get('show_page_heading');
		}
		if(!empty($menu) && method_exists($menu, 'getParams') && strpos($menu->link, 'index.php?option=com_hikashop&view=withdrawal') !== false) {
			if($show_page_heading)
				$this->title = $params->get('page_heading');
			$title = $params->get('page_title');
			if(empty($title))
				$title = $menu->title;
			hikashop_setPageTitle($title);

			$robots = $params->get('robots');
			if (!$robots) {
				$jconfig = JFactory::getConfig();
				$robots = $jconfig->get('robots', '');
			}
			if($robots) {
				$doc = JFactory::getDocument();
				$doc->setMetadata('robots', $robots);
			}

			$url = '';

		} else {
			if($show_page_heading)
				$this->title = JText::_('HIKA_WITHDRAW_FROM_CONTRACT');
			hikashop_setTitle(JText::_($this->nameForm), $this->icon, $this->ctrl);
		}

		$item_id = hikaInput::get()->getInt('Itemid');
		if($item_id) {
			$url .= '&Itemid='.$item_id;
		}
		$this->url = hikashop_completeLink($url);

		$this->currencyHelper = hikashop_get('helper.currency');
		$this->fieldsClass = hikashop_get('class.field');
		$this->imageHelper = hikashop_get('helper.image');

		parent::display($tpl);
    }
}
