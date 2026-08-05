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
    var $nameListing = 'HIKA_WITHDRAWAL_LISTING';
    var $nameForm = 'HIKA_WITHDRAWAL_MANAGE';
    var $icon = 'money'; 
	function display($tpl = null){
		$this->paramBase = HIKASHOP_COMPONENT.'.'.$this->getName();
		$function = $this->getLayout();
		if(method_exists($this,$function)) $this->$function();
		parent::display($tpl);
	}

    function listing() {
        $app = JFactory::getApplication();
        $db = JFactory::getDBO();

        $this->loadRef(array('toggleHelper' => 'helper.toggle'));

        $pageInfo = $this->getPageInfo('withdrawal_created'); 

        $filters = array();
        $order = '';
        $searchMap = array('a.withdrawal_id', 'a.withdrawal_status', 'o.order_number', 'u.user_email');

        $status = $app->getUserStateFromRequest($this->paramBase . '.filter_status', 'filter_status', '', 'cmd');
        $pageInfo->filter->status = $status;
        if(!empty($status) && $status != 'all') {
            $filters[] = 'a.withdrawal_status=' . $db->Quote($status);
        }

        $this->processFilters($filters, $order, $searchMap);
        $query = ' FROM ' . hikashop_table('withdrawal') . ' AS a LEFT JOIN '.hikashop_table('order').' AS o ON a.withdrawal_order_id=o.order_id LEFT JOIN '.hikashop_table('user').' AS u ON o.order_user_id=u.user_id ' . $filters . $order;
        $this->getPageInfoTotal($query);
        $db->setQuery('SELECT a.*, o.*, u.*' . $query, $pageInfo->limit->start, $pageInfo->limit->value);
        $rows = $db->loadObjectList();

        $this->assignRef('rows', $rows);
        $this->assignRef('pageInfo', $pageInfo);
        $this->getPagination();

        hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl);

        $this->toolbar = array(
            'editList',
            'deleteList',
            '|',
            'dashboard'
        );
    }

    function form() {
         $element_id = hikashop_getCID('withdrawal_id');
         $withdrawalClass = hikashop_get('class.withdrawal');
         $element = $withdrawalClass->get($element_id);

         if(!empty($element->withdrawal_order_id)) {
             $orderClass = hikashop_get('class.order');
             $element->order = $orderClass->loadFullOrder($element->withdrawal_order_id);
         }

         $this->assignRef('element', $element);

         hikashop_setTitle(JText::_($this->nameForm), $this->icon, $this->ctrl . '&task=edit&withdrawal_id=' . $element->withdrawal_id);

         $this->toolbar = array(
             'save',
             'apply',
             'cancel',
             '|',
             'dashboard'
         );
    }
}
