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
class DashboardController extends hikashopController{
	var $type = 'widget';

	function __construct($config = array()) {
		$this->display = array('listing','csv','cpanel','reports','config','settings','loadPreset');
		$this->modify_views = array('edit');
		$this->add = array('add');
		$this->modify = array('save');
		$this->delete = array('delete','remove');
		parent::__construct($config);
	}

	function cpanel() {
		hikaInput::get()->set('layout', 'cpanel');
		return $this->display();
	}

	function config() {
		hikaInput::get()->set('layout', 'config');
		return $this->display();
	}



	function settings() {
		hikaInput::get()->set('layout', 'settings');
		$this->display();
	}



	function loadPreset() {
		if(!JFactory::getUser()->authorise('core.admin', 'com_hikashop')) {
			echo '[]';
			exit;
		}

		$preset = hikaInput::get()->getCmd('name');
		$layout = $this->getPresetLayout($preset);

		echo json_encode($layout);
		exit;
	}

	private function getPresetLayout($preset) {
		if($preset == 'current') {
			$config = hikashop_config();
			$layoutStr = $config->get('dashboard_layout_cpanel');
			if(empty($layoutStr) || $layoutStr == '[]') {
				$preset = 'default';
			} else {
				return !empty($layoutStr) ? json_decode($layoutStr, true) : array();
			}
		}

		$layout = array();

		switch($preset) {
			case 'maps':
				$layout = array(
					array(
						'widgets' => array(
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Europe', 'REGION' => '150')),
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'North America', 'REGION' => '021')),
						)
					),
					array(
						'widgets' => array(
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Africa', 'REGION' => '002')),
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'South America', 'REGION' => '005')),
						)
					),
					array(
						'widgets' => array(
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Asia', 'REGION' => '142')),
							array('key' => 'geo_sales', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Oceania', 'REGION' => '009')),
						)
					)
				);
				break;
			case 'orders':
				$layout = array(
					array(
						'widgets' => array(
							array('key' => 'last_orders', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Confirmed Orders', 'order_status' => array('confirmed'))),
							array('key' => 'last_orders', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Shipped Orders', 'order_status' => array('shipped'))),
						)
					),
					array(
						'widgets' => array(
							array('key' => 'last_orders', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Created Orders', 'order_status' => array('created'))),
							array('key' => 'last_orders', 'width' => 'hkc-lg-6 hkc-sm-12', 'params' => array('custom_title' => 'Refunded Orders', 'order_status' => array('refunded'))),
						)
					)
				);
				break;
			case 'default':
				$layout = array(
					array(
						'widgets' => array(
							array('key' => 'sales_sum', 'width' => 'hkc-lg-3 hkc-sm-6', 'params' => array('custom_title' => JText::_('HIKA_STATS_ORDERS_THIS_MONTH'))),
							array('key' => 'sales_count', 'width' => 'hkc-lg-3 hkc-sm-6', 'params' => array('custom_title' => JText::_('HIKA_STATS_TOTAL_ORDERS'))),
							array('key' => 'sales_avg', 'width' => 'hkc-lg-3 hkc-sm-6', 'params' => array('custom_title' => JText::_('HIKA_STATS_AVERAGE_ORDER_PRICE'))),
							array('key' => 'sales_today_count', 'width' => 'hkc-lg-3 hkc-sm-6', 'params' => array('custom_title' => JText::_('HIKA_STATS_CURRENT_ORDERS'))),
						)
					),
					array(
						'widgets' => array(
							array('key' => 'orders_history', 'width' => 'hkc-lg-8 hkc-sm-12', 'params' => array('custom_title' => JText::_('ORDERS'))),
							array('key' => 'best_product', 'width' => 'hkc-lg-2 hkc-sm-3', 'params' => array('custom_title' => JText::_('HIKA_STATS_BEST_PRODUCT'))),
							array('key' => 'best_category', 'width' => 'hkc-lg-2 hkc-sm-3', 'params' => array('custom_title' => JText::_('HIKA_STATS_BEST_CATEGORY'))),
							array('key' => 'best_customer', 'width' => 'hkc-lg-2 hkc-sm-3', 'params' => array('custom_title' => JText::_('HIKA_STATS_BEST_CUSTOMER'))),
							array('key' => 'conversion_rate', 'width' => 'hkc-lg-2 hkc-sm-3', 'params' => array('custom_title' => JText::_('HIKA_STATS_CONVERSION_RATE'))),
						)
					),
					array(
						'widgets' => array(
							array('key' => 'geo_sales', 'width' => 'hkc-lg-4 hkc-sm-12', 'params' => array('custom_title' => JText::_('HIKA_STATS_GEO_SALES'))),
							array('key' => 'last_orders', 'width' => 'hkc-lg-8 hkc-sm-12', 'params' => array('custom_title' => JText::_('HIKA_STATS_LAST_ORDERS'))),
						)
					)
				);
				break;
		}

		return $layout;
	}

	function save() {
		if(!JFactory::getUser()->authorise('core.admin', 'com_hikashop')) {
			$this->setRedirect(hikashop_completeLink('dashboard',false,true), JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			return;
		}

		$layout = hikaInput::get()->get('layout', '', 'raw');
		$layout = json_decode($layout, true);

		if($layout) {
			$config = hikashop_config();





			$layout = $this->validateLayout($layout);
			if(empty($layout)) {
				$this->setRedirect(hikashop_completeLink('dashboard',false,true), 'Invalid Layout Data', 'error');
				return;
			}

			$saveData = new stdClass();
			$saveData->dashboard_layout_cpanel = json_encode($layout);
			if($config->save($saveData)) {
				$this->setRedirect(hikashop_completeLink('dashboard',false,true), JText::_('HIKASHOP_SUCC_SAVED'));
			} else {
				$this->setRedirect(hikashop_completeLink('dashboard',false,true), JText::_('ERROR_SAVING'), 'error');
			}
		} else {
			$this->setRedirect(hikashop_completeLink('dashboard',false,true), 'Error: Data from browser is not valid', 'error');
		}
	}

	public function reports() {
		$statName = hikaInput::get()->getCmd('chart', '');
		$statValue = hikaInput::get()->getString('value', '');
		if(empty($statName) || empty($statValue)) {
			echo '[]';
			exit;
		}

		$statisticsClass = hikashop_get('class.statistics');
		$ret = $statisticsClass->getAjaxData($statName, $statValue);

		if($ret === false) {
			echo '[]';
			exit;
		}
		echo $ret;
		exit;
	}

	private function validateLayout($layout) {
		if(!is_array($layout)) return false;

		if(version_compare(JVERSION, '4.0', '<')) {
			jimport('joomla.application.component.helper');
		}

		$cleanLayout = array();
		foreach($layout as $rowIndex => $row) {
			if(!isset($row['widgets']) || !is_array($row['widgets'])) continue;

			$cleanRow = array('widgets' => array());
			foreach($row['widgets'] as $widget) {
				if(empty($widget['key'])) continue;

				$cleanWidget = array(
					'key' => JComponentHelper::filterText($widget['key']),
					'width' => isset($widget['width']) ? JComponentHelper::filterText($widget['width']) : 'hkc-sm-12'
				);

				if(isset($widget['params']) && is_array($widget['params'])) {
					$cleanWidget['params'] = array();
					foreach($widget['params'] as $k => $v) {
						$cleanKey = JComponentHelper::filterText($k);
						if(is_array($v)) {
							$cleanWidget['params'][$cleanKey] = array_map(array('JComponentHelper', 'filterText'), $v);
						} else {
							$cleanWidget['params'][$cleanKey] = JComponentHelper::filterText((string)$v);
						}
					}
				}
				$cleanRow['widgets'][] = $cleanWidget;
			}
			if(!empty($cleanRow['widgets'])) {
				$cleanLayout[] = $cleanRow;
			}
		}
		return $cleanLayout;
	}
}
