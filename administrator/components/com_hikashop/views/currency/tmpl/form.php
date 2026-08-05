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
?><div class="iframedoc" id="iframedoc"></div>
<form action="index.php?option=<?php echo HIKASHOP_COMPONENT ?>&amp;ctrl=currency" method="post"  name="adminForm" id="adminForm" enctype="multipart/form-data">
<div id="page-currency" class="hk-row-fluid hikashop_backend_tile_edition">
	<div class="hkc-md-6 hikashop_tile_block"><div>
		<div class="hikashop_tile_title"><?php
			echo JText::_('CURRENCY_INFORMATION');
		?></div>
					<table class="admintable table" width="280px" style="margin:auto">
						<tr>
							<td class="key">
									<?php echo JText::_( 'HIKA_NAME' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_name]" value="<?php echo $this->escape(@$this->element->currency_name); ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'CURRENCY_CODE' ); ?>
							</td>
							<td>
								<?php if(empty($this->element->currency_id)){
									$type = 'text';
								}else{
									$type = 'hidden';
									echo @$this->element->currency_code;
								} ?>
								<input type="<?php echo $type; ?>" name="data[currency][currency_code]" value="<?php echo @$this->element->currency_code; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'CURRENCY_SYMBOL' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_symbol]" value="<?php echo $this->escape(@$this->element->currency_symbol); ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'RATE' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_rate]" value="<?php echo @$this->element->currency_rate; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
								<?php echo JText::_( 'HIKA_LAST_MODIFIED' ); ?>
							</td>
							<td>
								<?php echo hikashop_getDate(@$this->element->currency_modified); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'CURRENCY_PERCENT_FEE' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_percent_fee]" value="<?php echo @$this->element->currency_percent_fee; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'HIKA_PUBLISHED' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_published]" , '',@$this->element->currency_published	); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'CURRENCY_DISPLAYED' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_displayed]" , '',@$this->element->currency_displayed	); ?>
							</td>
						</tr>
					</table>
				</fieldset>
	</div></div>
	<div class="hkc-md-6 hikashop_tile_block"><div>
		<div class="hikashop_tile_title"><?php echo JText::_('CURRENCY_DISPLAY_EXAMPLE'); ?></div>

		<style>

			.hk-preview-box {
				background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
				border: 1px solid #dee2e6;
				border-radius: 8px;
				padding: 24px;
				text-align: center;
				margin-bottom: 30px;
				box-shadow: 0 4px 12px rgba(0,0,0,0.05);
			}
			.hk-preview-positive {
				font-size: 28px;
				font-weight: 700;
				color: #2e7d32;
				margin-bottom: 8px;
				text-shadow: 0 1px 0 rgba(255,255,255,0.8);
			}
			.hk-preview-negative {
				font-size: 20px;
				font-weight: 600;
				color: #c62828;
			}
			.hk-pb-row { 
				margin-bottom: 20px; 
				padding-bottom: 15px;
				border-bottom: 1px dashed #767575ff;
			}
			.hk-pb-row:last-child { border-bottom: none; }
			.hk-pb-label { 
				font-weight: 600; 
				display: block; 
				margin-bottom: 10px; 
				color: #495057; 
				font-size: 0.95em;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}
			.hk-pb-options { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
			.hk-pb-option { 
				cursor: pointer; 
				display: inline-flex; 
				align-items: center; 
				font-weight: normal; 
				margin: 0; 
				padding: 6px 12px;
				border-radius: 4px;
				transition: background-color 0.2s;
			}
			.hk-pb-option:hover {
				background-color: #f1f3f5;
			}
			.hk-pb-option input { margin-right: 8px; margin-top: 0; }
			.hk-custom-input { 
				margin-left: 8px; 
				display: inline-block; 
				width: 140px; 
				border: 1px solid #ced4da;
				border-radius: 4px;
				padding: 4px 8px;
			}
			.custom-select {
				padding: 4px 8px;
				border: 1px solid #ced4da;
				border-radius: 4px;
				background-color: #fff;
			}


			#ui_decimal_options, #ui_grouping_options, #ui_rounding_container {
				background: #f8f9fa;
				padding: 4px 10px;
				border-radius: 6px;
				border: 1px solid #828484ff;
			}
			.hk-preview-builder {
				padding: 0 15px;
			}
		</style>

		<div class="hk-preview-builder">
			<!-- Live Preview: Positive + Negative -->
			<div class="hk-preview-box">
				<div id="hk_currency_preview_positive" class="hk-preview-positive">Loading...</div>
				<div id="hk_currency_preview_negative" class="hk-preview-negative">Loading...</div>
			</div>

			<!-- Symbol Position -->
			<div class="hk-pb-row">
				<span class="hk-pb-label"><?php echo JText::_('CURRENCY'); ?></span>
				<div class="hk-pb-options">
					<label class="hk-pb-option"><input type="radio" name="ui_symbol_pos" value="1"> <?php echo JText::_('BEFORE_PRICE'); ?> (€1,234.56)</label>
					<label class="hk-pb-option"><input type="radio" name="ui_symbol_pos" value="0"> <?php echo JText::_('AFTER_PRICE'); ?> (1,234.56€)</label>
					<label class="hk-pb-option" style="margin-left: 15px;"><input type="checkbox" id="ui_space" value="1"> <?php echo JText::_('HIKA_SPACE'); ?></label>
				</div>
			</div>

			<!-- Grouping -->
			<div class="hk-pb-row">
				<span class="hk-pb-label"><?php echo JText::_('MON_GROUPING'); ?></span>
				<div class="hk-pb-options">
					<select id="ui_grouping" class="custom-select" style="width:180px; margin-right: 15px;">
						<option value="3" selected>3 (1,234,567)</option>
						<option value="0">0 (1234567)</option>
						<option value="2">2 (1,23,45,67)</option>
						<option value="4">4 (123,4567)</option>
					</select>

					<div id="ui_grouping_options" style="display: inline-flex; align-items: center;">
						<span style="margin-right: 10px; color: #555; font-size: 0.9em;"><?php echo JText::_('MON_THOUSANDS_SEP'); ?></span>
						<label class="hk-pb-option"><input type="radio" name="ui_thousand" value="."> [ <?php echo JText::_('DOT'); ?> ] </label>
						<label class="hk-pb-option"><input type="radio" name="ui_thousand" value=","> [ <?php echo JText::_('COMMA'); ?> ] </label>
						<label class="hk-pb-option"><input type="radio" name="ui_thousand" value=" "> [ <?php echo JText::_('HIKA_SPACE'); ?> ] </label>
						<label class="hk-pb-option"><input type="radio" name="ui_thousand" value=""> [ <?php echo JText::_('HIKA_NONE'); ?> ] </label>
					</div>
				</div>
			</div>

			<!-- Decimals -->
			<div class="hk-pb-row">
				<span class="hk-pb-label"><?php echo JText::_('NUMBER_OF_DECIMALS'); ?></span>
				<div class="hk-pb-options">
					<select id="ui_decimals" class="custom-select" style="width:180px; margin-right: 15px;">
						<option value="0">0 &rarr; 1,235</option>
						<option value="1">1 &rarr; 1,234.6</option>
						<option value="2" selected>2 &rarr; 1,234.56</option>
						<option value="3">3 &rarr; 1,234.560</option>
						<option value="4">4 &rarr; 1,234.5600</option>
					</select>

					<div id="ui_decimal_options" style="display: inline-flex; align-items: center;">
						<span style="margin-right: 10px; color: #555; font-size: 0.9em;"><?php echo JText::_('MON_DECIMAL_POINT'); ?></span>
						<label class="hk-pb-option"><input type="radio" name="ui_decimal" value="."> [ <?php echo JText::_('DOT'); ?> ]</label>
						<label class="hk-pb-option"><input type="radio" name="ui_decimal" value=","> [ <?php echo JText::_('COMMA'); ?> ]</label>
					</div>

					<div id="ui_rounding_container" style="display: inline-flex; align-items: center; margin-left: 20px;">
						<span style="margin-right: 10px; color: #555; font-size: 0.9em;"><?php echo JText::_('ROUNDING_INCREMENT'); ?></span>
						<input type="text" id="ui_rounding" class="hk-custom-input" style="width: 80px;" placeholder="0.05">
					</div>
				</div>
			</div>

			<!-- Negative Sign Position -->
			<div class="hk-pb-row">
				<span class="hk-pb-label"><?php echo JText::_('N_SIGN_POSN'); ?></span>
				<div class="hk-pb-options">
					<label class="hk-pb-option"><input type="radio" name="ui_negative" value="1"> <?php echo JText::_('SIGN_BEFORE'); ?> (-€1,234.56)</label>
					<label class="hk-pb-option"><input type="radio" name="ui_negative" value="4"> <?php echo JText::_('SIGN_AFTER_SYMBOL'); ?> (€-1,234.56)</label>
				</div>
			</div>

			<!-- Format -->
			<div class="hk-pb-row">
				<span class="hk-pb-label"><?php echo JText::_('CURRENCY_FORMAT'); ?></span>
				<div class="hk-pb-options" style="flex-direction: column; align-items: flex-start;">
					<label class="hk-pb-option"><input type="radio" name="ui_format" value="%n"> %n &rarr; €1,234.56 (<?php echo JText::_('STANDARD'); ?>)</label>
					<label class="hk-pb-option"><input type="radio" name="ui_format" value="%i"> %i &rarr; EUR 1,234.56 (<?php echo JText::_('INT_FRAC_DIGITS'); ?>)</label>
					<label class="hk-pb-option"><input type="radio" name="ui_format" value="%n%i"> %n%i &rarr; $1,234.56CAD (CA/AU)</label>
					<label class="hk-pb-option">
						<input type="radio" name="ui_format" value="custom"> <?php echo JText::_('HIKA_CUSTOM'); ?>:
						<input type="text" id="ui_format_custom" placeholder="%n" class="hk-custom-input">
					</label>
				</div>
			</div>

		</div>

		<div style="display:none">
		<div class="hikashop_tile_title"><?php
			echo JText::_('LOCALE_INFORMATION');
		?></div>
					<table class="admintable table" width="280px" style="margin:auto">
						<tr>
							<td class="key">
									<?php echo JText::_( 'CURRENCY_FORMAT' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_format]" value="<?php echo $this->escape(@$this->element->currency_format); ?>" />
								<a class="btn btn-primary" href="http://php.net/manual/function.money-format.php" target="_blank"><i class="fa fa-chevron-right"></i></a>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'MON_DECIMAL_POINT' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][mon_decimal_point]" value="<?php echo @$this->element->currency_locale['mon_decimal_point']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'MON_THOUSANDS_SEP' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][mon_thousands_sep]" value="<?php echo @$this->element->currency_locale['mon_thousands_sep']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'MON_GROUPING' ); ?>
							</td>
							<td>
								<?php if(isset($this->element->currency_locale['mon_grouping']) && is_array($this->element->currency_locale['mon_grouping'])) $this->element->currency_locale['mon_grouping'] = implode(',',$this->element->currency_locale['mon_grouping']); ?>
								<input type="text" name="data[currency][currency_locale][mon_grouping]" value="<?php echo @$this->element->currency_locale['mon_grouping']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'POSITIVE_SIGN' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][positive_sign]" value="<?php echo @$this->element->currency_locale['positive_sign']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'NEGATIVE_SIGN' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][negative_sign]" value="<?php echo @$this->element->currency_locale['negative_sign']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'INT_FRAC_DIGITS' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][int_frac_digits]" value="<?php echo @$this->element->currency_locale['int_frac_digits']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'FRAC_DIGITS' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][frac_digits]" value="<?php echo @$this->element->currency_locale['frac_digits']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'ROUNDING_INCREMENT' ); ?>
							</td>
							<td>
								<input type="text" name="data[currency][currency_locale][rounding_increment]" value="<?php echo @$this->element->currency_locale['rounding_increment']; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'P_CS_PRECEDES' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_locale][p_cs_precedes]" , '',@$this->element->currency_locale['p_cs_precedes']); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'P_SEP_BY_SPACE' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_locale][p_sep_by_space]" , '',@$this->element->currency_locale['p_sep_by_space']); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'N_CS_PRECEDES' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_locale][n_cs_precedes]" , '',@$this->element->currency_locale['n_cs_precedes']); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'N_SEP_BY_SPACE' ); ?>
							</td>
							<td>
								<?php echo JHTML::_('hikaselect.booleanlist', "data[currency][currency_locale][n_sep_by_space]" , '',@$this->element->currency_locale['n_sep_by_space']); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'P_SIGN_POSN' ); ?>
							</td>
							<td>
								<?php echo $this->signpos->display('data[currency][currency_locale][p_sign_posn]',@$this->element->currency_locale['p_sign_posn']); ?>
							</td>
						</tr>
						<tr>
							<td class="key">
									<?php echo JText::_( 'N_SIGN_POSN' ); ?>
							</td>
							<td>
								<?php echo $this->signpos->display('data[currency][currency_locale][n_sign_posn]',@$this->element->currency_locale['n_sign_posn']); ?>
							</td>
						</tr>
					</table>
				</fieldset>
		</div> <!-- End Hidden Original Table -->
	</div></div>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {

			function getCurrencyCode() {
				var codeInput = document.querySelector('input[name="data[currency][currency_code]"]');
				return codeInput ? codeInput.value : 'EUR';
			}

			window.hkUpdatePreview = function() {
				var symbol = document.querySelector('input[name="data[currency][currency_symbol]"]').value || '€';
				var code = getCurrencyCode();
				var symbolPos = getRadioVal('ui_symbol_pos') === '1' ? 'before' : 'after';
				var isSpace = document.getElementById('ui_space').checked;
				var thousand = getRadioVal('ui_thousand');
				var decimal = getRadioVal('ui_decimal') || '.';

				var decimalsInput = document.getElementById('ui_decimals');
				var decimals = parseInt(decimalsInput.value);
				if(isNaN(decimals)) decimals = 2; // Default if invalid

				var decimalPointsContainer = document.getElementById('ui_decimal_options');
				var roundingContainer = document.getElementById('ui_rounding_container');
				if(decimalPointsContainer) {
					decimalPointsContainer.style.display = (decimals === 0) ? 'none' : 'inline-flex';
				}
				if(roundingContainer) {
					roundingContainer.style.display = (decimals === 0) ? 'none' : 'inline-flex';
				}

				var groupingInput = document.getElementById('ui_grouping');
				var grouping = parseInt(groupingInput.value);
				if(isNaN(grouping)) grouping = 3;

				var groupingOptionsContainer = document.getElementById('ui_grouping_options');
				if(groupingOptionsContainer) {
					groupingOptionsContainer.style.display = (grouping === 0) ? 'none' : 'inline-flex';
				}

				var negStyle = getRadioVal('ui_negative') || '1';
				var formatType = getRadioVal('ui_format') || '%n';
				var formatCustom = document.getElementById('ui_format_custom').value;

				var rounding = parseFloat(document.getElementById('ui_rounding').value);

				var posAmount = 1234567.5678; 
				var negAmount = 1234567.5678; 

				if(!isNaN(rounding) && rounding > 0) {
					posAmount = roundByIncrement(posAmount, rounding);
					negAmount = roundByIncrement(negAmount, rounding);
				}

				var posNumStr = number_format(posAmount, decimals, decimal, thousand, grouping);
				var negNumStr = number_format(negAmount, decimals, decimal, thousand, grouping);

				var posPreview = buildPreview(posNumStr, symbol, code, symbolPos, formatType, formatCustom, false, negStyle, isSpace);
				var negPreview = buildPreview(negNumStr, symbol, code, symbolPos, formatType, formatCustom, true, negStyle, isSpace);

				document.getElementById('hk_currency_preview_positive').innerHTML = posPreview;
				document.getElementById('hk_currency_preview_negative').innerHTML = negPreview;

				syncToHidden();
			};

			function buildPreview(numStr, symbol, code, symbolPos, formatType, formatCustom, isNegative, negStyle, isSpace) {
				var effectiveFormat = formatType;
				var space = isSpace ? ' ' : '';

				if(formatType === 'custom' && formatCustom) {
					effectiveFormat = formatCustom;
				}

				var prefix = '';
				var suffix = '';
				var cprefix = '';
				var csuffix = '';
				var signal = isNegative ? '-' : '';

				if(isNegative) {
					switch(negStyle) {
						case '1': prefix = signal; break;  // Sign before everything
						case '2': suffix = signal; break;  // Sign after everything
						case '3': cprefix = signal; break; // Sign before currency symbol
						case '4': csuffix = signal; break; // Sign after currency symbol
						default: prefix = signal; break;
					}
				}

				var currency = '';
				if(effectiveFormat === '%i') {
					currency = cprefix + code + csuffix;
				} else if(effectiveFormat === '%n') {
					currency = cprefix + symbol + csuffix;
				} else if(effectiveFormat === '%n%i' || effectiveFormat === '%in') {
					if(effectiveFormat === '%n%i') {
						currency = cprefix + symbol + ' ' + code + csuffix;
					} else {
						currency = cprefix + code + ' ' + symbol + csuffix;
					}
				} else {
					var customResult = effectiveFormat;
					var nationalCurrency = cprefix + symbol + csuffix;
					var intlCurrency = cprefix + code + csuffix;

					if(customResult.indexOf('%n') !== -1 || customResult.indexOf('%i') !== -1) {
						var nReplace = (symbolPos === 'before') ? (nationalCurrency + space + numStr) : (numStr + space + nationalCurrency);
						var iReplace = (symbolPos === 'before') ? (intlCurrency + ' ' + numStr) : (numStr + ' ' + intlCurrency);
						customResult = customResult.replace(/%n/g, nReplace);
						customResult = customResult.replace(/%i/g, iReplace);
						return prefix + customResult + suffix;
					} else {
						currency = cprefix + symbol + csuffix;
						var val = (symbolPos === 'before') ? (currency + space + numStr) : (numStr + space + currency);
						return prefix + effectiveFormat + ' → ' + val + suffix;
					}
				}

				var result;
				if(symbolPos === 'before') {
					result = prefix + currency + space + numStr + suffix;
				} else {
					result = prefix + numStr + space + currency + suffix;
				}

				return result;
			}

			function roundByIncrement(number, increment) {
				if (!increment || increment <= 0) return number;
				return Math.round(number / increment) * increment;
			}

			function number_format(number, decimals, dec_point, thousands_sep, grouping) {
				number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
				var n = !isFinite(+number) ? 0 : +number,
					prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
					sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
					dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
					s = '',
					toFixedFix = function (n, prec) {
						var k = Math.pow(10, prec);
						return '' + Math.round(n * k) / k;
					};
				s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');

				if(grouping > 0 && sep !== '') {
					var intPart = s[0];
					var rgx = new RegExp('(\\d+)(\\d{' + grouping + '})');
					while (rgx.test(intPart)) {
						intPart = intPart.replace(rgx, '$1' + sep + '$2');
					}
					s[0] = intPart;
				}

				if ((s[1] || '').length < prec) {
					s[1] = s[1] || '';
					s[1] += new Array(prec - s[1].length + 1).join('0');
				}
				return s.join(dec);
			}

			function getRadioVal(name) {
				var el = document.querySelector('input[name="'+name+'"]:checked');
				return el ? el.value : '';
			}

			function setRadioVal(name, val) {
				var el = document.querySelector('input[name="'+name+'"][value="'+val+'"]');
				if(el) { 
					el.checked = true;
				}
			}

			function setSelectVal(id, val) {
				var el = document.getElementById(id);
				if(el) el.value = val;
			}

			function syncToHidden() {
				var symPos = getRadioVal('ui_symbol_pos') === '1';
				setBooleanList('data[currency][currency_locale][p_cs_precedes]', symPos ? 1 : 0);
				setBooleanList('data[currency][currency_locale][n_cs_precedes]', symPos ? 1 : 0);

				var isSpace = document.getElementById('ui_space').checked;
				setBooleanList('data[currency][currency_locale][p_sep_by_space]', isSpace ? 1 : 0);
				setBooleanList('data[currency][currency_locale][n_sep_by_space]', isSpace ? 1 : 0);

				var thousand = getRadioVal('ui_thousand');
				var thousandEl = document.getElementsByName('data[currency][currency_locale][mon_thousands_sep]')[0];
				if(thousandEl) thousandEl.value = thousand;

				var grouping = document.getElementById('ui_grouping').value;
				var groupingEl = document.getElementsByName('data[currency][currency_locale][mon_grouping]')[0];
				if(groupingEl) groupingEl.value = grouping;

				var decimal = getRadioVal('ui_decimal');
				var decimalEl = document.getElementsByName('data[currency][currency_locale][mon_decimal_point]')[0];
				if(decimalEl) decimalEl.value = decimal;

				var decimals = document.getElementById('ui_decimals').value;
				var fracEl = document.getElementsByName('data[currency][currency_locale][frac_digits]')[0];
				var intFracEl = document.getElementsByName('data[currency][currency_locale][int_frac_digits]')[0];
				if(fracEl) fracEl.value = decimals;
				if(intFracEl) intFracEl.value = decimals;

				var rounding = document.getElementById('ui_rounding').value;
				var roundingEl = document.getElementsByName('data[currency][currency_locale][rounding_increment]')[0];
				if(roundingEl) roundingEl.value = rounding;

				var neg = getRadioVal('ui_negative');
				var nSignEl = document.getElementsByName('data[currency][currency_locale][n_sign_posn]')[0];
				if(nSignEl) nSignEl.value = neg;

				var fmt = getRadioVal('ui_format');
				var fmtCustom = document.getElementById('ui_format_custom').value;
				var finalFmt = (fmt === 'custom') ? fmtCustom : fmt;
				var formatEl = document.getElementsByName('data[currency][currency_format]')[0];
				if(formatEl) formatEl.value = finalFmt;
			}

			function setBooleanList(name, val) {
				var el = document.querySelector('input[name="'+name+'"][value="'+val+'"]');
				if(el) el.checked = true;
			}

			function initFromHidden() {
				var p_cs = document.querySelector('input[name="data[currency][currency_locale][p_cs_precedes]"]:checked');
				var pos = (p_cs && p_cs.value === '1') ? '1' : '0';
				if(!p_cs) pos = '1';
				setRadioVal('ui_symbol_pos', pos);

				var sep_space = document.querySelector('input[name="data[currency][currency_locale][p_sep_by_space]"][value="1"]');
				var isSpace = (sep_space && sep_space.checked) ? true : false;
				var sep_checked = document.querySelector('input[name="data[currency][currency_locale][p_sep_by_space]"]:checked');
				if(sep_checked && sep_checked.value === '1') isSpace = true;
				document.getElementById('ui_space').checked = isSpace;

				var thousandEl = document.getElementsByName('data[currency][currency_locale][mon_thousands_sep]')[0];
				var thousand = thousandEl ? thousandEl.value : ',';
				if(thousand === ' ') thousand = ' ';
				setRadioVal('ui_thousand', thousand);

				var groupingEl = document.getElementsByName('data[currency][currency_locale][mon_grouping]')[0];
				var grouping = groupingEl ? groupingEl.value : '3';
				if(grouping && grouping.indexOf(',') > -1) grouping = grouping.split(',')[0];
				if(!grouping) grouping = '3';
				setSelectVal('ui_grouping', parseInt(grouping));

				var decimalEl = document.getElementsByName('data[currency][currency_locale][mon_decimal_point]')[0];
				var decimal = decimalEl ? decimalEl.value : '.';
				if(!decimal) decimal = '.'; // Default to dot
				setRadioVal('ui_decimal', decimal);

				var fracEl = document.getElementsByName('data[currency][currency_locale][frac_digits]')[0];
				var decimals = fracEl ? fracEl.value : '2';
				if(!decimals || decimals === '') decimals = '2'; // Default to 2
				setSelectVal('ui_decimals', parseInt(decimals));

				var roundingEl = document.getElementsByName('data[currency][currency_locale][rounding_increment]')[0];
				var rounding = roundingEl ? roundingEl.value : '';
				document.getElementById('ui_rounding').value = rounding;

				var nSignEl = document.getElementsByName('data[currency][currency_locale][n_sign_posn]')[0];
				var nSign = nSignEl ? nSignEl.value : '1';
				if(!nSign || nSign === '') nSign = '1';
				setRadioVal('ui_negative', nSign);

				var formatEl = document.getElementsByName('data[currency][currency_format]')[0];
				var fmt = formatEl ? formatEl.value : '%n';
				if(!fmt) fmt = '%n';

				if(fmt === '%i' || fmt === '%n' || fmt === '%n%i') {
					setRadioVal('ui_format', fmt);
				} else {
					setRadioVal('ui_format', 'custom');
					document.getElementById('ui_format_custom').value = fmt;
				}

				window.hkUpdatePreview();
			}

			var uiInputs = document.querySelectorAll('.hk-preview-builder input, .hk-preview-builder select');
			uiInputs.forEach(function(el){
				el.addEventListener('change', window.hkUpdatePreview);
				el.addEventListener('input', window.hkUpdatePreview);
			});

			var symbolInput = document.querySelector('input[name="data[currency][currency_symbol]"]');
			if(symbolInput) {
				symbolInput.addEventListener('input', window.hkUpdatePreview);
			}
			var codeInput = document.querySelector('input[name="data[currency][currency_code]"]');
			if(codeInput) {
				codeInput.addEventListener('input', window.hkUpdatePreview);
			}

			setTimeout(initFromHidden, 300);
		});
	</script>

</div>
	<div class="clr"></div>
	<input type="hidden" name="cid[]" value="<?php echo @$this->element->currency_id; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="currency" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
