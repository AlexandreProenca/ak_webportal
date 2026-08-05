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
class fieldOpt_datepicker_options {
	public function show($value) {
		if(!empty($value)) {
			if(is_string($value))
				$value = hikashop_unserialize($value);
		} else {
			$value = array();
		}

		$excludeFormats = array(
			JHTML::_('select.option', 'mdY', 'm/d/Y'),
			JHTML::_('select.option', 'dmY', 'd/m/Y')
		);

		$months = array();
		for($i = 1; $i <= 12; $i++) {
			$months[] = JHTML::_('select.option', $i, $i);
		}

		$checkDates = array(
			JHTML::_('select.option', 'all', JText::_('HIKA_EVERYWHERE')),
			JHTML::_('select.option', 'front', JText::_('HIKA_FRONTEND_ONLY'))
		);
		$ret = '
<table class="table admintable table-stripped">
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_DEFAULT_TODAY').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][today]" , '', @$value['today']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_INLINE_DISPLAY').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][inline]" , '', @$value['inline']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_MONDAY_FIRST').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][monday_first]" , '', @$value['monday_first']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_CHANGE_MONTH').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][change_month]" , '', @$value['change_month']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_CHANGE_YEAR').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][change_year]" , '', @$value['change_year']).'<br/>'.
		JText::_('HIKA_START').'<input type="text" name="field_options[datepicker_options][year_range_start]" value="'.@$value['year_range_start'].'" /><br/>'.JText::_('HIKASHOP_CHECKOUT_END').'<input type="text" name="field_options[datepicker_options][year_range_end]" value="'.@$value['year_range_end'].'" /></td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_SHOW_BTN_PANEL').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][show_btn_panel]" , '', @$value['show_btn_panel']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_SHOW_MONTHS').'</td>
		<td>'.
			JHTML::_('select.genericlist', $months, "field_options[datepicker_options][show_months]", 'class="custom-select"', 'value', 'text', @$value['show_months']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_OTHER_MONTH').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][other_month]" , '', @$value['other_month']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_FORBIDDEN_DAYS').'</td>
		<td>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_1]" value="1"'.(empty($value['forbidden_1'])?'':' checked="checked"').'/> '.JText::_('MONDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_2]" value="1"'.(empty($value['forbidden_2'])?'':' checked="checked"').'/> '.JText::_('TUESDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_3]" value="1"'.(empty($value['forbidden_3'])?'':' checked="checked"').'/> '.JText::_('WEDNESDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_4]" value="1"'.(empty($value['forbidden_4'])?'':' checked="checked"').'/> '.JText::_('THURSDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_5]" value="1"'.(empty($value['forbidden_5'])?'':' checked="checked"').'/> '.JText::_('FRIDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_6]" value="1"'.(empty($value['forbidden_6'])?'':' checked="checked"').'/> '.JText::_('SATURDAY').'</label><br/>
			<label><input type="checkbox" name="field_options[datepicker_options][forbidden_0]" value="1"'.(empty($value['forbidden_0'])?'':' checked="checked"').'/> '.JText::_('SUNDAY').'</label>
		</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_EXCLUDES').'</td>
		<td>
			'.JHTML::_('select.genericlist', $excludeFormats, "field_options[datepicker_options][exclude_days_format]", 'class="custom-select"', 'value', 'text', @$value['exclude_days_format']).'<br/>
			<textarea name="field_options__datepicker_options__excludes">'.@$value['excludes'].'</textarea>
		</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_WAITING_DAYS').'</td>
		<td>
			<input type="text" name="field_options[datepicker_options][waiting]" value="'.@$value['waiting'].'" />
		</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_HOUR_EXTRA_DAY').'</td>
		<td>
			<input type="text" name="field_options[datepicker_options][hour_extra_day]" value="'.@$value['hour_extra_day'].'" />
		</td>
	</tr>

	<tr>
		<td class="key">'.JText::_('DATE_PICKER_DAYS_FROM_NOW').'</td>
		<td>
			<input type="text" name="field_options[datepicker_options][days_from_now]" value="'.@$value['days_from_now'].'" />
		</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('HIKA_CHECK_DATES').'</td>
		<td>'.
			JHTML::_('select.genericlist', $checkDates, "field_options[datepicker_options][check_dates]", 'class="custom-select"', 'value', 'text', @$value['check_dates']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_RANGE_MODE').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][range]" , '', @$value['range']).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_RANGE_EXCLUDE_END').'</td>
		<td>'.
			JHTML::_('hikaselect.booleanlist', "field_options[datepicker_options][range_exclude_end]" , '', isset($value['range_exclude_end']) ? $value['range_exclude_end'] : 1).
		'</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_RANGE_MIN_NIGHTS').'</td>
		<td>
			<input type="text" name="field_options[datepicker_options][range_min_nights]" value="'.(isset($value['range_min_nights']) ? (int)$value['range_min_nights'] : 1).'" size="5" />
		</td>
	</tr>
	<tr>
		<td class="key">'.JText::_('DATE_PICKER_OPT_RANGE_MAX_NIGHTS').'</td>
		<td>
			<input type="text" name="field_options[datepicker_options][range_max_nights]" value="'.@$value['range_max_nights'].'" size="5" />
		</td>
	</tr>
</table>';
		return $ret;
	}

	public function save(&$options) {
		if(!empty($options['datepicker_options']))
			$options['datepicker_options']['excludes'] = hikaInput::get()->getRaw('field_options__datepicker_options__excludes', '');
	}
}

class hikashopDatepickerfield extends stdClass{

	public $prefix = null;
	public $suffix = null;
	public $excludeValue = null;
	public $report = null;
	public $parent = null;
	public $displayFor = false;
	protected $params = null;

	public function __construct(&$obj) {
		$this->prefix = $obj->prefix;
		$this->suffix = $obj->suffix;
		$this->excludeValue =& $obj->excludeValue;
		$this->report = @$obj->report;
		$this->parent =& $obj;

		$timeoffset = 0;
		$jconfig = JFactory::getConfig();
		if(!HIKASHOP_J30){
			$timeoffset = $jconfig->getValue('config.offset');
		} else {
			$timeoffset = $jconfig->get('offset');
		}
		$dateC = JFactory::getDate(time(),$timeoffset);
		$timeoffset = $dateC->getOffsetFromGMT(true);
		$this->timeoffset = $timeoffset *60*60 + date('Z');
	}

