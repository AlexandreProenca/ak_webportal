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
class withdrawalController extends hikashopController {
	var $type = 'withdrawal';
	var $modify_views = array();
	var $add = array();
	var $modify = array('save');
	var $delete = array();

	public function __construct($config = array(), $skip = false) {
		parent::__construct($config, $skip);

		$this->display[] = 'form';
	}

	function form() {
		global $Itemid;
		$url_itemid = (!empty($Itemid) ? '&Itemid=' . $Itemid : '');

		$formData = hikaInput::get()->get('withdrawal', array(), 'array');
		$order_id = hikaInput::get()->getInt('order_id');
        if(empty($order_id)) {
			$order_id = hikaInput::get()->getInt('withdrawal_order_id');
        }
        if(empty($order_id)) {
            $cid = hikaInput::get()->get('cid', array(), 'array');
            if(!empty($cid)) {
                $order_id = (int)reset($cid);
            }
        }
        if(empty($order_id)) $order_id = hikaInput::get()->getInt('id');

		if(empty($order_id) && !empty($formData['withdrawal_order_id'])) {
			$order_id = (int)$formData['withdrawal_order_id'];
		}

		$order = null;
		if(!empty($order_id)) {
			$orderClass = hikashop_get('class.order');
			$order = $orderClass->loadFullOrder($order_id);

			if(empty($order)) {
				if(!JFactory::getUser()->guest) {
					$this->app->enqueueMessage(JText::_('ORDER_NOT_FOUND'), 'error');
					$this->setRedirect(hikashop_completeLink('order&task=listing'.$url_itemid));
					return;
				}
			} else {
				$withdrawalClass = hikashop_get('class.withdrawal');
				if(!$withdrawalClass->isWithdrawable($order)) {
					 $this->app->enqueueMessage(JText::_('HIKA_WITHDRAWAL_NOT_ALLOWED'), 'error');
					 $this->setRedirect(hikashop_completeLink('order&task=listing'.$url_itemid));
					 return;
				}

				if($withdrawalClass->hasExistingRequest($order_id)) {
					$this->app->enqueueMessage(JText::_('HIKA_WITHDRAWAL_ALREADY_REQUESTED'), 'info');
					$this->setRedirect(hikashop_completeLink('order&task=listing'.$url_itemid));
					return;
				}
			}
		}

		$view = $this->getView('withdrawal', 'html');
		$view->assignRef('formData', $formData);
		$view->assignRef('order', $order);
		$view->setLayout('form');
		$view->display();
	}

