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
class plgHikashopDatepickerfield extends JPlugin
{
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);

		$this->loadLanguage('plg_hikashop_datepickerfield', JPATH_ADMINISTRATOR );
	}

	public function onFieldsLoad(&$fields, &$options) {
		$me = new stdClass();
		$me->name = 'datepickerfield';
		$me->text = JText::_('DATE_PICKER');
		$me->options = array('required', 'default', 'columnname', 'format', 'allow', 'datepicker_options');

		$fields[] = $me;

		$opt = new stdClass();
		$opt->name = 'datepicker_options';
		$opt->text = JText::_('DATE_PICKER_OPTIONS');
		$opt->own_block = true;
		$opt->obj = 'fieldOpt_datepicker_options';

		$options[$opt->name] = $opt;
	}

	public function onFilterTypeDisplay(&$types) {
		$types['plg.datepickerfield'] = JText::_('DATE_PICKER');
		$types['plg.rangedatepickerfield'] = JText::_('DATE_RANGE_PICKER');
	}

	public function onFilterTypeConfig(&$filter, &$html) {
		$currentType = (string)@$filter->filter_type;
		$selectedField = @$filter->filter_options['custom_field'];

		$database = JFactory::getDBO();
		$database->setQuery("SELECT * FROM " . hikashop_table('field') . " WHERE field_type = 'plg.datepickerfield' AND field_table IN ('product', 'item') AND field_published = 1 ORDER BY field_table, field_realname");
		$allFields = $database->loadObjectList();

		$productFields = array();
		$itemFields = array();
		foreach($allFields as $f) {
			if($f->field_table == 'product')
				$productFields[] = $f;
			else
				$itemFields[] = $f;
		}

		$fieldSelect = '<select name="data[filter][custom_field]" class="custom-select">';
		if(empty($allFields)) {
			$fieldSelect .= '<option value="">(' . JText::_('HIKA_NONE') . ')</option>';
		}
		if(!empty($productFields)) {
			$fieldSelect .= '<optgroup label="' . JText::_('PRODUCT') . '">';
			foreach($productFields as $f) {
				$sel = ($selectedField == $f->field_namekey) ? ' selected="selected"' : '';
				$fieldSelect .= '<option value="' . htmlspecialchars($f->field_namekey) . '"' . $sel . '>' . htmlspecialchars($f->field_realname) . '</option>';
			}
			$fieldSelect .= '</optgroup>';
		}
		if(!empty($itemFields)) {
			$fieldSelect .= '<optgroup label="' . JText::_('HIKASHOP_ITEM') . '">';
			foreach($itemFields as $f) {
				$sel = ($selectedField == $f->field_namekey) ? ' selected="selected"' : '';
				$fieldSelect .= '<option value="' . htmlspecialchars($f->field_namekey) . '"' . $sel . '>' . htmlspecialchars($f->field_realname) . '</option>';
			}
			$fieldSelect .= '</optgroup>';
		}
		$fieldSelect .= '</select>';

		$inline = !empty($filter->filter_options['inline']);
		$showMonths = (int)@$filter->filter_options['show_months'];
		if($showMonths < 1) $showMonths = 1;

		$show = ($currentType == 'plg.datepickerfield') ? '' : ' style="display:none"';
		$marginDays = (int)@$filter->filter_options['margin_days'];
		$showMarginCheckbox = !empty($filter->filter_options['show_margin_checkbox']);

		$html .= '<div data-plg-filter-type="plg.datepickerfield"' . $show . '>';
		$html .= '<table class="admintable table">';
		$html .= '<tr><td class="key">' . JText::_('FIELD') . '</td>';
		$html .= '<td>' . $fieldSelect . '</td></tr>';
		$html .= '<tr><td class="key">' . JText::_('INLINE_DISPLAY') . '</td>';
		$html .= '<td><input type="checkbox" name="data[filter][filter_options][inline]" value="1" ' . ($inline ? 'checked="checked"' : '') . ' /></td></tr>';
		$html .= '<tr><td class="key">' . JText::_('NUMBER_OF_MONTHS') . '</td>';
		$html .= '<td><input type="text" size="3" name="data[filter][filter_options][show_months]" value="' . $showMonths . '" /></td></tr>';
		$html .= '<tr><td class="key">' . JText::_('MARGIN_DAYS') . '</td>';
		$html .= '<td><input type="text" size="3" name="data[filter][filter_options][margin_days]" value="' . $marginDays . '" /> ' . JText::_('DAYS') . '</td></tr>';
		$html .= '<tr><td class="key">' . JText::_('SHOW_MARGIN_CHECKBOX') . '</td>';
		$html .= '<td><input type="checkbox" name="data[filter][filter_options][show_margin_checkbox]" value="1" ' . ($showMarginCheckbox ? 'checked="checked"' : '') . ' /></td></tr>';
		$html .= '</table>';
		$html .= '</div>';

		$show = ($currentType == 'plg.rangedatepickerfield') ? '' : ' style="display:none"';

		$html .= '<div data-plg-filter-type="plg.rangedatepickerfield"' . $show . '>';
		$html .= '<table class="admintable table">';
		$html .= '<tr><td class="key">' . JText::_('FIELD') . '</td>';
		$html .= '<td>' . $fieldSelect . '</td></tr>';
		$html .= '<tr><td class="key">' . JText::_('INLINE_DISPLAY') . '</td>';
		$html .= '<td><input type="checkbox" name="data[filter][filter_options][inline]" value="1" ' . ($inline ? 'checked="checked"' : '') . ' /></td></tr>';
		$html .= '<tr><td class="key">' . JText::_('NUMBER_OF_MONTHS') . '</td>';
		$html .= '<td><input type="text" size="3" name="data[filter][filter_options][show_months]" value="' . $showMonths . '" /></td></tr>';
		$html .= '</table>';
		$html .= '</div>';
	}

	public function onFilterTypeSaveForm(&$filter, &$formData) {
		$type = @$formData['filter']['filter_type'];
		if($type != 'plg.datepickerfield' && $type != 'plg.rangedatepickerfield')
			return;

		$filter->filter_data = serialize('custom_field');
		$formData['filter']['filter_data'] = 'custom_field';

		if(!is_array($filter->filter_options))
			$filter->filter_options = array();

		$filter->filter_options['custom_field'] = @$formData['filter']['custom_field'];
		$formData['filter']['custom_field'] = @$formData['filter']['custom_field'];

		$plgOptions = @$formData['filter']['filter_options'];
		if(is_array($plgOptions)) {
			foreach($plgOptions as $k => $v) {
				$filter->filter_options[$k] = $v;
			}
		}

	}

	public function initFilterTypeClass() {
		if(!class_exists('hikashopFilterDatepickerfieldClass'))
			require_once(dirname(__FILE__) . DS . 'datepickerfield_filter.php');
	}

	public function onInitFilterTypeClass($type) {
		if($type == 'datepickerfield' || $type == 'rangedatepickerfield')
			$this->initFilterTypeClass();
	}
}

