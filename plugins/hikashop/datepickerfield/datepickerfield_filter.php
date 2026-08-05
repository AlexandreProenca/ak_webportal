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
class hikashopFilterDatepickerfieldBaseClass extends hikashopFilterTypeClass {

	protected function initDatepickerAssets() {
		static $done = false;
		if($done)
			return;
		$done = true;

		hikashop_loadJsLib('jquery');

		if(hikashop_isClient('administrator')) {
			$base = '..';
		} else {
			$base = JURI::base(true);
		}

		$doc = JFactory::getDocument();
		$doc->addScript($base . '/plugins/hikashop/datepickerfield/jquery-ui-i18n.js');

		$lang = JFactory::getLanguage();
		$tag = $lang->getTag();
		$conversionTable = array(
			'af-ZA' => 'af', 'ar-AR' => 'ar', 'eu-ES' => 'eu', 'bg-BG' => 'bg',
			'ca-ES' => 'ca', 'zh-CN' => 'zh-CN', 'zh-TW' => 'zh-TW', 'bs-BA' => 'bs',
			'cs-CZ' => 'cs', 'da-DK' => 'da', 'nl-NL' => 'nl', 'en-AU' => 'en-AU',
			'en-NZ' => 'en-NZ', 'fi-FI' => 'fi', 'fr-FR' => 'fr', 'fr-CA' => 'fr',
			'fr-CH' => 'fr-CH', 'gl-ES' => 'gl', 'de-DE' => 'de', 'el-GR' => 'el',
			'he-IL' => 'he', 'hu-HU' => 'hu', 'it-IT' => 'it', 'ja-JP' => 'ja',
			'ko-KR' => 'ko', 'lv-LV' => 'lv', 'lt-LT' => 'lt', 'mk-MK' => 'mk',
			'nb-NO' => 'no', 'fa-IR' => 'fa', 'pl-PL' => 'pl', 'pt-BR' => 'pt-BR',
			'pt-PT' => 'pt', 'ro-RO' => 'ro', 'ru-RU' => 'ru', 'sr-RS' => 'sr',
			'es-ES' => 'es', 'sk-SK' => 'sk', 'sl-SL' => 'sl', 'sv-SE' => 'sv',
			'th-TH' => 'th', 'tr-TR' => 'tr', 'uk-UA' => 'uk', 'vi-VN' => 'vi',
		);
		$dpTag = isset($conversionTable[$tag]) ? $conversionTable[$tag] : 'en-GB';
		$doc->addScriptDeclaration('hkjQuery(function(){ hkjQuery.datepicker.setDefaults(hkjQuery.datepicker.regional[\'' . $dpTag . '\']); });');
	}

	protected function initRangeJs() {
		hikashopDatepickerfield_loadRangeJs();
	}