	function save() {
		global $Itemid;
		$url_itemid = (!empty($Itemid) ? '&Itemid=' . $Itemid : '');

		$failLink = JFactory::getUser()->guest
			? hikashop_completeLink('withdrawal&task=form'.$url_itemid)
			: hikashop_completeLink('order&task=listing'.$url_itemid);

		$data = hikaInput::get()->get('withdrawal', array(), 'array');
		$order_id = (int)@$data['withdrawal_order_id'];
		$orderClass = hikashop_get('class.order');
		$user_check = 1;

		if(!empty($order_id)) {
			$order = $orderClass->loadFullOrder($order_id);
		} else {
			$order_number = @$data['order_number'];
			$email = @$data['email'];

			if(empty($order_number) || empty($email)) {
				$this->app->enqueueMessage(JText::_('PLEASE_FILL_IN_ALL_THE_FIELDS'), 'error');
				$this->form();
				return;
			}

			$db = JFactory::getDbo();
			$query = 'SELECT order_id FROM ' . hikashop_table('order') . ' WHERE order_number = ' . $db->Quote($order_number);
			$db->setQuery($query);
			$order_id = (int)$db->loadResult();

			if(empty($order_id)) {
				$this->app->enqueueMessage(JText::_('ORDER_NOT_FOUND'), 'error');
				$this->form();
				return;
			}

			$order = $orderClass->loadFullOrder($order_id, false, false);
			$order_email = isset($order->customer->user_email) ? $order->customer->user_email : '';

			if(empty($order_email) || $order_email !== $email) {
				$this->app->enqueueMessage(JText::_('ORDER_NOT_FOUND'), 'error');
				$this->form();
				return;
			}
			$user_check = 0;

			foreach($order->products as $product) {
				$data['products'][$product->order_product_id] = $product->order_product_quantity;
				$data['selected_products'][$product->order_product_id] = 1;
			}
		}

		if(empty($order)) {
			$this->setRedirect($failLink);
			return;
		}

		$withdrawalClass = hikashop_get('class.withdrawal');
		if(!$withdrawalClass->isWithdrawable($order)) { // Check withdrawable again for direct access safety
			 $this->app->enqueueMessage(JText::_('HIKA_WITHDRAWAL_NOT_ALLOWED'), 'error');
			 $this->setRedirect($failLink);
			 return;
		}

		if($withdrawalClass->hasExistingRequest($order->order_id)) {
			$this->app->enqueueMessage(JText::_('HIKA_WITHDRAWAL_ALREADY_REQUESTED'), 'info');
			$this->setRedirect($failLink);
			return;
		}

		$withdrawal = new stdClass();
		$withdrawal->withdrawal_order_id = $order->order_id;
		$withdrawal->withdrawal_status = 'created';
		$withdrawal->withdrawal_created = time();
		$withdrawal->withdrawal_modified = time();
		$withdrawal->withdrawal_reason = JComponentHelper::filterText(@$data['withdrawal_reason']);
		$withdrawal->withdrawal_user_check = $user_check;

		$order_products = array();
		if(!empty($order->products)) {
			foreach($order->products as $product) {
				$order_products[$product->order_product_id] = $product;
			}
		}

		$selected_products = array();
		if(!empty($data['products'])) {
			 foreach($data['products'] as $pid => $qty) {
				 $qty = (int)$qty;
				 if($qty > 0 && isset($order_products[$pid]) && !empty($data['selected_products'][$pid])) {
					 if($qty > $order_products[$pid]->order_product_quantity) {
						 $qty = $order_products[$pid]->order_product_quantity;
					 }
					 $selected_products[] = array(
						'product_id' => (int)$order_products[$pid]->product_id, 
						'order_product_id' => (int)$pid,
						'quantity' => $qty
					);
				 }
			 }
		}
		$withdrawal->withdrawal_products = $selected_products;

		if($withdrawalClass->save($withdrawal)) {
			 $this->_sendEmail($withdrawal, $order);
			 $this->app->enqueueMessage(JText::_('HIKA_WITHDRAWAL_REQUEST_CREATED'));
		} else {
			 $this->app->enqueueMessage(JText::_('ERROR_SAVING'), 'error');
		}

		if($user_check) {
			$this->setRedirect(hikashop_completeLink('order&task=listing'.$url_itemid));
		} else {
			$this->setRedirect(hikashop_completeLink('withdrawal&task=form'.$url_itemid));
		}
	}

	function _sendEmail($withdrawal, $order) {
		$config = hikashop_config();

		$params = new stdClass();
		$params->order = $order;
		$params->withdrawal = $withdrawal;

		$admin_email = $config->get('withdrawal_notification_email', $config->get('from_email'));
		$user = $order->customer->user_email;

		$mailClass = hikashop_get('class.mail');
		$mail = $mailClass->get('withdrawal_admin_notification', $params);
		$mail->dst_email = $admin_email;
		$mail->subject = JText::sprintf($mail->subject, $order->order_number);
		if($user) {
			$mail->reply_to = $user;
		}
		$mailClass->sendMail($mail);

		if(!empty($user)) {
			$mailClass = hikashop_get('class.mail');
			$mail = $mailClass->get('withdrawal_user_notification', $params);
			$mail->dst_email = $user;
			$mail->subject = JText::sprintf($mail->subject, $order->order_number);
			$mailClass->sendMail($mail);
		}
	}
}