if(defined('HIKASHOP_COMPONENT')) {
	require_once( dirname(__FILE__).DS.'datepickerfield_class.php' );
}

function hikashopDatepickerfield_loadRangeJs() {
	static $done = false;
	if($done)
		return;
	$done = true;

	$lang = JFactory::getLanguage();
	$tag = $lang->getTag();
	$conversionTable = array(
		'fr-FR' => 'fr', 'de-DE' => 'de', 'es-ES' => 'es', 'it-IT' => 'it',
		'nl-NL' => 'nl', 'pt-PT' => 'pt', 'pt-BR' => 'pt-BR', 'ja-JP' => 'ja',
		'zh-CN' => 'zh-CN', 'zh-TW' => 'zh-TW', 'ko-KR' => 'ko', 'ru-RU' => 'ru',
		'pl-PL' => 'pl', 'cs-CZ' => 'cs', 'da-DK' => 'da', 'sv-SE' => 'sv',
		'nb-NO' => 'no', 'tr-TR' => 'tr', 'ro-RO' => 'ro', 'hu-HU' => 'hu',
		'bg-BG' => 'bg', 'el-GR' => 'el', 'he-IL' => 'he', 'ar-AR' => 'ar',
		'ca-ES' => 'ca', 'sk-SK' => 'sk', 'sl-SL' => 'sl', 'uk-UA' => 'uk',
		'vi-VN' => 'vi', 'th-TH' => 'th', 'fa-IR' => 'fa', 'af-ZA' => 'af',
		'bs-BA' => 'bs', 'lv-LV' => 'lv', 'lt-LT' => 'lt', 'mk-MK' => 'mk',
		'en-AU' => 'en-AU', 'en-NZ' => 'en-NZ', 'fr-CH' => 'fr-CH',
	);
	$dpTag = isset($conversionTable[$tag]) ? $conversionTable[$tag] : 'en-GB';
	$nightsLabel = addslashes(JText::_('DATE_PICKER_RANGE_NIGHTS'));

	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration('
if(typeof window.hikashopDatepickerRange === "undefined") {
window.hikashopDatepickerRange = function(el) {
	if(typeof(el) == "string") el = hkjQuery("#" + el);
	var options = {};
	if(el.attr("data-options")) options = Oby.evalJSON(el.attr("data-options"));

	var baseId = el.attr("data-picker");
	var minNights = parseInt(el.attr("data-range-min-nights")) || 1;
	var maxNights = parseInt(el.attr("data-range-max-nights")) || 0;
	var startDate = null, endDate = null;
	var state = "selectStart";

	var hidden = document.getElementById(baseId);
	if(hidden && hidden.value) {
		var rm = hidden.value.match(/^(\d{4})(\d{2})(\d{2})\d{6}-(\d{4})(\d{2})(\d{2})\d{6}$/);
		if(rm) {
			startDate = new Date(parseInt(rm[1]), parseInt(rm[2])-1, parseInt(rm[3]));
			endDate = new Date(parseInt(rm[4]), parseInt(rm[5])-1, parseInt(rm[6]));
			state = "selectStart";
		}
	}

	var origBeforeShowDay = null;
	if(options["exclude"] || options["excludeDays"] || options["excludeDates"] || options["excludeRanges"] || options["excludePatterns"]) {
		origBeforeShowDay = function(date) {
			return window.hikashopDatepicker_excludeWDays(date, options["exclude"], options["excludeDays"], options["excludeDates"], options["excludeRanges"], options["excludePatterns"]);
		};
	}

	options["beforeShowDay"] = function(date) {
		var orig = origBeforeShowDay ? origBeforeShowDay(date) : [true, ""];
		if(!orig[0]) return orig;
		var css = orig[1] || "";
		if(startDate && !endDate && date.getTime() === startDate.getTime()) {
			css += " hikashop_range_start";
		} else if(startDate && endDate) {
			var t = date.getTime();
			if(t === startDate.getTime()) css += " hikashop_range_start";
			else if(t === endDate.getTime()) css += " hikashop_range_end";
			else if(t > startDate.getTime() && t < endDate.getTime()) css += " hikashop_range_middle";
		}
		return [orig[0], css.replace(/^\s+/, ""), ""];
	};

	function hasExcludedDayInRange(from, to) {
		var currentBSD = el.datepicker("option", "beforeShowDay");
		if(!currentBSD) return false;
		var d = new Date(from.getTime());
		d.setDate(d.getDate() + 1);
		while(d.getTime() < to.getTime()) {
			var result = currentBSD(d);
			if(!result[0]) return true;
			d.setDate(d.getDate() + 1);
		}
		return false;
	}

	options["onSelect"] = function(dateText, inst) {
		var sel = hkjQuery(this).datepicker("getDate");
		if(!sel) return;
		if(state === "selectStart" || (startDate && endDate)) {
			startDate = sel; endDate = null; state = "selectEnd";
		} else {
			if(sel.getTime() <= startDate.getTime()) {
				startDate = sel; endDate = null; state = "selectEnd";
			} else {
				var candidateEnd = sel;
				var nights = Math.round((sel.getTime() - startDate.getTime()) / 86400000);
				if(nights < minNights) { candidateEnd = new Date(startDate.getTime()); candidateEnd.setDate(candidateEnd.getDate() + minNights); }
				else if(maxNights > 0 && nights > maxNights) { candidateEnd = new Date(startDate.getTime()); candidateEnd.setDate(candidateEnd.getDate() + maxNights); }
				if(hasExcludedDayInRange(startDate, candidateEnd)) {
					startDate = sel; endDate = null; state = "selectEnd";
				} else {
					endDate = candidateEnd; state = "selectStart";
				}
			}
		}
		updateRangeDisplay();
		updateRangeHidden();
		hkjQuery(this).datepicker("refresh");
	};

	delete options["altField"];
	delete options["altFormat"];

	hkjQuery.datepicker.setDefaults(hkjQuery.datepicker.regional["' . $dpTag . '"]);
	el.datepicker(options);

	function updateRangeDisplay() {
		var fmt = options["dateFormat"] || "mm/dd/yy";
		var sEl = document.getElementById(baseId + "_start_display");
		var eEl = document.getElementById(baseId + "_end_display");
		var nEl = document.getElementById(baseId + "_nights_display");
		if(sEl) sEl.textContent = startDate ? hkjQuery.datepicker.formatDate(fmt, startDate) : "-";
		if(eEl) eEl.textContent = endDate ? hkjQuery.datepicker.formatDate(fmt, endDate) : "-";
		if(nEl) {
			if(startDate && endDate) {
				var nights = Math.round((endDate.getTime() - startDate.getTime()) / 86400000);
				nEl.textContent = nights + " ' . $nightsLabel . '";
			} else { nEl.textContent = ""; }
		}
	}

	function updateRangeHidden() {
		if(!hidden) return;
		if(startDate && endDate) {
			hidden.value = formatRangeYmd(startDate) + "000000-" + formatRangeYmd(endDate) + "000000";
		} else { hidden.value = ""; }
		hidden.dispatchEvent(new Event("change"));
	}

	function formatRangeYmd(d) {
		return d.getFullYear() + ("0"+(d.getMonth()+1)).slice(-2) + ("0"+d.getDate()).slice(-2);
	}

	updateRangeDisplay();
};
}');
}
