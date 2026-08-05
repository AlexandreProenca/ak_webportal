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
class hikashopWithdrawalClass extends hikashopClass {
    var $tables = array('withdrawal');
    var $pkeys = array('withdrawal_id');

	function saveForm() {
        $data = hikaInput::get()->get('data', array(), 'array');
        $element = new stdClass();
        foreach($data['withdrawal'] as $key => $value) {
            $element->$key = $value;
        }

        $element->withdrawal_products = array();
        if(!empty($data['products']) && !empty($data['selected_products'])) {
            foreach($data['selected_products'] as $product_id => $active) {
                if(empty($active)) continue;
                if(isset($data['products'][$product_id])) {
                    $obj = new stdClass();
                    $obj->order_product_id = $product_id; // Using order_product_id as key as per implementation
                    $obj->quantity = (int)$data['products'][$product_id];
                    $element->withdrawal_products[] = $obj;
                }
            }
        }

        return $this->save($element);
    }

	function save(&$element) {
		if(isset($element->withdrawal_products) && is_array($element->withdrawal_products)) {
			$element->withdrawal_products = json_encode($element->withdrawal_products);
		}

		$pkey = 'withdrawal_id';
		$new = true;
		$element->old = new stdClass();
		if(!empty($element->$pkey)) {
			$new = false;
			$element->old = $this->get($element->$pkey);
		}

		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashopshipping');
		JPluginHelper::importPlugin('hikashoppayment');
		$app = JFactory::getApplication();
		$do = true;
		if($new)
			$app->triggerEvent('onBeforeWithdrawalCreate', array(&$element, &$do));
		else
			$app->triggerEvent('onBeforeWithdrawalUpdate', array(&$element, &$do));

		if(!$do)
			return false;

		$status = parent::save($element);

		if(!$status)
			return false;

		if($new) {
			$element->$pkey = $status;
			$app->triggerEvent('onAfterWithdrawalCreate', array(&$element));
		} else {
			$app->triggerEvent('onAfterWithdrawalUpdate', array(&$element));
		}

		return $status;
	}    
    function load($id, $columns = null) {
        $element = parent::load($id, $columns);
        if($element && !empty($element->withdrawal_products)) {
            $decoded = json_decode($element->withdrawal_products);
            if($decoded) $element->withdrawal_products = $decoded;
        }

        if(!empty($element->withdrawal_order_id)) {
            $orderClass = hikashop_get('class.order');
            $element->order = $orderClass->get($element->withdrawal_order_id);
            if(!empty($element->order)) {
                $orderClass->loadProducts($element->order);
                if(!empty($element->order->order_user_id))
                    $element->order->customer = hikashop_get('class.user')->get($element->order->order_user_id);
            }
        }

        return $element;
    }

    public function isWithdrawable($order) {
		if(empty($order)) return false;

		$config = hikashop_config();
		$allowed_statuses = explode(',', $config->get('withdrawal_order_statuses', 'confirmed,shipped,delivered'));
		if(!in_array($order->order_status, $allowed_statuses)) {
			return false;
		}

		if(empty($order->order_shipping_id)) {
			$download_statuses = explode(',', $config->get('order_status_for_download', 'shipped,confirmed'));
			if(in_array($order->order_status, $download_statuses)) {
				$db = JFactory::getDbo();
				$db->setQuery('SELECT 1 FROM ' . hikashop_table('download') . ' WHERE order_id = ' . (int)$order->order_id . ' LIMIT 1');
				if($db->loadResult())
					return false;
			}
		}

		$period = (int)$config->get('withdrawal_period', 14);
        $period_column_field = 'withdrawal_period_column_field_'. $order->order_status;
		switch($order->order_status) {
			case 'shipped':
                $ref_date_field_default = 'order_shipped';
				break;
			case 'delivered':
                $ref_date_field_default = 'order_delivered';
				break;
			case 'confirmed':
                $ref_date_field_default = 'order_invoice_created';
				break;
			case 'created':
                $ref_date_field_default = 'order_created';
				break;
			default:
                $ref_date_field_default = 'order_created';
				break;
		}
        $ref_date_field = $config->get($period_column_field, $ref_date_field_default);
        $ref_date = (int)@$order->$ref_date_field;

		if(empty($ref_date)) return false;

		$limit_time = $ref_date + ($period * 24 * 3600);

		return (time() <= $limit_time);
	}

	public function hasExistingRequest($order_id) {
		$order_id = (int)$order_id;
		if(empty($order_id)) return false;

		$db = JFactory::getDbo();
		$query = 'SELECT withdrawal_id FROM ' . hikashop_table('withdrawal') .
			' WHERE withdrawal_order_id = ' . $order_id .
			' AND withdrawal_status != ' . $db->Quote('rejected');
		$db->setQuery($query, 0, 1);
		return (bool)$db->loadResult();
	}

	public function &getNameboxData($typeConfig, &$fullLoad, $mode, $value, $search, $options) {
		$ret = array(
			0 => array(),
			1 => array()
		);

		$fullLoad = false;
		$limit = 20;
		$query = 'SELECT a.*, b.order_number FROM '.hikashop_table('withdrawal').' AS a LEFT JOIN '.hikashop_table('order').' AS b ON a.withdrawal_order_id=b.order_id ';
		if(!empty($search)){
			$search = $this->database->Quote('%'.$search.'%');
			$query .= ' WHERE (a.withdrawal_id LIKE '.$search.' OR b.order_number LIKE '.$search.')';
		}
		$query .= ' ORDER BY a.withdrawal_id DESC';

		$this->database->setQuery($query, 0, $limit);
		$elements = $this->database->loadObjectList();

		foreach($elements as $element) {
			$ret[0][$element->withdrawal_id] = $element;
		}

		if(!empty($value)) {
			if($mode == 1 && isset($ret[0][$value])) { // NAMEBOX_SINGLE
				$ret[1][$value] = $ret[0][$value];
			} elseif($mode == 2 && is_array($value)) { // NAMEBOX_MULTIPLE
				foreach($value as $v) {
					if(isset($ret[0][$v])) {
						$ret[1][$v] = $ret[0][$v];
					}
				}
			} else {
				$query = 'SELECT a.*, b.order_number FROM '.hikashop_table('withdrawal').' AS a LEFT JOIN '.hikashop_table('order').' AS b ON a.withdrawal_order_id=b.order_id WHERE a.withdrawal_id '.(is_array($value) ? 'IN ('.implode(',',$value).')' : ' = '.(int)$value);
				$this->database->setQuery($query);
				$elements = $this->database->loadObjectList();
				foreach($elements as $element) {
					$ret[1][$element->withdrawal_id] = $element;
				}
			}
		}

		return $ret;
	}
}