	private function init() {
		static $init = null;
		if($init !== null)
			return $init;

		hikashop_loadJsLib('jquery');
		$doc = JFactory::getDocument();
		$lang = JFactory::getLanguage();
		$app = JFactory::getApplication();
		$tag = $lang->getTag();
		$conversionTable = array(
			'af-ZA' => 'af',
			'ar-AR' => 'ar',
			'eu-ES' => 'eu',
			'bg-BG' => 'bg',
			'ca-ES' => 'ca',
			'zh-CN' => 'zh-CN',
			'zh-TW' => 'zh-TW',
			'bs-BA' => 'bs',
			'cs-CZ' => 'cs',
			'da-DK' => 'da',
			'nl-NL' => 'nl',
			'en-AU' => 'en-AU',
			'en-NZ' => 'en-NZ',
			'fi-FI' => 'fi',
			'fr-FR' => 'fr',
			'fr-CA' => 'fr',
			'fr-CH' => 'fr-CH',
			'gl-ES' => 'gl',
			'de-DE' => 'de',
			'el-GR' => 'el',
			'he-IL' => 'he',
			'hu-HU' => 'hu',
			'it-IT' => 'it',
			'ja-JP' => 'ja',
			'ko-KR' => 'ko',
			'lv-LV' => 'lv',
			'lt-LT' => 'lt',
			'mk-MK' => 'mk',
			'nb-NO' => 'no',
			'fa-IR' => 'fa',
			'pl-PL' => 'pl',
			'pt-BR' => 'pt-BR',
			'pt-PT' => 'pt',
			'ro-RO' => 'ro',
			'ru-RU' => 'ru',
			'sr-RS' => 'sr',
			'es-ES' => 'es',
			'sk-SK' => 'sk',
			'sl-SL' => 'sl',
			'sv-SE' => 'sv',
			'th-TH' => 'th',
			'tr-TR' => 'tr',
			'uk-UA' => 'uk',
			'vi-VN' => 'vi',
		);
		if(isset($conversionTable[$tag])){
			$tag = $conversionTable[$tag];
		}else{
			$tag = 'en-GB';
		}
		if(hikashop_isClient('administrator')) {
			$base = '..';
		} else {
			$base = JURI::base(true);
		}

		$doc->addScript($base.'/plugins/hikashop/datepickerfield/jquery-ui-i18n.js');

		$js = '
window.hikashopDatepicker_excludeWDays = function(date, w, d, dt, rg, wp) {
	var day = date.getDay(),
		m = date.getMonth()+1,
		dd = date.getDate(),
		y = date.getFullYear(),
		md = m * 100 + dd,
		fd = y * 10000 + md,
		r = true;
	if(w) { for(var i = w.length - 1; r && i >= 0; i--) { r = (day != w[i]); }}
	if(d) { for(var i = d.length - 1; r && i >= 0; i--) { r = (md != d[i]); }}
	if(dt) { for(var i = dt.length - 1; r && i >= 0; i--) { r = (fd != dt[i]); }}
	if(rg) { for(var i = rg.length - 1; r && i >= 0; i--) {
		if(rg[i][2] == 2)
			r = (md < rg[i][0] || md > rg[i][1]);
		else
			r = (fd < rg[i][0] || fd > rg[i][1]);
	}}
	if(wp) { for(var i = wp.length - 1; r && i >= 0; i--) {
		var pm = wp[i][0], pd = wp[i][1], py = wp[i][2];
		r = !((pm == 0 || pm == m) && (pd == 0 || pd == dd) && (py == 0 || py == y));
	}}
	return [r, \'\'];
};
window.hikashopDatepicker = function(el) {
	if(typeof(el) == "string")
		el = hkjQuery("#" + el);
	var options = {};
	if(el.attr("data-options")) {
		options = Oby.evalJSON( el.attr("data-options") );
	}
	if(options["exclude"] || options["excludeDays"] || options["excludeDates"] || options["excludeRanges"] || options["excludePatterns"]) {
		options["beforeShowDay"] = function(date){ return window.hikashopDatepicker_excludeWDays(date, options["exclude"], options["excludeDays"], options["excludeDates"], options["excludeRanges"], options["excludePatterns"]); };
	}
	options["altField"] = "#"+el.attr("data-picker");
	options["altFormat"] = "yy/mm/dd";
	hkjQuery.datepicker.setDefaults(hkjQuery.datepicker.regional[\''.$tag.'\']);
	el.datepicker(options);

	el.change(function(){
		var e = hkjQuery(this), format = e.datepicker("option", "dateFormat"), dateValue = e.val();
		if(dateValue == "") {
			hkjQuery("#"+e.attr("data-picker")).val("");
		} else {
			try{
				if(options["dateFormat"] && options["dateFormat"].indexOf("d") == -1 && options["dateFormat"].indexOf("D") == -1){
					dateValue = "01/"+dateValue;
					format = "dd/"+format;
				}
				hkjQuery.datepicker.parseDate(format, dateValue);
			}catch(ex) {
				hkjQuery("#"+e.attr("data-picker")).val("");
			}
		}
		var hidden_input = document.getElementById(e.attr("data-picker"));
		if(hidden_input) {
			const event = new Event(\'change\');
			hidden_input.dispatchEvent(event);
		}
	});
};
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
		d.setDate(d.getDate() + 1); // start checking from day after start
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
			startDate = sel;
			endDate = null;
			state = "selectEnd";
		} else {
			if(sel.getTime() <= startDate.getTime()) {
				startDate = sel;
				endDate = null;
				state = "selectEnd";
			} else {
				var candidateEnd = sel;
				var nights = Math.round((sel.getTime() - startDate.getTime()) / 86400000);
				if(nights < minNights) {
					candidateEnd = new Date(startDate.getTime());
					candidateEnd.setDate(candidateEnd.getDate() + minNights);
				} else if(maxNights > 0 && nights > maxNights) {
					candidateEnd = new Date(startDate.getTime());
					candidateEnd.setDate(candidateEnd.getDate() + maxNights);
				}

				if(hasExcludedDayInRange(startDate, candidateEnd)) {
					startDate = sel;
					endDate = null;
					state = "selectEnd";
				} else {
					endDate = candidateEnd;
					state = "selectStart";
				}
			}
		}
		updateRangeDisplay();
		updateRangeHidden();
		hkjQuery(this).datepicker("refresh");
	};

	delete options["altField"];
	delete options["altFormat"];

	hkjQuery.datepicker.setDefaults(hkjQuery.datepicker.regional[\'' . $tag . '\']);
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
				nEl.textContent = nights + " ' . addslashes(JText::_('DATE_PICKER_RANGE_NIGHTS')) . '";
			} else {
				nEl.textContent = "";
			}
		}
	}

	function updateRangeHidden() {
		if(!hidden) return;
		if(startDate && endDate) {
			hidden.value = formatRangeYmd(startDate) + "000000-" + formatRangeYmd(endDate) + "000000";
		} else {
			hidden.value = "";
		}
		hidden.dispatchEvent(new Event("change"));
	}

	function formatRangeYmd(d) {
		return d.getFullYear() + ("0"+(d.getMonth()+1)).slice(-2) + ("0"+d.getDate()).slice(-2);
	}

	updateRangeDisplay();
};';

		$doc->addScriptDeclaration($js);
		$doc->addStyleSheet('//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');

		$init = true;
		return $init;
	}