	protected function formatDateForDisplay($value, &$filter) {
		if(empty($value) || strlen($value) < 8)
			return '';

		$y = (int)substr($value, 0, 4);
		$m = (int)substr($value, 4, 2);
		$d = (int)substr($value, 6, 2);
		if($y == 0 || $m == 0 || $d == 0)
			return '';

		$timestamp = mktime(0, 0, 0, $m, $d, $y);

		$fieldDef = $this->getFieldDefinition($filter);
		$format = @$fieldDef->field_options['format'];
		if(!empty($format)) {
			if(strpos($format, '%') !== false)
				$format = str_replace(array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'), array('l','d','F','m','Y','y','H','i','s','D'), $format);
			return date($format, $timestamp);
		}

		return date('d/m/Y', $timestamp);
	}

	protected function getJQueryDateFormat(&$filter) {
		$fieldDef = $this->getFieldDefinition($filter);
		$format = @$fieldDef->field_options['format'];
		if(!empty($format)) {
			if(strpos($format, '%') !== false)
				$format = str_replace(array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'), array('l','d','F','m','Y','y','H','i','s','D'), $format);
			return str_replace(
				array('j','d', 'z','D','l', 'n','m', 'M','F', 'y','Y'),
				array('d','dd','o','D','DD','m','mm','M','MM','y','yy'),
				$format
			);
		}
		return 'dd/mm/yy';
	}

	protected function getOnSelectJs(&$filter, $divName, &$parent, $elementId) {
		if(empty($filter->filter_direct_application))
			return '';

		if(!empty($parent->ajax) && ($parent->params->get('module') != 'mod_hikashop_filter' || !$parent->params->get('force_redirect', 0) || $parent->itemid == $parent->params->get('itemid')))
			return 'window.hikashop.refreshFilters(document.getElementById(\'' . $elementId . '\'));';

		return 'document.forms[\'hikashop_filter_form_' . $divName . '\'].submit();';
	}

	protected function getAvailableDates(&$filter, $datas) {
		if(empty($datas['products']))
			return array();

		$fieldName = @$filter->filter_options['custom_field'];
		if(empty($fieldName))
			return array();

		$productIds = array();
		foreach($datas['products'] as $p) {
			if(!empty($p->product_id))
				$productIds[] = (int)$p->product_id;
		}
		if(empty($productIds))
			return array();

		$database = JFactory::getDBO();
		$fn = $database->quoteName($fieldName);
		$query = 'SELECT DISTINCT LEFT(' . $fn . ', 8) FROM ' . hikashop_table('product')
			. ' WHERE product_id IN (' . implode(',', $productIds) . ')'
			. ' AND ' . $fn . ' != \'\'';
		$database->setQuery($query);
		return $database->loadColumn();
	}

	protected function getDateBounds(&$filter, $datas) {
		if(empty($datas['products']))
			return null;

		$fieldName = @$filter->filter_options['custom_field'];
		if(empty($fieldName))
			return null;

		$productIds = array();
		foreach($datas['products'] as $p) {
			if(!empty($p->product_id))
				$productIds[] = (int)$p->product_id;
		}
		if(empty($productIds))
			return null;

		$database = JFactory::getDBO();
		$fn = $database->quoteName($fieldName);

		if(!empty($filter->filter_options['range_field'])) {
			$query = 'SELECT MIN(LEFT(' . $fn . ', 8)) as min_date, MAX(SUBSTRING(' . $fn . ', 16, 8)) as max_date'
				. ' FROM ' . hikashop_table('product')
				. ' WHERE product_id IN (' . implode(',', $productIds) . ')'
				. ' AND ' . $fn . ' != \'\'';
		} else {
			$query = 'SELECT MIN(LEFT(' . $fn . ', 8)) as min_date, MAX(LEFT(' . $fn . ', 8)) as max_date'
				. ' FROM ' . hikashop_table('product')
				. ' WHERE product_id IN (' . implode(',', $productIds) . ')'
				. ' AND ' . $fn . ' != \'\'';
		}
		$database->setQuery($query);
		return $database->loadObject();
	}

	protected function isItemField(&$filter) {
		$fieldDef = $this->getFieldDefinition($filter);
		if(!empty($fieldDef) && $fieldDef->field_table == 'item')
			return true;
		return false;
	}

	protected function getFieldDefinition(&$filter) {
		static $cache = array();
		$fieldName = @$filter->filter_options['custom_field'];
		if(empty($fieldName))
			return null;
		if(!isset($cache[$fieldName])) {
			$fieldClass = hikashop_get('class.field');
			$cache[$fieldName] = $fieldClass->getField($fieldName, 'product');
			if(empty($cache[$fieldName]))
				$cache[$fieldName] = $fieldClass->getField($fieldName, 'item');
		}
		return $cache[$fieldName];
	}

	protected function dateToJs($yyyymmdd) {
		$y = substr($yyyymmdd, 0, 4);
		$m = (int)substr($yyyymmdd, 4, 2) - 1; // JS months are 0-based
		$d = (int)substr($yyyymmdd, 6, 2);
		return 'new Date(' . $y . ',' . $m . ',' . $d . ')';
	}
}


class hikashopFilterDatepickerfieldClass extends hikashopFilterDatepickerfieldBaseClass {

	function display(&$filter, $divName, &$parent, $datas = '') {
		$selected = parent::display($filter, $divName, $parent);
		if(!is_array($selected))
			$selected = array($selected);

		if(!$filter->filter_dynamic)
			$datas = '';

		if(!empty($selected)) {
			if($filter->filter_deletable && isset($selected[0]) && $selected[0] != $filter->filter_namekey && $selected[0] != 'none' && trim(str_replace('::', '', $selected[0])) != '') {
				return parent::displayInList($filter, $divName, $selected);
			}
		}

		$selectedDate = '';
		if(!empty($selected[0]) && $selected[0] != $filter->filter_namekey && $selected[0] != 'none')
			$selectedDate = $selected[0];

		$this->initDatepickerAssets();

		$inputId = 'filter_' . $filter->filter_namekey . '_' . $divName;
		$hiddenName = 'filter_' . $filter->filter_namekey;
		$dateFormat = $this->getJQueryDateFormat($filter);
		$displayValue = $this->formatDateForDisplay(str_replace('_m', '', $selectedDate), $filter);
		$onSelect = $this->getOnSelectJs($filter, $divName, $parent, $inputId . '_value');

		$availableDays = array();
		$hasDynamic = false;
		if($filter->filter_dynamic && !empty($datas)) {
			$availableDays = $this->getAvailableDates($filter, $datas);
			$hasDynamic = true;
			if(empty($availableDays)) {
				$this->canBeUsed = false;
				return '';
			}
		}

		$isInline = !empty($filter->filter_options['inline']);

		$marginDays = (int)@$filter->filter_options['margin_days'];
		$hasMarginCheckbox = ($marginDays > 0 && !empty($filter->filter_options['show_margin_checkbox']));
		$marginActive = (strpos($selectedDate, '_m') !== false);
		$cleanDate = str_replace('_m', '', $selectedDate);

		$html = '';
		$html .= '<input type="hidden" name="' . $hiddenName . '[]" id="' . $inputId . '_value" value="' . htmlspecialchars($selectedDate) . '" data-container-div="hikashop_filter_form_' . $divName . '" />';

		if($isInline) {
			$html .= '<div id="' . $inputId . '" class="hikashop_filter_datepicker_inline"></div>';
		} else {
			$html .= '<input type="text" id="' . $inputId . '" class="hikashop_filter_datepicker form-control" value="' . htmlspecialchars($displayValue) . '" readonly="readonly" autocomplete="off" />';
		}

		if($hasMarginCheckbox) {
			$marginChecked = $marginActive ? 'checked="checked"' : '';
			$marginJs = 'var v=document.getElementById(\'' . $inputId . '_value\');'
				. 'var d=v.value.replace(\'_m\',\'\');'
				. 'if(d && d!==\'' . $filter->filter_namekey . '\'){v.value=this.checked?d+\'_m\':d;' . $onSelect . '}'
				;
			$html .= ' <label><input type="checkbox" id="' . $inputId . '_margin_cb" ' . $marginChecked
				. ' onchange="' . htmlspecialchars($marginJs) . '" '
				. ' /> +/- ' . $marginDays . ' ' . JText::_('DAYS') . '</label>';
		}

		$isInline = !empty($filter->filter_options['inline']);
		$showMonths = (int)@$filter->filter_options['show_months'];
		if($showMonths < 1) $showMonths = 1;

		$jsOptions = array();
		$jsOptions[] = 'dateFormat: "' . $dateFormat . '"';
		$jsOptions[] = 'showButtonPanel: true';
		$jsOptions[] = 'closeText: "' . addslashes(JText::_('RESET')) . '"';
		$jsOptions[] = 'currentText: ""';
		if($showMonths > 1)
			$jsOptions[] = 'numberOfMonths: ' . $showMonths;

		$fieldDef = $this->getFieldDefinition($filter);
		if(!empty($fieldDef)) {
			$dpOptions = @$fieldDef->field_options['datepicker_options'];
			if(!empty($dpOptions) && is_string($dpOptions))
				$dpOptions = hikashop_unserialize($dpOptions);

			$waiting = (int)@$dpOptions['waiting'];
			$daysFromNow = (int)@$dpOptions['days_from_now'];

			if(!empty($dpOptions['hour_extra_day'])) {
				$parts = explode(':', $dpOptions['hour_extra_day']);
				$hour = (int)array_shift($parts);
				$minute = count($parts) ? (int)array_shift($parts) : 0;
				$now = getdate();
				if((int)$now['hours'] > $hour || ((int)$now['hours'] == $hour && (int)$now['minutes'] >= $minute)) {
					$waiting++;
					$daysFromNow++;
				}
			}

			if(@$fieldDef->field_options['allow'] == 'future') {
				$jsOptions[] = 'minDate: ' . ($waiting > 0 ? $waiting : '0');
				if($daysFromNow > 0)
					$jsOptions[] = 'maxDate: ' . $daysFromNow;
			} elseif(@$fieldDef->field_options['allow'] == 'past') {
				$jsOptions[] = 'maxDate: ' . ($waiting > 0 ? (0 - $waiting) : '0');
				if($daysFromNow > 0)
					$jsOptions[] = 'minDate: ' . (0 - $daysFromNow);
			}
		}

		if($hasDynamic && !empty($availableDays)) {
			if($marginDays > 0 && empty($filter->filter_options['show_margin_checkbox'])) {
				$html .= '<script type="text/javascript">' . "\n"
					. 'var hikaDpAvail_' . $filter->filter_id . ' = ' . json_encode($availableDays) . ';' . "\n"
					. '</script>';
				$jsOptions[] = 'beforeShowDay: function(date) {
					var m = ' . $marginDays . ', avail = hikaDpAvail_' . $filter->filter_id . ';
					for(var off = -m; off <= m; off++) {
						var check = new Date(date); check.setDate(check.getDate() + off);
						var ymd = hkjQuery.datepicker.formatDate("yymmdd", check);
						if(avail.indexOf(ymd) !== -1) return [true, "hikashop_date_available", ""];
					}
					return [false, "hikashop_date_unavailable", ""];
				}';
			} else {
				$html .= '<script type="text/javascript">' . "\n"
					. 'var hikaDpAvail_' . $filter->filter_id . ' = ' . json_encode($availableDays) . ';' . "\n"
					. '</script>';
				$jsOptions[] = 'beforeShowDay: function(date) {
					var ymd = hkjQuery.datepicker.formatDate("yymmdd", date);
					if(hikaDpAvail_' . $filter->filter_id . '.indexOf(ymd) !== -1) return [true, "hikashop_date_available", ""];
					return [false, "hikashop_date_unavailable", ""];
				}';
			}
		}

		$marginSuffix = $hasMarginCheckbox
			? 'var cb=document.getElementById("' . $inputId . '_margin_cb"); if(cb && cb.checked) val+="_m";'
			: ($marginDays > 0 ? 'val+="_m";' : '');
		$jsOptions[] = 'onSelect: function(dateText, inst) {
			var d = hkjQuery(this).datepicker("getDate");
			var val = hkjQuery.datepicker.formatDate("yymmdd", d) + "000000";
			' . $marginSuffix . '
			hkjQuery("#' . $inputId . '_value").val(val);
			' . $onSelect . '
		}';

