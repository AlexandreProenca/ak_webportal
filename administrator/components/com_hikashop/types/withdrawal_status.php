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
class hikashopWithdrawal_statusType extends hikashopType {
	public function __construct() {
		$this->values = array();
	}

	protected function load() {
		$statuses = array('created', 'processed', 'rejected', 'approved');
		foreach($statuses as $status) {
			$this->values[$status] = JHTML::_('select.option', $status, JText::_('HIKA_WITHDRAWAL_STATUS_' . strtoupper($status)));
		}
	}

	public function display($map, $value, $extra = '', $addAll = false) {
		if(empty($this->values))
			$this->load();

		if($addAll) {
			if(empty($value))
				$value = 'all';
			$values = array_merge(
				array(JHTML::_('select.option', 'all', JText::_('ALL_STATUSES'))),
				$this->values
			);
		} else {
			$values = $this->values;
		}
		if(empty($extra))
			$extra = 'class="custom-select"';
		return JHTML::_('select.genericlist', $values, $map, $extra, 'value', 'text', $value);
	}

	public function displayFilter($key, $filterValues, $extra = '', $addAll = true) {
		return $this->display('filter_'.$key, $filterValues, $extra.' class="custom-select" onchange="this.form.submit();"', $addAll);
	}
}