	public function getFieldName(&$field, $requiredDisplay = false, $classname = '') {
		$app = JFactory::getApplication();
		if(hikashop_isClient('administrator')) return $this->trans($field->field_realname);
		$required = '';
		$options = '';
		$for = '';
		if($requiredDisplay && !empty($field->field_required))
			$required = '<span class="hikashop_field_required_label">*</span>';
		if(!empty($classname))
			$options = ' class="'.str_replace('"','',$classname).'"';
		if($this->displayFor)
			$for = ' for="'.$this->prefix.$field->field_namekey.$this->suffix.'"';
		return '<label'.$for.$options.'>'.$this->trans($field->field_realname).$required.'</label>';
	}

	public function trans($name) {
		$val = preg_replace('#[^a-z0-9]#i','_',strtoupper($name));

		$app = JFactory::getApplication();
		if(hikashop_isClient('administrator') && strcmp(JText::_($val), strip_tags(JText::_($val))) !== 0)
			$trans = $val;
		else
			$trans = JText::_($val);

		if($val == $trans)
			$trans = $name;
		return $trans;
	}

	public function show(&$field, $value) {
		if(!$this->init())
			return '';

		if($value === '')
			return '';

		if(self::isRangeValue($value)) {
			$parts = explode('-', $value, 2);
			$startVal = $parts[0];
			$endVal = $parts[1];

			if(is_string($field->field_options)) {
				$field->field_options = hikashop_unserialize($field->field_options);
			}
			$format = @$field->field_options['format'];
			if(strpos($format, '%') !== false) {
				$format = str_replace(array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),array('l','d','F','m','Y','y','H','i','s','D'),$format);
			}
			$joomlaFormat = str_replace(array('l','d','F','m','Y','y','H','i','s','D'),array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),$format);

			$startDate = $this->getDate($startVal);
			$startTs = $this->getTimestamp($startDate);
			$endDate = $this->getDate($endVal);
			$endTs = $this->getTimestamp($endDate);