		$html .= '<script type="text/javascript">' . "\n"
			. 'hkjQuery(function(){' . "\n"
			. '  var dp = hkjQuery("#' . $inputId . '");' . "\n"
			. '  dp.datepicker({' . implode(', ', $jsOptions) . '});' . "\n";

		if($isInline) {
			if(!empty($cleanDate)) {
				$html .= '  dp.datepicker("setDate", new Date(' . (int)substr($cleanDate, 0, 4) . ', ' . ((int)substr($cleanDate, 4, 2) - 1) . ', ' . (int)substr($cleanDate, 6, 2) . '));' . "\n";
			}
			$html .= '  function hikaFilterInjectClear_' . $filter->filter_id . '() {' . "\n"
				. '    var pane = dp.find(".ui-datepicker-buttonpane");' . "\n"
				. '    if(!pane.length) return;' . "\n"
				. '    pane.find(".ui-datepicker-current").hide();' . "\n"
				. '    if(pane.find(".hikashop-filter-clear").length) return;' . "\n"
				. '    pane.find(".ui-datepicker-close").hide();' . "\n"
				. '    var clearBtn = hkjQuery(\'<button type="button" class="hikashop-filter-clear ui-state-default ui-priority-primary ui-corner-all">' . addslashes(JText::_('RESET')) . '</button>\');' . "\n"
				. '    clearBtn.on("click", function(e) {' . "\n"
				. '      e.preventDefault();' . "\n"
				. '      dp.datepicker("setDate", null);' . "\n"
				. '      hkjQuery("#' . $inputId . '_value").val("");' . "\n"
				. '      ' . $onSelect . "\n"
				. '    });' . "\n"
				. '    pane.append(clearBtn);' . "\n"
				. '  }' . "\n"
				. '  hikaFilterInjectClear_' . $filter->filter_id . '();' . "\n"
				. '  setTimeout(hikaFilterInjectClear_' . $filter->filter_id . ', 50);' . "\n"
				. '  dp.datepicker("option", "onChangeMonthYear", function() {' . "\n"
				. '    setTimeout(hikaFilterInjectClear_' . $filter->filter_id . ', 1);' . "\n"
				. '  });' . "\n"
				. '  new MutationObserver(function() { hikaFilterInjectClear_' . $filter->filter_id . '(); }).observe(dp[0], {childList: true, subtree: true});' . "\n";
		} else {
			$html .= '  dp.datepicker("option", "beforeShow", function(input, inst) {' . "\n"
				. '    setTimeout(function(){' . "\n"
				. '      var panel = hkjQuery(inst.dpDiv);' . "\n"
				. '      panel.find(".ui-datepicker-current").hide();' . "\n"
				. '      var btn = panel.find(".ui-datepicker-close");' . "\n"
				. '      btn.off("click").on("click", function(e) {' . "\n"
				. '        e.preventDefault(); e.stopImmediatePropagation();' . "\n"
				. '        dp.datepicker("setDate", null).val("");' . "\n"
				. '        hkjQuery("#' . $inputId . '_value").val("");' . "\n"
				. '        dp.datepicker("hide");' . "\n"
				. '        ' . $onSelect . "\n"
				. '      });' . "\n"
				. '    }, 1);' . "\n"
				. '  });' . "\n";
		}

