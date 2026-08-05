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

$labelcolumnclass = 'hkc-sm-4';
$inputcolumnclass = 'hkc-sm-8';
if(!isset($this->address)) $this->address = null;
$config = hikashop_config();
$isEnabled = $config->get('address_form_layout_enabled', 0);
$layoutConfig = $config->get('address_form_layout', '');
if(empty($layoutConfig)) {
    $fieldClass = hikashop_get('class.field');
	$layout = $fieldClass->getAddressLayoutDefault($this->fields);
} else {
    $layout = json_decode($layoutConfig, true);
}


if(empty($layout) || empty($layout['rows']) || !$isEnabled) {
    foreach($this->fields as $field) {
        if(empty($field->field_frontcomp))
            continue;
        if(empty($this->onChangeJs)) {
            $toggleJs = "window.hikashop.toggleField(this,'".$field->field_namekey."','".str_replace('hikashop_', '', $this->idPrefix)."',0,'hikashop_');";
        } else {
            $toggleJs = str_replace('{FIELD}', $field->field_namekey, $this->onChangeJs);
        }
        echo $this->loadHkLayout('address_field_row', array('field' => $field, 'onChangeJs' => $toggleJs, 'idPrefix' => $this->idPrefix, 'inputPrefix' => $this->inputPrefix, 'fields' => $this->fields, 'address' => $this->address));
    }
    return;
}

$fieldsByKey = array();
foreach($this->fields as $field) {
    if(empty($field->field_frontcomp)) continue;
    $fieldsByKey[$field->field_namekey] = $field;
}

$globalHideLabels = !empty($layout['global_hide_labels']);
$cols = isset($layout['columns']) ? (int)$layout['columns'] : 4;
if($cols < 1) $cols = 4;
$columnsClass = 12 / $cols;

foreach($layout['rows'] as $row) {
    if(empty($row['fields']))
        continue;
?>
<div class="hk-row-fluid hikashop_address_row">
<?php
    foreach($row['fields'] as $fieldConfig) {
        $namekey = $fieldConfig['namekey'];
        if(!isset($fieldsByKey[$namekey]))
            continue;

        $field = $fieldsByKey[$namekey];

        $width = (int)$fieldConfig['width'];
        $labelPos = isset($fieldConfig['label_position']) ? $fieldConfig['label_position'] : null;
        if($labelPos === null) $labelPos = @$layout['label_position'];

        $hideLabel = ($labelPos === 'no');
        $labelAbove = ($labelPos === 'above');

        if($labelAbove || $hideLabel) {
            $labelcolumnclass = 'hkc-sm-12';
            $inputcolumnclass = 'hkc-sm-12';
        }

		if ($hideLabel && empty($field->field_options['placeholder'])){
            if(empty($field->field_options)) $field->field_options = array();
			$field->field_options['placeholder'] = strip_tags(JText::_($field->field_realname));
		}

        $colClass = 'hkc-sm-' . round($width * $columnsClass);

        $fieldname = $field->field_namekey;
        $onWhat = ($field->field_type == 'radio') ? 'onclick' : 'onchange';
        if(empty($this->onChangeJs)) {
		    $toggleJs = "window.hikashop.toggleField(this,'".$fieldname."','".str_replace('hikashop_', '', $this->idPrefix)."',0,'hikashop_');";
        } else {
            $toggleJs = str_replace('{FIELD}', $fieldname, $this->onChangeJs);
        }

        $html = $this->fieldClass->display(
            $field,
            @$this->address->$fieldname,
            $this->inputPrefix . '[' . $fieldname . ']',
            false,
            ' class="' . HK_FORM_CONTROL_CLASS . '" ' . $onWhat . '="' . $toggleJs . '"',
            false,
            $this->fields,
            $this->address,
            false
        );

        if($field->field_type == 'hidden') {
            echo $html;
            continue;
        }
?>
    <div class="<?php echo $colClass; ?>">
        <div class="hkform-group control-group hikashop_address_<?php echo $fieldname; ?> <?php if($labelAbove) echo 'hk-label-above'; ?>" id="<?php echo $this->idPrefix . '_' . $fieldname; ?>">
<?php
        if(!$hideLabel) {
            echo $this->fieldClass->getFieldName($field, true, $labelcolumnclass.' hkcontrol-label');
        }
?>
            <div class="hikashop_address_field_input <?php echo $inputcolumnclass; ?>">
                <?php echo $html; ?>
            </div>
        </div>
    </div>
<?php
        unset($fieldsByKey[$namekey]);
    }
?>
</div>
<?php
}

foreach($fieldsByKey as $field) {
    if(empty($this->onChangeJs)) {
        $toggleJs = "window.hikashop.toggleField(this,'".$field->field_namekey."','".str_replace('hikashop_', '', $this->idPrefix)."',0,'hikashop_');";
    } else {
        $toggleJs = str_replace('{FIELD}', $field->field_namekey, $this->onChangeJs);
    }
    echo $this->loadHkLayout('address_field_row', array('field' => $field, 'onChangeJs' => $toggleJs, 'idPrefix' => $this->idPrefix, 'inputPrefix' => $this->inputPrefix, 'fields' => $this->fields, 'address' => $this->address));
}