			if(!empty($joomlaFormat)) {
				return hikashop_getDate($startTs, $joomlaFormat) . ' - ' . hikashop_getDate($endTs, $joomlaFormat);
			}
			return hikashop_getDate($startTs) . ' - ' . hikashop_getDate($endTs);
		}

		if(!empty($field->field_value) && !is_array($field->field_value)) {
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		if(isset($field->field_value[$value])) {
			$value = $field->field_value[$value]->value;
		}

		if(is_string($field->field_options)) {
			$field->field_options = hikashop_unserialize($field->field_options);
		}
		$format = @$field->field_options['format'];
		if(strpos($format, '%') !== false) {
			$format = str_replace(array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),array('l','d','F','m','Y','y','H','i','s','D'),$format);
		}

		$ret = $value;
		$date = $this->getDate($value);
		$timestamp = $this->getTimestamp($date);

		$joomlaFormat = str_replace(array('l','d','F','m','Y','y','H','i','s','D'),array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),$format);
		if(!empty($joomlaFormat))
			$ret = hikashop_getDate($timestamp, $joomlaFormat);
		else
			$ret = hikashop_getDate($timestamp);

		return $ret;
	}

	public function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null) {
		if(!$this->init())
			return '';

		$app = JFactory::getApplication();
		$ret = '';
		$timestamp = null;
		$id = $this->prefix . @$field->field_namekey . $this->suffix;

		$default_value = $field->field_default;
		if(!empty($value) && !empty($default_value) && !empty($datepicker_options['today']) && $test && ((int)$value == (int)$default_value)) {
			$value = null;
		}

		if(!empty($value)) {
			$value = $this->getDate($value);
			$timestamp = $this->getTimestamp($value);
		}

		$datepicker_options = @$field->field_options['datepicker_options'];
		if(!empty($datepicker_options)) {
			if(is_string($datepicker_options))
				$datepicker_options = hikashop_unserialize($datepicker_options);
		} else {
			$datepicker_options = array();
		}

		$dateOptions = array();

		if(!empty($datepicker_options['hour_extra_day'])) {
			$parts = explode(':',$datepicker_options['hour_extra_day']);
			$hour = (int)array_shift($parts);
			$minute = 0;
			if(count($parts))
				$minute = (int)array_shift($parts);
			$date_today = getdate();
			$current_hour = (int)$date_today['hours'];
			$current_minute = (int)$date_today['minutes'];
			if($current_hour > $hour || ($current_hour == $hour && $current_minute >= $minute)) {
				$datepicker_options['waiting'] = (int)$datepicker_options['waiting'] + 1;
				$datepicker_options['days_from_now'] = (int)$datepicker_options['days_from_now'] + 1;
			}
		}

		if(@$field->field_options['allow'] == 'future') {
			if(!empty($datepicker_options['waiting']))
				$dateOptions[] = '"minDate":'.(int)$datepicker_options['waiting'];
			else
				$dateOptions[] = '"minDate":0';
			if(!empty($datepicker_options['days_from_now']))
				$dateOptions[] = '"maxDate":'.(int)$datepicker_options['days_from_now'];
		} else if(@$field->field_options['allow'] == 'past') {
			if(!empty($datepicker_options['waiting']))
				$dateOptions[] = '"maxDate":'.(0 - (int)($datepicker_options['waiting']));
			else
				$dateOptions[] = '"maxDate":0';
			if(!empty($datepicker_options['days_from_now']))
				$dateOptions[] = '"minDate":'.(0 - (int)($datepicker_options['days_from_now']));
		}

		$format = @$field->field_options['format'];
		if(strpos($format,'%') !== false) {
			$format = str_replace(array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),array('l','d','F','m','Y','y','H','i','s','D'),$format);
		}
		if(!empty($format)) {
			$dateOptions[] = '"dateFormat":"'.str_replace(
					array('j','d', 'z','D','l', 'n','m', 'M','F', 'y','Y'),
					array('d','dd','o','D','DD','m','mm','M','MM','y','yy'),
					$format
				).'"';
		}

		$joomlaFormat = str_replace(array('l','d','F','m','Y','y','H','i','s','D'),array('%A','%d','%B','%m','%Y','%y','%H','%M','%S','%a'),$format);
		if(!empty($value) && !empty($value['y'])) {
			if(!empty($joomlaFormat))
				$txtValue = hikashop_getDate($timestamp, $joomlaFormat);
			else
				$txtValue = hikashop_getDate($timestamp);
		} else {
			$timestamp = 0;
			$txtValue = '';
		}

		if(!empty($datepicker_options['today']) && empty($timestamp)) {
			$timestamp = time();

			$allow_check = true;
			if (!empty($datepicker_options['check_dates']) && $datepicker_options['check_dates'] == 'front' && hikashop_isClient('administrator'))
				$allow_check = false;

			if((empty($field->field_options['allow']) || $field->field_options['allow'] == 'future') && $allow_check) {

				if(!empty($datepicker_options['waiting']))
					$timestamp += 86400 * (int)$datepicker_options['waiting'];

				do {
					$inc = $this->checkFuturRules($timestamp, $datepicker_options);
					if(is_int($inc) && (int)$inc > 0)
						$timestamp += 86400 * (int)$inc;
				} while(is_int($inc) && $inc > 0);
			}

			if(!empty($joomlaFormat))
				$txtValue = hikashop_getDate($timestamp, $joomlaFormat);
			else
				$txtValue = hikashop_getDate($timestamp);
		}
		if(empty($value) && !empty($timestamp))
			$value = $this->getDate($timestamp);

		if(!empty($txtValue))
			$dateOptions[] = '"defaultDate":"'.$txtValue.'"';

		if(!empty($datepicker_options['monday_first']))
			$dateOptions[] = '"firstDay":1';
		else
			$dateOptions[] = '"firstDay":0';

		if(!empty($datepicker_options['change_month']))
			$dateOptions[] = '"changeMonth":true';
		if(!empty($datepicker_options['change_year'])){
			$dateOptions[] = '"changeYear":true';
			if(!empty($datepicker_options['year_range_start']) || !empty($datepicker_options['year_range_end'])){
				if(empty($datepicker_options['year_range_start'])){
					$datepicker_options['year_range_start']='c-10';
				}
				if(empty($datepicker_options['year_range_end'])){
					$datepicker_options['year_range_end']='c+10';
				}
				$dateOptions[] = '"yearRange": "'.$datepicker_options['year_range_start'].':'.$datepicker_options['year_range_end'].'"';
			}
		}
		if(!empty($datepicker_options['show_btn_panel']))
			$dateOptions[] = '"showButtonPanel":true';
		if(!empty($datepicker_options['show_months']) && (int)$datepicker_options['show_months'] > 1 && (int)$datepicker_options['show_months'] <= 12)
			$dateOptions[] = '"numberOfMonths":'.(int)$datepicker_options['show_months'];

		if(!empty($datepicker_options['other_month'])) {
			$dateOptions[] = '"showOtherMonths":true';
			$dateOptions[] = '"selectOtherMonths":true';
		}

		$spe_day_format = 'm/d/Y';
		if(!empty($datepicker_options['exclude_days_format'])) {
			$spe_day_format = $datepicker_options['exclude_days_format'];
		}

		$excludeDays = array();
		for($i = 0; $i <= 6; $i++) { if(!empty($datepicker_options['forbidden_'.$i])) { $excludeDays[] = $i; } }
		if(!empty($excludeDays)) $dateOptions[] = '"exclude":['.implode(',',$excludeDays).']';

		$excludeDays = explode('|', str_replace(array("\r\n","\n","\r",' '),array('|','|','|','|'), (string)@$datepicker_options['excludes']));
		$date_today = getdate();
		$disabled_dates = array();
		$disabled_days = array();
		$disabled_ranges = array();
		$disabled_patterns = array();
		foreach($excludeDays as $day){
			if(strpos($day, '-') === false) {
				$day = explode('/', trim($day));

				$hasWildcard = false;
				foreach($day as $part) {
					if(trim($part) == '*') { $hasWildcard = true; break; }
				}

				if($hasWildcard) {
					$this->parseWildcardPattern($day, $spe_day_format, $pm, $pd, $py);
					$disabled_patterns[] = '[' . $pm . ',' . $pd . ',' . $py . ']';
				} else {
					$ret = $this->convertDay($day, $date_today, $spe_day_format);
					if(!empty($ret)) {
						if(count($day) == 3)
							$disabled_dates[] = $ret;
						if(count($day) == 2)
							$disabled_days[] = $ret;
					}
				}
			} else {
				$days = explode('-', trim($day));
				$day1 = explode('/', trim($days[0]));
				$ret1 = $this->convertDay($day1, $date_today, $spe_day_format);
				$day2 = explode('/', trim($days[1]));
				$ret2 = $this->convertDay($day2, $date_today, $spe_day_format);

				if(!empty($ret1) && !empty($ret2) && count($day1) == count($day2)) {
					$disabled_ranges[] = '['.$ret1.','.$ret2.','.count($day1).']';
				}
			}
		}
		if(!empty($disabled_days))
			$dateOptions[] = '"excludeDays":['.implode(',',$disabled_days).']';
		if(!empty($disabled_dates))
			$dateOptions[] = '"excludeDates":['.implode(',',$disabled_dates).']';
		if(!empty($disabled_ranges))
			$dateOptions[] = '"excludeRanges":['.implode(',',$disabled_ranges).']';
		if(!empty($disabled_patterns))
			$dateOptions[] = '"excludePatterns":['.implode(',',$disabled_patterns).']';

		$app = JFactory::getApplication();
		$app->triggerEvent('onPrepareDatePickerFieldOptions', array(&$dateOptions, &$field, &$datepicker_options, &$value));
		if(!empty($dateOptions)) {
			$dateOptions = '{' . implode(',', $dateOptions) . '}';
		} else {
			$dateOptions = '';
		}

		$datepicker_id = $id . '_input';

		if(!empty($datepicker_options['range'])) {
			$hasMonths = (strpos($dateOptions, '"numberOfMonths"') !== false);
			if(!$hasMonths) {
				$monthsCount = max(2, (int)@$datepicker_options['show_months']);
				$dateOptions = rtrim($dateOptions, '}');
				if(strlen($dateOptions) > 1) $dateOptions .= ',';
				$dateOptions .= '"numberOfMonths":' . $monthsCount . '}';
			}

			$hiddenValue = '';
			$rawValue = hikaInput::get()->getString($field->field_namekey, '');
			if(empty($rawValue)) {
				$rawValue = hikaInput::get()->getString('item_data_' . $field->field_namekey, '');
			}
			if(empty($rawValue)) {
				$formData = hikaInput::get()->get('data', array(), 'array');
				if(!empty($formData['item'][$field->field_namekey]))
					$rawValue = $formData['item'][$field->field_namekey];
				elseif(!empty($formData['order'][$field->field_namekey]))
					$rawValue = $formData['order'][$field->field_namekey];
			}
			if(self::isRangeValue($rawValue)) {
				$hiddenValue = $rawValue;
			}

			$rangeMinNights = isset($datepicker_options['range_min_nights']) ? (int)$datepicker_options['range_min_nights'] : 1;
			$rangeMaxNights = !empty($datepicker_options['range_max_nights']) ? (int)$datepicker_options['range_max_nights'] : 0;
			$rangeExcludeEnd = isset($datepicker_options['range_exclude_end']) ? (int)$datepicker_options['range_exclude_end'] : 1;

			$ret = '<div class="hikashop_datepicker_range">'
				. '<div class="hikashop_datepicker_range_display" aria-live="polite" aria-atomic="true">'
				. '<div class="hikashop_datepicker_range_cell"><span class="hikashop_datepicker_range_label">' . JText::_('DATE_PICKER_RANGE_START') . '</span><strong id="' . $id . '_start_display">-</strong></div>'
				. '<div class="hikashop_datepicker_range_cell"><span class="hikashop_datepicker_range_label">' . JText::_('DATE_PICKER_RANGE_END') . '</span><strong id="' . $id . '_end_display">-</strong></div>'
				. '<div class="hikashop_datepicker_range_cell hikashop_datepicker_range_nights" id="' . $id . '_nights_display"></div>'
				. '</div>'
				. '<div id="' . $datepicker_id . '" data-picker="' . $id . '" data-options=\'' . $dateOptions . '\''
				. ' data-range="1"'
				. ' data-range-min-nights="' . $rangeMinNights . '"'
				. ' data-range-max-nights="' . $rangeMaxNights . '"'
				. ' data-range-exclude-end="' . $rangeExcludeEnd . '"'
				. ' class="hikashop_datepicker" role="application" aria-label="' . htmlspecialchars($this->trans(@$field->field_realname), ENT_COMPAT, 'UTF-8') . '"></div>'
				. '<input type="hidden" value="' . $hiddenValue . '" name="' . $map . '" id="' . $id . '"/>'
				. '</div>';

			$ret .= '
<script type="text/javascript">
window.hikashop.ready(function(){ window.hikashopDatepickerRange("' . $datepicker_id . '"); });
</script>
';
		}
		else if(empty($datepicker_options['inline'])) {
			$ariaLabel = ' aria-label="' . htmlspecialchars($this->trans(@$field->field_realname), ENT_COMPAT, 'UTF-8') . '"';
			if((hikashop_isClient('administrator') && HIKASHOP_BACK_RESPONSIVE) || (!hikashop_isClient('administrator') && HIKASHOP_RESPONSIVE)) {
				$ret = '<div class="input-append">'.
					'<input type="text" id="'.$datepicker_id.'" data-picker="'.$id.'" data-options=\''.$dateOptions.'\' class="hikashop_datepicker" value="'.$txtValue.'"'.$ariaLabel.' aria-haspopup="dialog"/>'.
					'<button class="btn" onclick="document.getElementById(\''.$datepicker_id.'\').focus();return false;" aria-label="' . htmlspecialchars(JText::_('HIKA_OPEN_CALENDAR'), ENT_COMPAT, 'UTF-8') . '"><i class="icon-calendar"></i></button>'.
					'</div>';
			} else {
				$ret = '<input type="text" id="'.$datepicker_id.'" data-picker="'.$id.'" data-options=\''.$dateOptions.'\' class="hikashop_datepicker form-control" value="'.$txtValue.'"'.$ariaLabel.' aria-haspopup="dialog"/>';
			}

			$ret .= '<input type="hidden" value="'.$this->serializeDate($value).'" name="'.$map.'" id="'.$id.'"/>';

			$ret .= '
<script type="text/javascript">
window.hikashop.ready(function(){ window.hikashopDatepicker("'.$datepicker_id.'"); });
</script>
';
		} else {
			$ret = '<div id="'.$datepicker_id.'" data-picker="'.$id.'" data-options=\''.$dateOptions.'\' class="hikashop_datepicker" value="'.$txtValue.'" role="application" aria-label="' . htmlspecialchars($this->trans(@$field->field_realname), ENT_COMPAT, 'UTF-8') . '"></div>';

			$ret .= '<input type="hidden" value="'.$this->serializeDate($value).'" name="'.$map.'" id="'.$id.'"/>';

			$ret .= '
<script type="text/javascript">
window.hikashop.ready(function(){ window.hikashopDatepicker("'.$datepicker_id.'"); });
</script>
';
		}

		return $ret;
	}

	private function convertDay($day, $today, $spe_day_format) {
		if(count($day) == 3) {
			$y = (int)$day[2];
			if($y < 100) $y += 2000;
			if($spe_day_format == 'dmY') {
				$d = (int)$day[0]; $m = (int)$day[1];
			} else {
				$d = (int)$day[1]; $m = (int)$day[0];
			}

			if( empty($today) || $y >= $today['year'] || $m >= $today['mon'] || $d >= $today['mday'] ) {
				return $y.(($m<10)?'0':'').$m.(($d<10)?'0':'').$d;
			}
			return '';
		}

		if(count($day) == 2) {
			if($spe_day_format == 'dmY') {
				$d = (int)$day[0]; $m = (int)$day[1];
			} else {
				$d = (int)$day[1]; $m = (int)$day[0];
			}
			return $m.(($d<10)?'0':'').$d;
		}
		return '';
	}

	private function parseWildcardPattern($day, $spe_day_format, &$pm, &$pd, &$py) {
		if($spe_day_format == 'dmY') {
			$pd = (trim($day[0]) != '*') ? (int)$day[0] : 0;
			$pm = (count($day) >= 2 && trim($day[1]) != '*') ? (int)$day[1] : 0;
		} else {
			$pm = (trim($day[0]) != '*') ? (int)$day[0] : 0;
			$pd = (count($day) >= 2 && trim($day[1]) != '*') ? (int)$day[1] : 0;
		}
		$py = (count($day) >= 3 && trim($day[2]) != '*') ? (int)$day[2] : 0;
		if($py > 0 && $py < 100) $py += 2000;
	}

	private function getDate($value, $format = 'm/d/Y') {
		$ret = array(
			'y' => 0, 'm' => 0, 'd' => 0,
			'h' => 0, 'i' => 0, 's' => 0
		);

		if(empty($value))
			return $ret;

		$dateValue = $value;
		if(preg_match('#^([0-9]+)$#', (string)$value)) {
			if(strlen((string)$value) == 14) {
				$dateValue = substr((string)$value,0,4) . '/' . substr((string)$value,4,2) . '/' . substr((string)$value,6,2);
			} else {
				$dateValue = hikashop_getDate($value, '%Y/%m/%d');
			}
			list($y,$m,$d) = explode('/', (string)$dateValue, 3);
		} else {
			$y = 0; $m = 0; $d = 0;
			$timestamp = strtotime(str_replace('/', '-', (string)$value));
			if($timestamp !== false && $timestamp !== -1 && $timestamp > 0) {
				$dateValue = date('Y/m/d', $timestamp);
				list($y,$m,$d) = explode('/', (string)$dateValue, 3);
			} else {
				$v = explode('/', (string)$value, 3);
				if(count($v) == 3)
					list($y,$m,$d) = $v;
			}
		}

		$ret['y'] = (int)$y;
		$ret['m'] = (int)$m;
		$ret['d'] = (int)$d;

		return $ret;
	}

	private function getTimestamp($value) {
		if(is_array($value)) {
			$value = $value['y'] . '/' . $value['m'] . '/' . $value['d'];
			if(empty($this->params)) {
				$plugin = JPluginHelper::getPlugin('hikashop', 'datepickerfield');
				$this->params = new JRegistry(@$plugin->params);
			}
			if($this->params->get('time_shift', 0))
				$value .= ' 12:00:00';
		}
		$ret = hikashop_getTime($value);

		return $ret;
	}

	private function serializeDate($value) {
		if(empty($value))
			return '';

		$ret = $value['y'];

		$keys = array('m' => 12, 'd' => 31, 'h' => 24, 'i' => 60, 's' => 60);
		foreach($keys as $k => $v) {
			$t = (int)$value[$k];
			if($t > $v) $t = $v;
			if($t < 0) $t = 0;
			if($t < 10) $ret .= '0';
			$ret .= $t;
		}

		return $ret;
	}

	public function JSCheck(&$oneField, &$requiredFields, &$validMessages, &$values) {
		if(empty($oneField->field_required))
			return;

		$requiredFields[] = $oneField->field_namekey;
		if(!empty($oneField->field_options['errormessage'])) {
			$validMessages[] = addslashes($this->trans($oneField->field_options['errormessage']));
		}else{
			$validMessages[] = addslashes(JText::sprintf('FIELD_VALID', $this->trans($oneField->field_realname)));
		}
	}

	protected function checkFuturRules($timestamp, &$datepicker_options) {
		$phpDate = getdate($timestamp);
		$wday = $phpDate['wday'];
		$wday_cursor = $wday;

		$ret = 0;

		for($i = $wday; $i <= 6; $i++) {
			if(!empty($datepicker_options['forbidden_'.$i]) && $i == $wday_cursor) {
				$ret++;
				$wday_cursor = (($wday_cursor+1) % 7);
			}
		}
		for($i = 0; $i < $wday; $i++) {
			if(!empty($datepicker_options['forbidden_'.$i]) && $i == $wday_cursor) {
				$ret++;
				$wday_cursor = (($wday_cursor+1) % 7);
			}
		}

		if($ret == 7)
			return 0;

		if(empty($datepicker_options['excludes']))
			return $ret;

		$spe_day_format = 'm/d/Y';
		if(!empty($datepicker_options['exclude_days_format'])) {
			$spe_day_format = $datepicker_options['exclude_days_format'];
		}

		$dateValue = $this->getDate($timestamp + (86400*$ret));
		$fullDayCode = $dateValue['y'] * 10000 + $dateValue['m'] * 100 + $dateValue['d'];
		$dayCode = $dateValue['m'] * 100 + $dateValue['d'];

		$excludeDays = explode('|', str_replace(array("\r\n","\n","\r",' '),array('|','|','|','|'), $datepicker_options['excludes']));
		foreach($excludeDays as $day) {
			if(strpos($day, '-') === false) {
				$day = explode('/', trim($day));

				$hasWildcard = false;
				foreach($day as $part) {
					if(trim($part) == '*') { $hasWildcard = true; break; }
				}

				$excluded = false;
				if($hasWildcard) {
					$this->parseWildcardPattern($day, $spe_day_format, $pm, $pd, $py);
					$excluded = ($pm == 0 || $pm == $dateValue['m']) && ($pd == 0 || $pd == $dateValue['d']) && ($py == 0 || $py == $dateValue['y']);
				} else {
					$exc_day = (int)$this->convertDay($day, null, $spe_day_format);
					if(!empty($exc_day))
						$excluded = (count($day) == 3 && $fullDayCode == $exc_day) || (count($day) == 2 && $dayCode == $exc_day);
				}

				if($excluded) {
					$ret++;
					$dateValue = $this->getDate($timestamp + (86400*$ret));
					$fullDayCode = $dateValue['y'] * 10000 + $dateValue['m'] * 100 + $dateValue['d'];
					$dayCode = $dateValue['m'] * 100 + $dateValue['d'];
				}
			} else {
				$days = explode('-', trim($day));
				$day1 = explode('/', trim($days[0]));
				$ret1 = (int)$this->convertDay($day1, null, $spe_day_format);
				$day2 = explode('/', trim($days[1]));
				$ret2 = (int)$this->convertDay($day2, null, $spe_day_format);

				if(!empty($ret1) && !empty($ret2) && count($day1) == count($day2) && $ret1 < $ret2) {
					$final_date = 0;
					if(count($day1) == 3 && $fullDayCode >= $ret1 && $fullDayCode <= $ret2) {
						$final_date = floor($ret2 / 10000) . '/' . floor(($ret2 % 10000) / 100) . '/' . ($ret2 % 100);
					} else if(count($day1) == 2 && $dayCode >= $ret1 && $dayCode <= $ret2) {
						$final_date = $dateValue['y'] . '/' . floor($ret2 / 100) . '/' . ($ret2%100);
					}
					if(!empty($final_date)) {
						$t1 = hikashop_getTime($final_date);
						$t2 = hikashop_getTime($dateValue['y'].'/'.$dateValue['m'].'/'.$dateValue['d']);

						$ret += 1 + (int)(($t1 - $t2) / 86400);
						$dateValue = $this->getDate($timestamp + (86400*$ret));
						$fullDayCode = $dateValue['y'] * 10000 + $dateValue['m'] * 100 + $dateValue['d'];
						$dayCode = $dateValue['m'] * 100 + $dateValue['d'];
					}
				}
			}
		}

		return $ret;
	}

	public function check(&$field, &$value, $oldvalue) {
		$app = JFactory::getApplication();

		$fieldClass = hikashop_get('class.field');
		$fullField = $fieldClass->get($field->field_id);

		$datepicker_options = @$fullField->field_options['datepicker_options'];
		if(!empty($datepicker_options)) {
			if(is_string($datepicker_options))
				$datepicker_options = hikashop_unserialize($datepicker_options);
		} else {
			$datepicker_options = array();
		}

		if(!empty($datepicker_options['range']) && !empty($value) && self::isRangeValue($value)) {
			$parts = explode('-', $value, 2);
			$startVal = $parts[0];
			$endVal = $parts[1];

			$startDate = $this->getDate($startVal);
			$startVal = $this->serializeDate($startDate);
			$endDate = $this->getDate($endVal);
			$endVal = $this->serializeDate($endDate);
			$name = $this->trans($field->field_realname);

			$startTs = $this->getTimestamp($startDate);
			$endTs = $this->getTimestamp($endDate);
			if($endTs <= $startTs) {
				if($this->report)
					$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_END_BEFORE_START', $name), 'error');
				return false;
			}

			$nights = round(($endTs - $startTs) / 86400);
			$minNights = isset($datepicker_options['range_min_nights']) ? (int)$datepicker_options['range_min_nights'] : 1;
			$maxNights = !empty($datepicker_options['range_max_nights']) ? (int)$datepicker_options['range_max_nights'] : 0;

			if($nights < $minNights) {
				if($this->report)
					$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_TOO_SHORT', $minNights), 'error');
				return false;
			}
			if($maxNights > 0 && $nights > $maxNights) {
				if($this->report)
					$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_TOO_LONG', $maxNights), 'error');
				return false;
			}

			if(!empty($datepicker_options['hour_extra_day'])) {
				$hParts = explode(':', $datepicker_options['hour_extra_day']);
				$hour = (int)array_shift($hParts);
				$minute = count($hParts) ? (int)array_shift($hParts) : 0;
				$now = getdate();
				if((int)$now['hours'] > $hour || ((int)$now['hours'] == $hour && (int)$now['minutes'] >= $minute)) {
					$datepicker_options['waiting'] = (int)@$datepicker_options['waiting'] + 1;
					$datepicker_options['days_from_now'] = (int)@$datepicker_options['days_from_now'] + 1;
				}
			}

			$allow_check = true;
			if(!empty($datepicker_options['check_dates']) && $datepicker_options['check_dates'] == 'front' && hikashop_isClient('administrator'))
				$allow_check = false;

			if(!empty($fullField->field_options['allow']) && $allow_check) {
				$today = getdate();
				$fullTodayCode = (int)$today['year'] * 10000 + (int)$today['mon'] * 100 + (int)$today['mday'];
				$startDayCode = $startDate['y'] * 10000 + $startDate['m'] * 100 + $startDate['d'];
				$endDayCode = $endDate['y'] * 10000 + $endDate['m'] * 100 + $endDate['d'];
				$waiting = (int)@$datepicker_options['waiting'];

				if($fullField->field_options['allow'] == 'future') {
					if($startDayCode < ($fullTodayCode + $waiting)) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $name), $field);
						return false;
					}
					if(!empty($datepicker_options['days_from_now']) && $endDayCode > ($fullTodayCode + (int)$datepicker_options['days_from_now'])) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $name), $field);
						return false;
					}
				}

				if($fullField->field_options['allow'] == 'past') {
					if($endDayCode > ($fullTodayCode - $waiting)) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $name), $field);
						return false;
					}
					if(!empty($datepicker_options['days_from_now']) && $startDayCode < ($fullTodayCode - (int)$datepicker_options['days_from_now'])) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $name), $field);
						return false;
					}
				}
			}

			$spe_day_format = !empty($datepicker_options['exclude_days_format']) ? $datepicker_options['exclude_days_format'] : 'm/d/Y';
			$excludePatterns = array();
			$excludeSpecificDates = array();
			$excludeRecurringDays = array();
			$excludeRanges = array();

			if(!empty($datepicker_options['excludes'])) {
				$excludeLines = explode('|', str_replace(array("\r\n", "\n", "\r", ' '), array('|', '|', '|', '|'), $datepicker_options['excludes']));
				foreach($excludeLines as $exDay) {
					if(empty(trim($exDay)))
						continue;
					if(strpos($exDay, '-') === false) {
						$exParts = explode('/', trim($exDay));
						$hasWildcard = false;
						foreach($exParts as $part) {
							if(trim($part) == '*') { $hasWildcard = true; break; }
						}
						if($hasWildcard) {
							$this->parseWildcardPattern($exParts, $spe_day_format, $pm, $pd, $py);
							$excludePatterns[] = array('m' => $pm, 'd' => $pd, 'y' => $py);
						} else {
							$ret = (int)$this->convertDay($exParts, null, $spe_day_format);
							if(!empty($ret)) {
								if(count($exParts) == 3)
									$excludeSpecificDates[] = $ret;
								elseif(count($exParts) == 2)
									$excludeRecurringDays[] = $ret;
							}
						}
					} else {
						$exDays = explode('-', trim($exDay));
						$exDay1 = explode('/', trim($exDays[0]));
						$ret1 = (int)$this->convertDay($exDay1, null, $spe_day_format);
						$exDay2 = explode('/', trim($exDays[1]));
						$ret2 = (int)$this->convertDay($exDay2, null, $spe_day_format);
						if(!empty($ret1) && !empty($ret2) && count($exDay1) == count($exDay2))
							$excludeRanges[] = array('start' => $ret1, 'end' => $ret2, 'type' => count($exDay1));
					}
				}
			}

			$checkTs = $startTs;
			while($checkTs < $endTs) {
				$checkDate = getdate($checkTs);
				$checkWday = $checkDate['wday'];
				$checkMonth = (int)$checkDate['mon'];
				$checkDay = (int)$checkDate['mday'];
				$checkYear = (int)$checkDate['year'];
				$checkFullCode = $checkYear * 10000 + $checkMonth * 100 + $checkDay;
				$checkDayCode = $checkMonth * 100 + $checkDay;

				if(!empty($datepicker_options['forbidden_' . $checkWday])) {
					if($this->report)
						$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
					return false;
				}

				foreach($excludeSpecificDates as $exCode) {
					if($checkFullCode == $exCode) {
						if($this->report)
							$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
						return false;
					}
				}

				foreach($excludeRecurringDays as $exCode) {
					if($checkDayCode == $exCode) {
						if($this->report)
							$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
						return false;
					}
				}

				foreach($excludePatterns as $pat) {
					if(($pat['m'] == 0 || $pat['m'] == $checkMonth) && ($pat['d'] == 0 || $pat['d'] == $checkDay) && ($pat['y'] == 0 || $pat['y'] == $checkYear)) {
						if($this->report)
							$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
						return false;
					}
				}

				foreach($excludeRanges as $rng) {
					if($rng['type'] == 3 && $checkFullCode >= $rng['start'] && $checkFullCode <= $rng['end']) {
						if($this->report)
							$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
						return false;
					}
					if($rng['type'] == 2 && $checkDayCode >= $rng['start'] && $checkDayCode <= $rng['end']) {
						if($this->report)
							$app->enqueueMessage(JText::sprintf('DATE_PICKER_RANGE_EXCLUDED_DAY', $name, date('Y-m-d', $checkTs)), 'error');
						return false;
					}
				}

				$checkTs += 86400;
			}

			$value = $startVal . '-' . $endVal;
			return true;
		}

		if(!empty($value)) {
			$dateValue = $this->getDate($value);
			$value = $this->serializeDate($dateValue);
		} else {
			$value = '';
			$dateValue = array();
		}

		$app->triggerEvent('onCheckDatePickerField', array(&$field, &$datepicker_options, &$value));

		if(!empty($value) && !empty($dateValue['y'])) {
			$fullDayCode = $dateValue['y'] * 10000 + $dateValue['m'] * 100 + $dateValue['d'];
			$dayCode = $dateValue['m'] * 100 + $dateValue['d'];

			$today = getdate();
			$today_year = (int)$today['year'];
			$today_month = (int)$today['mon'];
			$today_day = (int)$today['mday'];

			$fullTodayCode = $today_year * 10000 + $today_month * 100 + $today_day;
			$todayCode = $today_month * 100 + $today_day;

			if(!empty($datepicker_options['hour_extra_day'])) {
				$hour = (int)$datepicker_options['hour_extra_day'];
				$date_today = getdate();
				$current_hour = (int)$date_today['hours'];
				if($current_hour >= $hour) {
					$datepicker_options['waiting'] = (int)$datepicker_options['waiting'] + 1;
					$datepicker_options['days_from_now'] = (int)@$datepicker_options['days_from_now'] + 1;
				}
			}

			$allow_check = true;
			if (!empty($datepicker_options['check_dates']) && $datepicker_options['check_dates'] == 'front' && hikashop_isClient('administrator'))
				$allow_check = false;

			if(!empty($fullField->field_options['allow']) && $allow_check) {

				if($fullField->field_options['allow'] == 'future') {
					$todayCode += (int)@$datepicker_options['waiting'];
				}
				if($fullField->field_options['allow'] == 'past') {
					$todayCode -= (int)@$datepicker_options['waiting'];
				}

				if($fullField->field_options['allow'] == 'future' && $fullDayCode < ($fullTodayCode + (int)@$datepicker_options['waiting'])) {
					$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $this->trans($field->field_realname)), $field);
					return false;
				}

				if($fullField->field_options['allow'] == 'past' && $fullDayCode > ($fullTodayCode - (int)@$datepicker_options['waiting'])) {
					$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $this->trans($field->field_realname)), $field);
					return false;
				}
				if(!empty($datepicker_options['days_from_now'])) {
					if($fullField->field_options['allow'] == 'future' && $fullDayCode > ($fullTodayCode + (int)$datepicker_options['days_from_now'])) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $this->trans($field->field_realname)), $field);
						return false;
					}
					if($fullField->field_options['allow'] == 'past' && $fullDayCode < ($fullTodayCode - (int)$datepicker_options['days_from_now'])) {
						$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $this->trans($field->field_realname)), $field);
						return false;
					}
				}
			}

			$timestamp = $this->getTimestamp($dateValue);
			$phpDate = getdate($timestamp);

			if($phpDate['hours'] != 0) {
				$timestamp -= $this->timeoffset;
				$phpDate = getdate($timestamp);
			}

			$wday = $phpDate['wday'];

			$excludeDays = array();
			for($i = 0; $i <= 6; $i++) {
				if(!empty($datepicker_options['forbidden_'.$i]) && $i == $wday) {
					$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
					return false;
				}
			}

			if(!empty($datepicker_options['excludes'])) {
				$spe_day_format = 'm/d/Y';
				if(!empty($datepicker_options['exclude_days_format'])) {
					$spe_day_format = $datepicker_options['exclude_days_format'];
				}

				$excludeDays = explode('|', str_replace(array("\r\n","\n","\r",' '),array('|','|','|','|'), $datepicker_options['excludes']));
				foreach($excludeDays as $day){
					if(strpos($day, '-') === false) {
						$day = explode('/', trim($day));

						$hasWildcard = false;
						foreach($day as $part) {
							if(trim($part) == '*') { $hasWildcard = true; break; }
						}

						if($hasWildcard) {
							$this->parseWildcardPattern($day, $spe_day_format, $pm, $pd, $py);
							if(($pm == 0 || $pm == $dateValue['m']) && ($pd == 0 || $pd == $dateValue['d']) && ($py == 0 || $py == $dateValue['y'])) {
								$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
								return false;
							}
						} else {
							$ret = (int)$this->convertDay($day, null, $spe_day_format);
							if(!empty($ret)) {
								if(count($day) == 3 && $fullDayCode == $ret) {
									$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
									return false;
								}
								if(count($day) == 2 && $dayCode == $ret) {
									$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
									return false;
								}
							}
						}
					} else {
						$days = explode('-', trim($day));
						$day1 = explode('/', trim($days[0]));
						$ret1 = (int)$this->convertDay($day1, null, $spe_day_format);
						$day2 = explode('/', trim($days[1]));
						$ret2 = (int)$this->convertDay($day2, null, $spe_day_format);

						if(!empty($ret1) && !empty($ret2) && count($day1) == count($day2) && $ret1 < $ret2) {
							if(count($day1) == 3 && $fullDayCode >= $ret1 && $fullDayCode <= $ret2) {
								$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
								return false;
							} else if(count($day1) == 2 && $dayCode >= $ret1 && $dayCode <= $ret2) {
								$this->_displayMessage(JText::sprintf('DATE_PICKER_INCORRECT_DATE_FOR', $this->trans($field->field_realname)), $field);
								return false;
							}
						}
					}
				}
			}
		}

		if(!$field->field_required || strlen((string)$value) || strlen((string)$oldvalue))
			return true;


		if(!empty($fullField->field_options['errormessage']))
			$this->_displayMessage($this->trans($fullField->field_options['errormessage']), $field);
		else
			$this->_displayMessage(JText::sprintf('PLEASE_FILL_THE_FIELD', $this->trans($field->field_realname)), $field);
		return false;
	}
	private function _displayMessage($message, &$field) {
		if($this->report === true) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($message, 'error');
		} else {
			$this->parent->messages[$this->prefix.$field->field_namekey] = array($message);
		}
	}

	public static function isRangeValue($value) {
		return !empty($value) && preg_match('/^\d{14}-\d{14}$/', $value);
	}

	public static function expandRange($value, $excludeEnd = true) {
		if(!preg_match('/^(\d{4})(\d{2})(\d{2})\d{6}-(\d{4})(\d{2})(\d{2})\d{6}$/', $value, $m))
			return array($value);

		$startTs = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
		$endTs = mktime(0, 0, 0, (int)$m[5], (int)$m[6], (int)$m[4]);

		$dates = array();
		$ts = $startTs;
		while($ts < $endTs || (!$excludeEnd && $ts <= $endTs)) {
			$dates[] = date('Ymd', $ts) . '000000';
			$ts += 86400;
		}
		return $dates;
	}
}