		$html .= '});' . "\n"
			. '</script>';

		$html = '<span id="filter_values_container_' . $filter->filter_namekey . '_' . $divName . '" class="filter_values_container">' . $html . '</span>';
		if(@$filter->filter_options['title_position'] != 'inside')
			$html = parent::titlePosition($filter, $html);

		return $html;
	}

	function addFilter(&$filter, &$filters, &$select, &$select2, &$a, &$b, &$on, &$order, &$parent, $divName) {
		$originalData = $filter->filter_data;
		if($filter->filter_data == 'custom_field')
			$filter->filter_data = '_plg_datepicker_skip';

		parent::addFilter($filter, $filters, $select, $select2, $a, $b, $on, $order, $parent, $divName);

		$filter->filter_data = $originalData;

		$app = JFactory::getApplication();
		$cid = hikaInput::get()->getInt('cid', 'itemid_' . hikaInput::get()->getInt('Itemid', 0));
		$config = hikashop_config();
		if(hikaInput::get()->getVar('filtered') == 1 || $config->get('redirect_post', 0)) {
			$infoGet = hikaInput::get()->getVar('filter_' . $filter->filter_namekey);
		} else {
			$infoGet = $app->getUserStateFromRequest('com_hikashop.' . $cid . '_filter_' . $filter->filter_namekey, 'filter_' . $filter->filter_namekey, array(), 'array');
		}
		if(!is_array($infoGet))
			$infoGet = array($infoGet);

		$rawValue = isset($infoGet[0]) ? trim($infoGet[0]) : '';
		if(empty($rawValue) || $rawValue == $filter->filter_namekey || $rawValue == 'none')
			return;

		$marginActive = (strpos($rawValue, '_m') !== false);
		$selectedDate = str_replace('_m', '', $rawValue);
		$marginDays = (int)@$filter->filter_options['margin_days'];
		if($marginDays > 0 && empty($filter->filter_options['show_margin_checkbox']))
			$marginActive = true;

		if(empty($filter->filter_options['custom_field']))
			return;

		if($this->isItemField($filter)) {
			JPluginHelper::importPlugin('hikashop');
			$app = JFactory::getApplication();
			$app->triggerEvent('onFilterItemFieldAddFilter', array(&$filter, $selectedDate, $marginActive, $marginDays, &$filters, &$select, &$select2, &$a, &$b, &$on, &$order));
			return;
		}

		$database = JFactory::getDBO();
		$fieldName = 'b.' . hikashop_secureField($filter->filter_options['custom_field']);

		if($marginActive && $marginDays > 0) {
			$ts = mktime(0, 0, 0, (int)substr($selectedDate, 4, 2), (int)substr($selectedDate, 6, 2), (int)substr($selectedDate, 0, 4));
			$startDate = date('Ymd', $ts - $marginDays * 86400) . '000000';
			$endDate = date('Ymd', $ts + $marginDays * 86400) . '235959';
			$filters[] = $fieldName . ' >= ' . $database->Quote($startDate) . ' AND ' . $fieldName . ' <= ' . $database->Quote($endDate);
		} else {
			$dayStart = substr($selectedDate, 0, 8) . '000000';
			$dayEnd = substr($selectedDate, 0, 8) . '235959';
			$filters[] = $fieldName . ' >= ' . $database->Quote($dayStart) . ' AND ' . $fieldName . ' <= ' . $database->Quote($dayEnd);
		}
	}

	function getFieldToLoad($filter, $divName, &$parent) {
		if($filter->filter_data == 'custom_field' && !empty($filter->filter_options['custom_field']) && !$this->isItemField($filter))
			return 'b.' . $filter->filter_options['custom_field'];
		return '';
	}
}


