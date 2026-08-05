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
?><div class="hikashop_dashboard_settings">
	<?php if(!empty($this->error)) { ?>
		<div class="alert alert-error alert-danger">
			<?php echo JText::_($this->error); ?>
		</div>
	<?php return; } ?>

	<h2><?php echo JText::sprintf('HIKA_WIDGET_SETTINGS_TITLE', isset($this->widget_label) ? $this->widget_label : $this->widget); ?></h2>
	<form id="widget-settings-form" class="form-horizontal">
		<?php
		if(is_array($this->form)) {
			foreach($this->form as $name => $field) {
				$fieldName = 'params['.$name.']';
				$value = $this->params->get($name, isset($field['default']) ? $field['default'] : null);

				if(!empty($field['multiple']) && is_string($value) && strpos($value, ',') !== false) {
					$value = explode(',', $value);
				}

				echo '<div class="control-group">';
				echo '<div class="control-label"><label for="'.$name.'">'.JText::_($field['label']).'</label></div>';
				echo '<div class="controls">';

				if($field['type'] == 'radio') {
					echo JHtml::_('select.radiolist', 
						array_map(function($k,$v){ return JHtml::_('select.option', $k, JText::_($v)); }, array_keys($field['values']), $field['values']),
						$fieldName, 'class="btn-group"', 'value', 'text', $value
					);
				} elseif($field['type'] == 'list') {
					$multiple = !empty($field['multiple']);
					echo JHtml::_('select.genericlist',
						array_map(function($k,$v){ return JHtml::_('select.option', $k, JText::_($v)); }, array_keys($field['values']), $field['values']),
						$fieldName . ($multiple ? '[]' : ''),
						'class="input-xlarge custom-select" ' . ($multiple ? 'multiple="multiple"' : ''),
						'value', 'text', $value
					);
				} elseif($field['type'] == 'order_status') {
					$orderStatusType = hikashop_get('type.order_status');
					$multiple = !empty($field['multiple']);
					echo $orderStatusType->display($fieldName . ($multiple ? '[]' : ''), $value, $multiple ? 'multiple="multiple"' : '');
				} elseif($field['type'] == 'text') {
					echo '<input type="text" name="'.$fieldName.'" value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'" />';
				} elseif($field['type'] == 'textarea') {
					echo '<textarea name="'.$fieldName.'" class="input-xlarge" width="100%" rows="4">'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'</textarea>';
				}

				if(isset($field['description']) || JText::_($field['label'].'_DESC') != $field['label'].'_DESC') {
					echo '<div class="muted">'.(isset($field['description']) ? JText::_($field['description']) : JText::_($field['label'].'_DESC')).'</div>';
				}

				echo '</div></div>';
			}
		} else {
			echo $this->form;
		}
		?>
		<input type="hidden" name="widget_key" value="<?php echo $this->widget; ?>" />

		<div class="control-group">
			<div class="control-label">
				<label for="custom_title" title="<?php echo JText::_('HIKA_CUSTOM_NAME_DESC'); ?>">
					<?php echo JText::_('HIKA_CUSTOM_NAME'); ?>
				</label>
			</div>
			<div class="controls">
				<input type="text" name="params[custom_title]" id="custom_title" value="<?php echo htmlspecialchars($this->params->get('custom_title', isset($this->widget_type_label) ? $this->widget_type_label : ''), ENT_QUOTES, 'UTF-8'); ?>" />
			</div>
		</div>

		<div class="form-actions mt-3">
			<button type="button" class="btn btn-success" onclick="saveWidgetSettings()"><i class="fas fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?></button>
			<button type="button" class="btn btn-danger" onclick="window.parent.hikashop.closeBox()"><?php echo JText::_('HIKA_CANCEL'); ?></button>
		</div>
	</form>
</div>

<script type="text/javascript">
	function saveWidgetSettings() {
		var form = document.getElementById('widget-settings-form');
		var data = {};

		for(var i=0; i<form.elements.length; i++) {
			var input = form.elements[i];
			if(!input.name || input.name.indexOf('params[') !== 0) continue;

			var keyEnd = input.name.indexOf(']');
			if(keyEnd === -1) continue;

			var key = input.name.substring(7, keyEnd);
			var isArray = input.name.indexOf('[]') !== -1;
			var value = null;

			if(input.tagName === 'SELECT' && input.multiple) {
				value = [];
				for(var j=0; j<input.options.length; j++) {
					if(input.options[j].selected) {
						value.push(input.options[j].value);
					}
				}
			} else if(input.type === 'checkbox' || input.type === 'radio') {
				if(input.checked) value = input.value;
			} else {
				value = input.value;
			}

			if(value !== null) {
				if(isArray) {
					if(!data[key]) data[key] = [];
					if(Array.isArray(value)) {
						data[key] = data[key].concat(value);
					} else {
						data[key].push(value);
					}
				} else {
					data[key] = value;
				}
			}
		}

		if(window.parent && window.parent.hikashop_dashboard) {
			window.parent.hikashop_dashboard.saveSettings(data);
		} else {
			alert('Cannot communicate with parent window.');
		}
	}
</script>
<style>
	body { padding: 20px; }
	.form-actions {
		padding-top: 20px;
		border-top: 1px solid #eee;
		text-align: right;
	}
	.btn { margin-left: 5px; }
    input[type="text"], textarea, select {
        width: 100% !important;
    }
    .muted {
        color: #999;
        font-size: 14px;
        font-style: italic;
    }
</style>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		if(window.parent && window.parent.currentWidgetSettings && window.parent.currentWidgetSettings.dataset.params) {
			try {
				var params = JSON.parse(window.parent.currentWidgetSettings.dataset.params);
				var fields = ['prefix', 'dynamic', 'suffix', 'custom_title'];
				fields.forEach(function(key) {
					if(params[key]) {
						var el = document.getElementsByName('params['+key+']')[0];
						if(el) el.value = params[key];
					}
				});
			} catch(e) { console.error('Error populating params from parent', e); }
		}
	});
</script>