class hikashopFilterRangedatepickerfieldClass extends hikashopFilterDatepickerfieldBaseClass {

	function display(&$filter, $divName, &$parent, $datas = '') {
		$selected = parent::display($filter, $divName, $parent);
		if(!is_array($selected))
			$selected = array($selected);

		if(!$filter->filter_dynamic)
			$datas = '';

		if(!empty($selected)) {
			if($filter->filter_deletable && isset($selected[0]) && $selected[0] != $filter->filter_namekey && $selected[0] != 'none' && trim(str_replace('::', '', $selected[0])) != '') {
				return parent::displayInList($filter, $divName, $selected);
			}
		}

		$startDate = '';
		$endDate = '';
		if(!empty($selected[0]) && preg_match('/^\d{14}-\d{14}$/', $selected[0])) {
			$startDate = substr($selected[0], 0, 14);
			$endDate = substr($selected[0], 15, 14);
		}

		$this->initDatepickerAssets();

		$inputId = 'filter_' . $filter->filter_namekey . '_' . $divName;
		$hiddenName = 'filter_' . $filter->filter_namekey;
		$dateFormat = $this->getJQueryDateFormat($filter);
		$onComplete = $this->getOnSelectJs($filter, $divName, $parent, $inputId . '_value');
		$showMonths = (int)@$filter->filter_options['show_months'];
		if($showMonths < 1) $showMonths = 1;

		$this->initRangeJs();

		$dpOptions = array();
		$dpOptions['dateFormat'] = $dateFormat;
		$dpOptions['showButtonPanel'] = true;
		if($showMonths > 1)
			$dpOptions['numberOfMonths'] = $showMonths;

		$fieldDef = $this->getFieldDefinition($filter);
		if(!empty($fieldDef)) {
			$fieldDpOpts = @$fieldDef->field_options['datepicker_options'];
			if(!empty($fieldDpOpts) && is_string($fieldDpOpts))
				$fieldDpOpts = hikashop_unserialize($fieldDpOpts);

			$waiting = (int)@$fieldDpOpts['waiting'];
			$daysFromNow = (int)@$fieldDpOpts['days_from_now'];

			if(!empty($fieldDpOpts['hour_extra_day'])) {
				$parts = explode(':', $fieldDpOpts['hour_extra_day']);
				$hour = (int)array_shift($parts);
				$minute = count($parts) ? (int)array_shift($parts) : 0;
				$now = getdate();
				if((int)$now['hours'] > $hour || ((int)$now['hours'] == $hour && (int)$now['minutes'] >= $minute)) {
					$waiting++;
					$daysFromNow++;
				}
			}

			if(@$fieldDef->field_options['allow'] == 'future') {
				$dpOptions['minDate'] = ($waiting > 0 ? $waiting : 0);
				if($daysFromNow > 0)
					$dpOptions['maxDate'] = $daysFromNow;
			} elseif(@$fieldDef->field_options['allow'] == 'past') {
				$dpOptions['maxDate'] = ($waiting > 0 ? (0 - $waiting) : 0);
				if($daysFromNow > 0)
					$dpOptions['minDate'] = (0 - $daysFromNow);
			}
		}

		if($filter->filter_dynamic && !empty($datas)) {
			$bounds = $this->getDateBounds($filter, $datas);
			if(empty($bounds) || empty($bounds->min_date)) {
				$this->canBeUsed = false;
				return '';
			}
		}

		$html = '';
		$rangeVal = '';
		if(!empty($startDate) && !empty($endDate))
			$rangeVal = $startDate . '-' . $endDate;
		$html .= '<input type="hidden" name="' . $hiddenName . '[]" id="' . $inputId . '_value" value="' . htmlspecialchars($rangeVal) . '" data-container-div="hikashop_filter_form_' . $divName . '" />';

		$html .= '<div class="hikashop_datepicker_range">';
		$html .= '<div class="hikashop_datepicker_range_display">';
		$html .= '<div class="hikashop_datepicker_range_cell"><span class="hikashop_datepicker_range_label">' . JText::_('FROM') . '</span><strong id="' . $inputId . '_value_start_display">' . ($startDate ? $this->formatDateForDisplay($startDate, $filter) : '-') . '</strong></div>';
		$html .= '<div class="hikashop_datepicker_range_cell"><span class="hikashop_datepicker_range_label">' . JText::_('TO') . '</span><strong id="' . $inputId . '_value_end_display">' . ($endDate ? $this->formatDateForDisplay($endDate, $filter) : '-') . '</strong></div>';
		$html .= '</div>';
		$html .= '<div id="' . $inputId . '" class="hikashop_datepicker" data-picker="' . $inputId . '_value" data-options="' . htmlspecialchars(json_encode($dpOptions)) . '" data-range="1"></div>';
		$html .= '</div>';

		$resetLabel = addslashes(JText::_('RESET'));
		$js = 'hkjQuery(function(){' . "\n"
			. '  var dp = hkjQuery("#' . $inputId . '");' . "\n"
			. '  window.hikashopDatepickerRange(dp);' . "\n"
			. '  function hikaRangeInjectClear_' . $filter->filter_id . '() {' . "\n"
			. '    var pane = dp.find(".ui-datepicker-buttonpane");' . "\n"
			. '    if(!pane.length) return;' . "\n"
			. '    pane.find(".ui-datepicker-current").hide();' . "\n"
			. '    pane.find(".ui-datepicker-close").hide();' . "\n"
			. '    if(pane.find(".hikashop-filter-clear").length) return;' . "\n"
			. '    var clearBtn = hkjQuery(\'<button type="button" class="hikashop-filter-clear ui-state-default ui-priority-primary ui-corner-all">' . $resetLabel . '</button>\');' . "\n"
			. '    clearBtn.on("click", function(e) {' . "\n"
			. '      e.preventDefault();' . "\n"
			. '      hkjQuery("#' . $inputId . '_value").val("");' . "\n"
			. '      dp.datepicker("setDate", null).datepicker("refresh");' . "\n"
			. '      hkjQuery("#' . $inputId . '_value_start_display").text("-");' . "\n"
			. '      hkjQuery("#' . $inputId . '_value_end_display").text("-");' . "\n"
			. '      ' . $onComplete . "\n"
			. '    });' . "\n"
			. '    pane.append(clearBtn);' . "\n"
			. '  }' . "\n"
			. '  hikaRangeInjectClear_' . $filter->filter_id . '();' . "\n"
			. '  setTimeout(hikaRangeInjectClear_' . $filter->filter_id . ', 50);' . "\n"
			. '  new MutationObserver(function() { hikaRangeInjectClear_' . $filter->filter_id . '(); }).observe(dp[0], {childList: true, subtree: true});' . "\n"
			. '  hkjQuery("#' . $inputId . '_value").on("change", function() {' . "\n"
			. '    var v = this.value;' . "\n"
			. '    if(v && v.match(/^\\d{14}-\\d{14}$/)) { ' . $onComplete . ' }' . "\n"
			. '  });' . "\n"
			. '});';

		$html .= '<script type="text/javascript">' . "\n" . $js . "\n" . '</script>';

		$html = '<span id="filter_values_container_' . $filter->filter_namekey . '_' . $divName . '" class="filter_values_container">' . $html . '</span>';
		if(@$filter->filter_options['title_position'] != 'inside')
			$html = parent::titlePosition($filter, $html);

		return $html;
	}

	function addFilter(&$filter, &$filters, &$select, &$select2, &$a, &$b, &$on, &$order, &$parent, $divName) {
		$originalData = $filter->filter_data;
		if($filter->filter_data == 'custom_field')
			$filter->filter_data = '_plg_rangedatepicker_skip';

		parent::addFilter($filter, $filters, $select, $select2, $a, $b, $on, $order, $parent, $divName);

		$filter->filter_data = $originalData;

		$app = JFactory::getApplication();
		$cid = hikaInput::get()->getInt('cid', 'itemid_' . hikaInput::get()->getInt('Itemid', 0));
		$config = hikashop_config();
		if(hikaInput::get()->getVar('filtered') == 1 || $config->get('redirect_post', 0)) {
			$infoGet = hikaInput::get()->getVar('filter_' . $filter->filter_namekey);
		} else {
			$infoGet = $app->getUserStateFromRequest('com_hikashop.' . $cid . '_filter_' . $filter->filter_namekey, 'filter_' . $filter->filter_namekey, array(), 'array');
		}
		if(!is_array($infoGet))
			$infoGet = array($infoGet);

		$rangeValue = isset($infoGet[0]) ? trim($infoGet[0]) : '';
		if(empty($rangeValue) || $rangeValue == $filter->filter_namekey || $rangeValue == 'none')
			return;

		if(!preg_match('/^(\d{14})-(\d{14})$/', $rangeValue, $m))
			return;
		$startDate = $m[1];
		$endDate = $m[2];

		if(empty($filter->filter_options['custom_field']))
			return;

		if($this->isItemField($filter)) {
			JPluginHelper::importPlugin('hikashop');
			$app = JFactory::getApplication();
			$app->triggerEvent('onFilterItemFieldAddFilter', array(&$filter, $startDate . '-' . $endDate, false, 0, &$filters, &$select, &$select2, &$a, &$b, &$on, &$order));
			return;
		}

		$database = JFactory::getDBO();
		$fieldName = 'b.' . hikashop_secureField($filter->filter_options['custom_field']);

		$fieldDef = $this->getFieldDefinition($filter);
		$dpOptions = array();
		if(!empty($fieldDef)) {
			$dpOptions = @$fieldDef->field_options['datepicker_options'];
			if(!empty($dpOptions) && is_string($dpOptions))
				$dpOptions = hikashop_unserialize($dpOptions);
		}
		$isRangeField = !empty($dpOptions['range']);

		if($isRangeField) {
			$filters[] = 'LEFT(' . $fieldName . ', 14) <= ' . $database->Quote($endDate)
				. ' AND SUBSTRING(' . $fieldName . ', 16, 14) >= ' . $database->Quote($startDate);
		} else {
			$filters[] = $fieldName . ' >= ' . $database->Quote($startDate)
				. ' AND ' . $fieldName . ' <= ' . $database->Quote($endDate);
		}
	}

	function getFieldToLoad($filter, $divName, &$parent) {
		if($filter->filter_data == 'custom_field' && !empty($filter->filter_options['custom_field']) && !$this->isItemField($filter))
			return 'b.' . $filter->filter_options['custom_field'];
		return '';
	}
}
